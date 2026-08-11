<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Support\Carbon;
use App\Concerns\MatrixSummary;

class GuestMatrixController extends Controller
{
    use MatrixSummary;

    public function index()
    {
        $currentYear  = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;

        $months = [];
        for ($i = 1; $i <= $currentMonth; $i++) {
            $months[$i] = Carbon::create()->month($i)->translatedFormat('F');
        }

        $rootForms = Form::with('children.children.children.children')
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();

        $submissions = FormSubmission::whereYear('date', $currentYear)->get();

        // Pre-build O(1) lookup: [form_id][month] = true
        // This avoids iterating ALL submissions inside every form×month loop
        $filledLookup = $this->buildFilledLookup($submissions, $currentYear);

        $matrix  = $this->buildMatrixRows($rootForms, $months, $currentYear, $filledLookup);
        $summary = $this->buildMonthlySummary($months, $currentYear, $submissions);

        $matrixStatusMap = [];
        foreach ($matrix as $row) {
            $matrixStatusMap[$row['id']] = $row['status'];
        }

        return view('guest-matrix', [
            'months'          => $months,
            'matrix'          => $matrix,
            'matrixStatusMap' => $matrixStatusMap,
            'summary'         => $summary,
            'currentYear'     => $currentYear,
            'isLive'          => false,
        ]);
    }

    public function live(\Illuminate\Http\Request $request, \App\Services\GoogleFormsSyncService $syncService)
    {
        $currentYear  = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;

        $months = [];
        for ($i = 1; $i <= $currentMonth; $i++) {
            $months[$i] = Carbon::create()->month($i)->translatedFormat('F');
        }

        $rootForms = Form::with('children.children.children.children')
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();

        // Fetch from Google Forms API and keep in memory
        $formId          = '1bJXgi5GMANXfesZAXkn77f4KFugpXCwLN7nLkEIKQjk';
        $liveSubmissions = $syncService->getLiveResponses($formId, 'mobile-ladder');

        // Cache as plain PHP array (NOT Collection) — Collections fail to unserialize from file cache
        \Illuminate\Support\Facades\Cache::put(
            'live_submissions_' . session()->getId(),
            $liveSubmissions->values()->toArray(),
            now()->addMinutes(30)
        );

        // Build lookup for live submissions (plain arrays)
        $filledLookup = $this->buildFilledLookupFromArrays($liveSubmissions, $currentYear);

        $matrix  = $this->buildMatrixRows($rootForms, $months, $currentYear, $filledLookup);
        $summary = $this->buildMonthlySummary($months, $currentYear, $liveSubmissions);

        $matrixStatusMap = [];
        foreach ($matrix as $row) {
            $matrixStatusMap[$row['id']] = $row['status'];
        }

        return view('guest-matrix', [
            'months'          => $months,
            'matrix'          => $matrix,
            'matrixStatusMap' => $matrixStatusMap,
            'summary'         => $summary,
            'currentYear'     => $currentYear,
            'isLive'          => true,
        ]);
    }

    /**
     * AJAX polling endpoint — returns fresh filledLookup as JSON without full page reload.
     * Called periodically by the auto-refresh toggle in Live Mode.
     */
    public function livePoll(\App\Services\GoogleFormsSyncService $syncService): \Illuminate\Http\JsonResponse
    {
        $currentYear = Carbon::now()->year;

        $formId          = '1bJXgi5GMANXfesZAXkn77f4KFugpXCwLN7nLkEIKQjk';
        $liveSubmissions = $syncService->getLiveResponses($formId, 'mobile-ladder');

        // Update the cache so the submission-detail modal still works
        \Illuminate\Support\Facades\Cache::put(
            'live_submissions_' . session()->getId(),
            $liveSubmissions->values()->toArray(),
            now()->addMinutes(30)
        );

        $filledLookup = $this->buildFilledLookupFromArrays($liveSubmissions, $currentYear);
        
        $months = [];
        $currentMonth = Carbon::now()->month;
        for ($i = 1; $i <= $currentMonth; $i++) {
            $months[$i] = Carbon::create()->month($i)->translatedFormat('F');
        }
        
        $rootForms = Form::with('children.children.children.children')
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();
            
        $matrix  = $this->buildMatrixRows($rootForms, $months, $currentYear, $filledLookup);
        
        $matrixStatusMap = [];
        foreach ($matrix as $row) {
            $matrixStatusMap[$row['id']] = $row['status'];
        }

        return response()->json([
            'matrixStatusMap' => $matrixStatusMap,
            'refreshedAt'     => now()->translatedFormat('H:i:s'),
        ]);
    }

    /**
     * Build a nested lookup from Eloquent FormSubmission collection.
     * Result: $lookup[form_id][month] = true
     *
     * @param  \Illuminate\Support\Collection<int, FormSubmission>  $submissions
     */
    private function buildFilledLookup(\Illuminate\Support\Collection $submissions, int $year): array
    {
        $lookup = [];
        foreach ($submissions as $sub) {
            $fid   = $sub->form_id;
            $month = (int) Carbon::parse($sub->date)->format('n');
            $yr    = (int) Carbon::parse($sub->date)->format('Y');
            if ($yr === $year) {
                $lookup[$fid][$month] = true;
            }
        }
        return $lookup;
    }

    /**
     * Build a nested lookup from plain-array live submissions.
     * Result: $lookup[form_id][month] = true
     *
     * @param  \Illuminate\Support\Collection<int, array>  $submissions
     */
    private function buildFilledLookupFromArrays(\Illuminate\Support\Collection $submissions, int $year): array
    {
        $lookup = [];
        foreach ($submissions as $sub) {
            $fid   = $sub['form_id'] ?? null;
            $date  = $sub['date'] ?? null;
            if (!$fid || !$date) continue;
            $month = (int) Carbon::parse($date)->format('n');
            $yr    = (int) Carbon::parse($date)->format('Y');
            if ($yr === $year) {
                $lookup[$fid][$month] = true;
            }
        }
        return $lookup;
    }

    /**
     * Build flattened matrix rows recursively with 3-state status.
     * Uses pre-built $filledLookup for O(1) per-cell status checks.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Form>  $forms
     * @param  array<int, string>  $months
     * @param  array<int, array<int, bool>>  $filledLookup  [form_id][month] = true
     * @return array<int, array<string, mixed>>
     */
    private function buildMatrixRows(
        $forms,
        array $months,
        int $currentYear,
        array $filledLookup,
        int $level = 0,
        ?int $parentId = null,
        array $ancestors = []
    ): array {
        $rows = [];

        foreach ($forms as $form) {
            $hasChildren = $form->children->count() > 0;

            $leafIds = $hasChildren
                ? $this->getLeafDescendantIds($form)
                : [$form->id];

            $status = [];
            foreach ($months as $monthNum => $monthName) {
                if ($hasChildren) {
                    $filledCount = 0;
                    foreach ($leafIds as $lid) {
                        if (isset($filledLookup[$lid][$monthNum])) {
                            $filledCount++;
                        }
                    }
                    $totalLeaves = count($leafIds);

                    if ($filledCount === 0) {
                        $status[$monthNum] = 'none';
                    } elseif ($filledCount >= $totalLeaves) {
                        $status[$monthNum] = 'full';
                    } else {
                        $status[$monthNum] = 'partial';
                    }
                } else {
                    $status[$monthNum] = isset($filledLookup[$form->id][$monthNum]) ? 'full' : 'none';
                }
            }

            $rows[] = [
                'id'          => $form->id,
                'parent_id'   => $parentId,
                'ancestors'   => $ancestors,
                'level'       => $level,
                'title'       => $form->title,
                'slug'        => $form->slug ?? null,
                'is_category' => $form->is_category,
                'has_children'=> $hasChildren,
                'status'      => $status,
            ];

            if ($hasChildren) {
                $newAncestors = array_merge($ancestors, [$form->id]);
                $childRows    = $this->buildMatrixRows($form->children, $months, $currentYear, $filledLookup, $level + 1, $form->id, $newAncestors);
                $rows         = array_merge($rows, $childRows);
            }
        }

        return $rows;
    }

    /**
     * Get IDs of all leaf descendants (forms with no children).
     *
     * @return array<int>
     */
    private function getLeafDescendantIds(Form $form): array
    {
        $ids = [];
        foreach ($form->children as $child) {
            if ($child->children->count() === 0) {
                $ids[] = $child->id;
            } else {
                $ids = array_merge($ids, $this->getLeafDescendantIds($child));
            }
        }
        return $ids;
    }
}

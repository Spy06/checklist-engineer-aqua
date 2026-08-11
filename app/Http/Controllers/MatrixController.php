<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use App\Concerns\MatrixSummary;

class MatrixController extends Controller
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

        // Always show all root forms in the matrix (regardless of user role)
        $rootForms = Form::with('children.children.children.children')->whereNull('parent_id')->orderBy('order')->get();
        $submissions = FormSubmission::whereYear('date', $currentYear)->get();

        $matrix = $this->buildMatrixRows($rootForms, $months, $currentYear, $submissions);
        $summary = $this->buildMonthlySummary($months, $currentYear);

        return view('matrix', [
            'months'      => $months,
            'matrix'      => $matrix,
            'summary'     => $summary,
            'currentYear' => $currentYear,
        ]);
    }

    /**
     * Return submission detail data for a specific form+month+year as JSON.
     */
    public function submissionDetail(Request $request): JsonResponse
    {
        $formId = $request->integer('form_id');
        $month  = $request->integer('month');
        $year   = $request->integer('year');
        $isLive = $request->boolean('live');

        $form = Form::find($formId);
        if (! $form) {
            return response()->json(['error' => 'Form tidak ditemukan.'], 404);
        }

        $parseNotes = function (?string $rawNotes) {
            if (!$rawNotes) return ['notes' => null, 'failures' => []];
            $notes = $rawNotes;
            $failures = [];
            if (str_contains($notes, '[IMPORTED FAILURES]')) {
                preg_match('/\[IMPORTED FAILURES\]:\s*(.*)/', $notes, $matches);
                if (!empty($matches[1])) {
                    $failures = array_map('trim', explode(',', $matches[1]));
                }
                $notes = trim(preg_replace('/\[IMPORTED FAILURES\].*/', '', $notes));
            }
            return [
                'notes' => $notes ?: null,
                'failures' => $failures
            ];
        };

        if ($isLive) {
            // Data stored as plain PHP array — safe for file cache serialization
            $liveData = \Illuminate\Support\Facades\Cache::get('live_submissions_' . session()->getId());

            if (!$liveData) {
                return response()->json(['error' => 'Data live sudah kedaluwarsa. Silakan muat ulang halaman Live Mode.'], 410);
            }

            $submissions = collect($liveData)
                ->filter(fn($s) => ($s['form_id'] ?? null) == $formId)
                ->filter(fn($s) => Carbon::parse($s['date'] ?? '')->month == $month && Carbon::parse($s['date'] ?? '')->year == $year)
                ->sortBy('date')
                ->values()
                ->map(function ($sub) use ($parseNotes) {
                    $parsed = $parseNotes($sub['notes'] ?? null);
                    return [
                        'id'         => $sub['id'] ?? '-',
                        'date'       => Carbon::parse($sub['date'] ?? '')->translatedFormat('d F Y'),
                        'user_name'  => $sub['user_name'] ?? '-',
                        'notes'      => $parsed['notes'],
                        'failures'   => $parsed['failures'],
                        'answers'    => $sub['answers'] ?? [],
                    ];
                });
        } else {
            $submissions = FormSubmission::with('user')
                ->where('form_id', $formId)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->orderBy('date', 'asc')
                ->get()
                ->map(function ($sub) use ($parseNotes) {
                    $parsed = $parseNotes($sub->notes);
                    return [
                        'id'         => $sub->id,
                        'date'       => $sub->date->translatedFormat('d F Y'),
                        'user_name'  => $sub->user?->name ?? '-',
                        'notes'      => $parsed['notes'],
                        'failures'   => $parsed['failures'],
                        'answers'    => $sub->answers,
                    ];
                });
        }

        return response()->json([
            'form_title'  => $form->title,
            'submissions' => $submissions,
        ]);
    }

    /**
     * Build flattened matrix rows recursively with 3-state status.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Form>  $forms
     * @param  array<int, string>  $months
     * @return array<int, array<string, mixed>>
     */
    private function buildMatrixRows($forms, array $months, int $currentYear, \Illuminate\Support\Collection $submissions, int $level = 0, ?int $parentId = null, array $ancestors = []): array
    {
        $rows = [];

        foreach ($forms as $form) {
            $hasChildren = $form->children->count() > 0;

            // Get all "leaf" descendants (forms without children) for accurate status
            $leafIds = $this->getLeafDescendantIds($form);

            // If this form itself is a leaf, just use its own ID
            if (! $hasChildren) {
                $leafIds = [$form->id];
            }

            $status = [];
            foreach ($months as $monthNum => $monthName) {
                if ($hasChildren) {
                    $filledCount = $submissions->whereIn('form_id', $leafIds)
                        ->filter(fn($s) => Carbon::parse($s->date)->month == $monthNum && Carbon::parse($s->date)->year == $currentYear)
                        ->unique('form_id')
                        ->count();

                    $totalLeaves = count($leafIds);

                    if ($filledCount === 0) {
                        $status[$monthNum] = 'none';
                    } elseif ($filledCount >= $totalLeaves) {
                        $status[$monthNum] = 'full';
                    } else {
                        $status[$monthNum] = 'partial';
                    }
                } else {
                    $exists = $submissions->where('form_id', $form->id)
                        ->filter(fn($s) => Carbon::parse($s->date)->month == $monthNum && Carbon::parse($s->date)->year == $currentYear)
                        ->isNotEmpty();

                    $status[$monthNum] = $exists ? 'full' : 'none';
                }
            }

            $rows[] = [
                'id'           => $form->id,
                'parent_id'    => $parentId,
                'ancestors'    => $ancestors,
                'level'        => $level,
                'title'        => $form->title,
                'slug'         => $form->slug ?? null,
                'is_category'  => $form->is_category,
                'has_children' => $hasChildren,
                'status'       => $status,
            ];

            if ($hasChildren) {
                $newAncestors = array_merge($ancestors, [$form->id]);
                $childRows    = $this->buildMatrixRows($form->children, $months, $currentYear, $submissions, $level + 1, $form->id, $newAncestors);
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

<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Concerns\MatrixSummary;

class GuestMatrixController extends Controller
{
    use MatrixSummary;

    /**
     * Cache key for the shared live submissions (all visitors share same data).
     * We use a global key (not per-session) so one fetch benefits everyone.
     */
    private const CACHE_KEY  = 'global_live_submissions';
    private const CACHE_TTL  = 10; // minutes — fresh enough, fast enough

    /**
     * Live matrix — the single public page of the app.
     * Serves from cache if available to avoid Google API timeout on page load.
     * If no cache exists yet, fetches synchronously with a 25s time limit.
     */
    public function live(\App\Services\GoogleFormsSyncService $syncService)
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

        // ---------- Fetch live submissions (cached) ----------
        $liveSubmissions = $this->fetchLiveSubmissions($syncService);

        // Build session cache for submission-detail modal
        Cache::put(
            'live_submissions_' . session()->getId(),
            $liveSubmissions->values()->toArray(),
            now()->addMinutes(self::CACHE_TTL + 5)
        );

        $filledLookup = $this->buildFilledLookupFromArrays($liveSubmissions, $currentYear);
        $matrix       = $this->buildMatrixRows($rootForms, $months, $currentYear, $filledLookup);
        $summary      = $this->buildMonthlySummary($months, $currentYear, $liveSubmissions);

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
        ]);
    }

    /**
     * AJAX polling endpoint — called by the auto-refresh toggle.
     * Always fetches fresh data from Google and updates the global cache.
     * Returns the new matrixStatusMap so Alpine can update cells reactively.
     */
    public function livePoll(\App\Services\GoogleFormsSyncService $syncService): \Illuminate\Http\JsonResponse
    {
        $currentYear  = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;

        // Force a fresh fetch and update global cache
        $liveSubmissions = $this->fetchFreshSubmissions($syncService);

        // Update session cache for submission-detail modal
        Cache::put(
            'live_submissions_' . session()->getId(),
            $liveSubmissions->values()->toArray(),
            now()->addMinutes(self::CACHE_TTL + 5)
        );

        $months = [];
        for ($i = 1; $i <= $currentMonth; $i++) {
            $months[$i] = Carbon::create()->month($i)->translatedFormat('F');
        }

        $rootForms = Form::with('children.children.children.children')
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();

        $filledLookup = $this->buildFilledLookupFromArrays($liveSubmissions, $currentYear);
        $matrix       = $this->buildMatrixRows($rootForms, $months, $currentYear, $filledLookup);

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
     * Return submission detail data for a specific form+month+year as JSON.
     */
    public function submissionDetail(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $formId = $request->integer('form_id');
        $month  = $request->integer('month');
        $year   = $request->integer('year');

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

        // Data stored as plain PHP array — safe for file cache serialization
        $liveData = Cache::get('live_submissions_' . session()->getId());

        if (!$liveData) {
            return response()->json(['error' => 'Data live sudah kedaluwarsa. Silakan muat ulang halaman.'], 410);
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

        return response()->json([
            'form_title'  => $form->title,
            'submissions' => $submissions,
        ]);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Fetch live submissions, preferring the cache.
     * If cache is empty, falls back to fetchFreshSubmissions().
     */
    private function fetchLiveSubmissions(\App\Services\GoogleFormsSyncService $syncService): \Illuminate\Support\Collection
    {
        $cached = Cache::get(self::CACHE_KEY);
        if ($cached !== null) {
            return collect($cached);
        }

        return $this->fetchFreshSubmissions($syncService);
    }

    /**
     * Fetch fresh data from all registered Google Forms and update the global cache.
     * Wrapped in a try-catch so errors never crash the page.
     */
    private function fetchFreshSubmissions(\App\Services\GoogleFormsSyncService $syncService): \Illuminate\Support\Collection
    {
        try {
            // All Google Form IDs registered for this matrix.
            // Add more entries here when new Google Forms are connected.
            $googleForms = [
                ['id' => '1bJXgi5GMANXfesZAXkn77f4KFugpXCwLN7nLkEIKQjk', 'slug' => 'mobile-ladder'],
            ];

            $all = collect();
            foreach ($googleForms as $gf) {
                $responses = $syncService->getLiveResponses($gf['id'], $gf['slug']);
                $all = $all->merge($responses);
            }

            // Store in global shared cache so every visitor benefits
            Cache::put(self::CACHE_KEY, $all->values()->toArray(), now()->addMinutes(self::CACHE_TTL));

            return $all;
        } catch (\Exception $e) {
            Log::error('GuestMatrixController fetchFreshSubmissions: ' . $e->getMessage());
            // Return whatever is in cache (even stale), or empty collection
            $stale = Cache::get(self::CACHE_KEY);
            return $stale ? collect($stale) : collect();
        }
    }

    /**
     * Build a nested lookup from plain-array live submissions.
     * Result: $lookup[form_id][month] = true
     */
    private function buildFilledLookupFromArrays(\Illuminate\Support\Collection $submissions, int $year): array
    {
        $lookup = [];
        foreach ($submissions as $sub) {
            $fid  = $sub['form_id'] ?? null;
            $date = $sub['date'] ?? null;
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
                $childRows    = $this->buildMatrixRows($form->children, $months, $currentYear, $filledLookup, $level + 1, $form->id, $newAncestors);
                $rows         = array_merge($rows, $childRows);
            }
        }

        return $rows;
    }

    /**
     * Get IDs of all leaf descendants (forms with no children).
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

<?php

namespace App\Http\Controllers;

use App\Concerns\MatrixSummary;
use App\Models\Form;
use App\Services\GoogleFormsSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class GuestMatrixController extends Controller
{
    use MatrixSummary;

    /**
     * Cache key for the shared live submissions (all visitors share same data).
     * We use a global key (not per-session) so one fetch benefits everyone.
     */
    private const CACHE_KEY = 'global_live_submissions';

    private const CACHE_TTL = 10080; // 7 days — implement stale-while-revalidate pattern

    /**
     * Live matrix — the single public page of the app.
     * Serves from cache if available to avoid Google API timeout on page load.
     * If no cache exists yet, fetches synchronously with a 25s time limit.
     */
    public function live(Request $request, GoogleFormsSyncService $syncService)
    {
        $currentYear = $request->integer('year', Carbon::now()->year);
        $currentMonth = ($currentYear == Carbon::now()->year) ? Carbon::now()->month : 12;

        $start = Carbon::now()->startOfYear();
        $end = Carbon::now()->endOfYear();
        $months = $this->generateMonthRange($start, $end);

        $monthsList = [];
        foreach ($months as $k => $v) {
            $monthsList[] = ['key' => $k, 'name' => $v];
        }

        $rootForms = Form::with('children.children.children.children')
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();

        // ---------- Fetch live submissions (cached) ----------
        $liveSubmissions = $this->fetchLiveSubmissions($syncService);

        // Filter submissions to the active date range (current year by default)
        $filteredSubmissions = $liveSubmissions->filter(function ($sub) use ($start, $end) {
            if (empty($sub['date'])) {
                return false;
            }
            return Carbon::parse($sub['date'])->between($start, $end);
        });

        // Build session cache for submission-detail modal (contains all to avoid re-fetch)
        Cache::put(
            'live_submissions_'.session()->getId(),
            $liveSubmissions->values()->toArray(),
            now()->addMinutes(self::CACHE_TTL + 5)
        );

        $filledLookup = $this->buildFilledLookupFromArrays($filteredSubmissions);
        $matrix = $this->buildMatrixRows($rootForms, $months, $filledLookup);
        $summary = $this->buildMonthlySummary($months, $filteredSubmissions, []);

        $annualSummary = $this->buildAnnualSummaryFromFiltered($filteredSubmissions, []);

        $matrixStatusMap = [];
        foreach ($matrix as $row) {
            $matrixStatusMap[$row['id']] = $row['status'];
        }

        return view('guest-matrix', [
            'months' => $months,
            'monthsList' => $monthsList,
            'matrix' => $matrix,
            'matrixStatusMap' => $matrixStatusMap,
            'summary' => $summary,
            'annualSummary' => $annualSummary,
        ]);
    }

    public function livePollInit(Request $request)
    {
        try {
            Artisan::call('forms:sync-structure');
        } catch (\Exception $e) {
            Log::error('GuestMatrix: failed to sync forms structure: '.$e->getMessage());
        }

        $googleForms = [];
        $configPath = storage_path('app/pandu_config.json');
        if (File::exists($configPath)) {
            $config = json_decode(File::get($configPath), true);
            $googleForms = $config['forms'] ?? [];
        }

        return response()->json([
            'forms' => $googleForms
        ]);
    }

    public function livePollChunk(Request $request, GoogleFormsSyncService $syncService): JsonResponse
    {
        set_time_limit(0);
        $slug = $request->input('slug');
        $googleFormId = $request->input('id');

        try {
            $responses = $syncService->getLiveResponses($googleFormId, $slug);
            Cache::put("live_submissions_".session()->getId()."_{$slug}", $responses->values()->toArray(), now()->addMinutes(10));
            return response()->json(['success' => true, 'count' => $responses->count()]);
        } catch (\Exception $e) {
            Log::error("GuestMatrix chunk error for {$slug}: ".$e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function livePollFinish(Request $request): JsonResponse
    {
        $fetchedSlugs = $request->input('fetched_slugs', []);
        $hiddenIds = $request->input('hidden_ids', []);

        $oldCache = Cache::get(self::CACHE_KEY, []);
        
        $fetchedRootForms = Form::whereIn('slug', $fetchedSlugs)->get();
        $fetchedLeafIds = [];
        foreach ($fetchedRootForms as $rf) {
            if ($rf->children->count() === 0) {
                $fetchedLeafIds[] = $rf->id;
            } else {
                $fetchedLeafIds = array_merge($fetchedLeafIds, $this->getLeafDescendantIds($rf));
            }
        }

        $newSubmissions = collect($oldCache)->filter(function($s) use ($fetchedLeafIds) {
            return !in_array($s['form_id'], $fetchedLeafIds);
        });

        $sessionId = session()->getId();
        foreach ($fetchedSlugs as $slug) {
            $partial = Cache::pull("live_submissions_{$sessionId}_{$slug}");
            if ($partial) {
                $newSubmissions = $newSubmissions->merge($partial);
            }
        }

        Cache::put(self::CACHE_KEY, $newSubmissions->values()->toArray(), now()->addMinutes(self::CACHE_TTL));
        Cache::put('live_submissions_'.$sessionId, $newSubmissions->values()->toArray(), now()->addMinutes(self::CACHE_TTL + 5));

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        
        $start = $fromDate ? Carbon::parse($fromDate)->startOfDay() : Carbon::now()->startOfYear();
        $end = $toDate ? Carbon::parse($toDate)->endOfDay() : Carbon::now()->endOfDay();

        $filteredSubmissions = $newSubmissions->filter(function($s) use ($start, $end) {
            if (empty($s['date'])) return false;
            return Carbon::parse($s['date'])->between($start, $end);
        });
        
        $months = $this->generateMonthRange($start, $end);
        
        $monthsList = [];
        foreach ($months as $k => $v) {
            $monthsList[] = ['key' => $k, 'name' => $v];
        }

        $rootForms = Form::with('children.children.children.children')->whereNull('parent_id')->orderBy('order')->get();

        $filledLookup = $this->buildFilledLookupFromArrays($filteredSubmissions);
        $matrix = $this->buildMatrixRows($rootForms, $months, $filledLookup);
        $summary = $this->buildMonthlySummary($months, $filteredSubmissions, $hiddenIds);
        $annualSummary = $this->buildAnnualSummaryFromFiltered($filteredSubmissions, $hiddenIds);

        $matrixStatusMap = [];
        foreach ($matrix as $row) {
            $matrixStatusMap[$row['id']] = $row['status'];
        }

        return response()->json([
            'matrixStatusMap' => $matrixStatusMap,
            'annualSummary' => $annualSummary,
            'summary' => $summary,
            'monthsList' => $monthsList,
            'refreshedAt' => now()->translatedFormat('H:i:s'),
        ]);
    }

    /**
     * Return submission detail data for a specific form+month+year as JSON.
     */
    public function submissionDetail(Request $request): JsonResponse
    {
        $formId = $request->integer('form_id');
        $monthKey = $request->input('month_key'); // Format: YYYY-MM

        $form = Form::find($formId);
        if (! $form) {
            return response()->json(['error' => 'Form tidak ditemukan.'], 404);
        }

        $parseNotes = function (?string $rawNotes) {
            if (! $rawNotes) {
                return ['notes' => null, 'failures' => []];
            }
            $notes = $rawNotes;
            $failures = [];
            foreach (['[IMPORTED FAILURES]', '[API FAILURES]'] as $marker) {
                if (str_contains($notes, $marker)) {
                    preg_match('/\[(?:IMPORTED|API) FAILURES\]:\s*(.*)/', $notes, $matches);
                    if (! empty($matches[1])) {
                        $failures = array_map('trim', explode(',', $matches[1]));
                    }
                    $notes = trim(preg_replace('/\[(?:IMPORTED|API) FAILURES\].*/', '', $notes));
                }
            }

            return [
                'notes' => $notes ?: null,
                'failures' => $failures,
            ];
        };

        // Try session cache first, then fall back to global cache
        $liveData = Cache::get(self::CACHE_KEY, []);

        if (! $liveData) {
            return response()->json([
                'form_title' => $form->title,
                'submissions' => [],
            ]);
        }

        $submissions = collect($liveData)
            ->filter(fn ($s) => ($s['form_id'] ?? null) == $formId)
            ->filter(fn ($s) => Carbon::parse($s['date'] ?? '')->format('Y-m') === $monthKey)
            ->sortBy('date')
            ->values()
            ->map(function ($sub) use ($parseNotes) {
                $parsed = $parseNotes($sub['notes'] ?? null);

                return [
                    'id' => $sub['id'] ?? '-',
                    'date' => Carbon::parse($sub['date'] ?? '')->translatedFormat('d F Y'),
                    'user_name' => $sub['user_name'] ?? '-',
                    'notes' => $parsed['notes'],
                    'failures' => $parsed['failures'],
                    'answers' => $sub['answers'] ?? [],
                ];
            });

        return response()->json([
            'form_title' => $form->title,
            'submissions' => $submissions,
        ]);
    }

    public function exportExcel(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (! $startDate || ! $endDate) {
            return back()->with('error', 'Tanggal awal dan akhir harus diisi.');
        }

        // Try session cache first, then global
        $liveData = Cache::get(self::CACHE_KEY, []);

        if (! $liveData) {
            return back()->with('error', 'Data live belum dimuat. Silakan klik Muat Data Live terlebih dahulu.');
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $submissions = collect($liveData)->filter(function ($sub) use ($start, $end) {
            if (empty($sub['date'])) {
                return false;
            }
            $date = Carbon::parse($sub['date']);

            return $date->between($start, $end);
        })->sortBy('date')->values();

        $forms = Form::pluck('title', 'id');

        return response(view('export-excel', [
            'submissions' => $submissions,
            'forms' => $forms,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]))
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="Checklist_Export_'.$startDate.'_to_'.$endDate.'.xls"');
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Fetch live submissions from cache ONLY.
     * On initial page load we never hit Google API to avoid timeout.
     * The user can trigger a fresh fetch via the auto-refresh polling endpoint.
     */
    private function fetchLiveSubmissions(GoogleFormsSyncService $syncService): Collection
    {
        $cached = Cache::get(self::CACHE_KEY);
        if ($cached !== null) {
            return collect($cached);
        }

        // Cache miss on first load — return empty so page renders instantly.
        // User should click "Refresh Sekarang" to pull live data.
        return collect();
    }

    // Old fetchFreshSubmissions has been replaced by livePollChunk / livePollFinish

    /**
     * Build a nested lookup from plain-array live submissions.
     * Result: $lookup[form_id][month] = true
     */
    private function buildFilledLookupFromArrays(Collection $submissions): array
    {
        $lookup = [];
        foreach ($submissions as $sub) {
            $fid = $sub['form_id'] ?? null;
            $date = $sub['date'] ?? null;
            if (! $fid || ! $date) {
                continue;
            }

            $monthKey = Carbon::parse($date)->format('Y-m');
            $lookup[$fid][$monthKey] = true;
        }

        return $lookup;
    }

    /**
     * Generate an array of YYYY-MM keys between two dates.
     */
    private function generateMonthRange(Carbon $start, Carbon $end): array
    {
        $months = [];
        $current = $start->copy()->startOfMonth();
        $endMonth = $end->copy()->startOfMonth();

        while ($current->lte($endMonth)) {
            $key = $current->format('Y-m');
            $months[$key] = $current->translatedFormat('F Y');
            $current->addMonth();
        }

        return $months;
    }

    /**
     * Build flattened matrix rows recursively with 3-state status.
     */
    private function buildMatrixRows(
        $forms,
        array $months,
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
            foreach ($months as $monthKey => $monthName) {
                if ($hasChildren) {
                    $filledCount = 0;
                    foreach ($leafIds as $lid) {
                        if (isset($filledLookup[$lid][$monthKey])) {
                            $filledCount++;
                        }
                    }
                    $totalLeaves = count($leafIds);

                    if ($filledCount === 0) {
                        $status[$monthKey] = 'none';
                    } elseif ($filledCount >= $totalLeaves) {
                        $status[$monthKey] = 'full';
                    } else {
                        $status[$monthKey] = 'partial';
                    }
                } else {
                    $status[$monthKey] = isset($filledLookup[$form->id][$monthKey]) ? 'full' : 'none';
                }
            }

            $rows[] = [
                'id' => $form->id,
                'parent_id' => $parentId,
                'ancestors' => $ancestors,
                'level' => $level,
                'title' => $form->title,
                'slug' => $form->slug ?? null,
                'is_category' => $form->is_category,
                'has_children' => $hasChildren,
                'status' => $status,
            ];

            if ($hasChildren) {
                $newAncestors = array_merge($ancestors, [$form->id]);
                $childRows = $this->buildMatrixRows($form->children, $months, $filledLookup, $level + 1, $form->id, $newAncestors);
                $rows = array_merge($rows, $childRows);
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

    /**
     * Filter cached submissions by date range and return updated matrix + summary as JSON.
     * Used by the client-side date range filter (no Google API call, reads from cache only).
     */
    public function viewByDateRange(Request $request): JsonResponse
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $hiddenIds = json_decode($request->input('hidden_ids', '[]'), true) ?? [];

        $liveData = Cache::get(self::CACHE_KEY, []);

        if (! $liveData) {
            $empty = ['total' => 0, 'filled_count' => 0, 'unfilled_count' => 0, 'good_count' => 0, 'not_good_count' => 0,
                'filled_percent' => 0, 'unfilled_percent' => 0, 'good_percent' => 0, 'not_good_percent' => 0];

            return response()->json(['matrixStatusMap' => [], 'annualSummary' => $empty, 'summary' => []]);
        }

        $start = $fromDate ? Carbon::parse($fromDate)->startOfDay() : Carbon::now()->startOfYear();
        $end = $toDate ? Carbon::parse($toDate)->endOfDay() : Carbon::now()->endOfDay();

        // Filter submissions to the requested date range
        $filtered = collect($liveData)->filter(function ($sub) use ($start, $end) {
            if (empty($sub['date'])) {
                return false;
            }

            return Carbon::parse($sub['date'])->between($start, $end);
        });

        // Generate dynamic month columns from start to end date
        $months = $this->generateMonthRange($start, $end);

        $monthsList = [];
        foreach ($months as $k => $v) {
            $monthsList[] = ['key' => $k, 'name' => $v];
        }

        $rootForms = Form::with('children.children.children.children')
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();

        $filledLookup = $this->buildFilledLookupFromArrays($filtered);
        $matrix = $this->buildMatrixRows($rootForms, $months, $filledLookup);
        $summary = $this->buildMonthlySummary($months, $filtered, $hiddenIds);
        $annualSummary = $this->buildAnnualSummaryFromFiltered($filtered, $hiddenIds);

        $matrixStatusMap = [];
        foreach ($matrix as $row) {
            $matrixStatusMap[$row['id']] = $row['status'];
        }

        return response()->json([
            'matrixStatusMap' => $matrixStatusMap,
            'annualSummary' => $annualSummary,
            'summary' => $summary,
            'monthsList' => $monthsList,
        ]);
    }

    /**
     * Compute aggregate summary (total/filled/unfilled/good/not_good)
     * from a pre-filtered collection of submission arrays.
     *
     * A "form" is considered filled if at least one submission exists in the filtered set.
     * "Good" means no failure markers; "Not Good" means failures were recorded.
     */
    private function buildAnnualSummaryFromFiltered(Collection $filtered, array $hiddenIds = []): array
    {
        // Work only with leaf forms (no children)
        $leafQuery = Form::whereDoesntHave('children');
        if (!empty($hiddenIds)) {
            $leafQuery->whereNotIn('id', $hiddenIds);
        }
        $leafIds = $leafQuery->pluck('id')->all();
        $total = count($leafIds);

        // Group filtered submissions by form_id, only for leaf forms
        $byForm = $filtered
            ->filter(fn ($s) => in_array($s['form_id'] ?? null, $leafIds))
            ->groupBy(fn ($s) => $s['form_id']);

        $filledCount = $byForm->count();
        $goodCount = 0;
        $notGoodCount = 0;

        foreach ($byForm as $subs) {
            // Pick the most recent submission per form within the date range
            $latest = collect($subs)->sortByDesc('date')->first();
            $rawNotes = $latest['notes'] ?? '';
            $hasFailures = str_contains($rawNotes, '[IMPORTED FAILURES]') ||
                           str_contains($rawNotes, '[API FAILURES]');
            if ($hasFailures) {
                $notGoodCount++;
            } else {
                $goodCount++;
            }
        }

        $unfilledCount = max(0, $total - $filledCount);
        $filledPercent = $total > 0 ? round(($filledCount / $total) * 100) : 0;
        $unfilledPercent = $total > 0 ? 100 - $filledPercent : 0;
        $goodPercent = $filledCount > 0 ? round(($goodCount / $filledCount) * 100) : 0;
        $notGoodPercent = $filledCount > 0 ? 100 - $goodPercent : 0;

        return [
            'total' => $total,
            'filled_count' => $filledCount,
            'unfilled_count' => $unfilledCount,
            'filled_percent' => $filledPercent,
            'unfilled_percent' => $unfilledPercent,
            'good_count' => $goodCount,
            'not_good_count' => $notGoodCount,
            'good_percent' => $goodPercent,
            'not_good_percent' => $notGoodPercent,
        ];
    }
}

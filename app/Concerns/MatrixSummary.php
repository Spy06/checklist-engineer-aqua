<?php

namespace App\Concerns;

use App\Models\Form;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

trait MatrixSummary
{
    /**
     * Menghitung statistik bulanan untuk matriks.
     */
    public function buildMonthlySummary(array $months, Collection $submissions, array $hiddenIds = []): array
    {
        $summary = [];
        $leafQuery = Form::with('parent.parent.parent')->whereDoesntHave('children');
        if (!empty($hiddenIds)) {
            $leafQuery->whereNotIn('id', $hiddenIds);
        }
        $leaves = $leafQuery->get();

        if ($submissions) {
            $allSubmissions = $submissions->filter(fn ($s) => in_array(
                is_array($s) ? ($s['form_id'] ?? null) : ($s->form_id ?? null),
                $leaves->pluck('id')->all()
            ));
        } else {
            $allSubmissions = collect();
        }

        // Pre-compute form paths
        $paths = [];
        foreach ($leaves as $leaf) {
            $paths[$leaf->id] = $this->getFormPath($leaf);
        }

        // Helper: works for both Eloquent models and plain arrays
        $getField = fn ($s, $key) => is_array($s) ? ($s[$key] ?? null) : ($s->$key ?? null);

        // Pre-group ALL submissions by form_id ONCE — avoids re-filtering on every month iteration
        $submissionsByFormId = $allSubmissions->groupBy(fn ($s) => $getField($s, 'form_id'));

        foreach ($months as $monthKey => $monthName) {
            // For each month, filter the already-grouped data by YYYY-MM key
            $monthSubs = $submissionsByFormId
                ->map(fn ($subs) => $subs->filter(
                    fn ($s) => Carbon::parse($getField($s, 'date'))->format('Y-m') === $monthKey
                ))
                ->filter(fn ($subs) => $subs->isNotEmpty());

            $unfilledForms = [];
            $goodForms = [];
            $notGoodForms = [];

            foreach ($leaves as $leaf) {
                $path = $paths[$leaf->id];

                if (! isset($monthSubs[$leaf->id])) {
                    $unfilledForms[] = [
                        'id' => $leaf->id,
                        'title' => $leaf->title,
                        'path' => $path,
                    ];
                } else {
                    $subs = $monthSubs[$leaf->id];
                    // We check the latest submission for the month
                    $latestSub = $subs->sortByDesc(fn ($s) => $getField($s, 'date'))->first();

                    $options = is_array($leaf->options) ? $leaf->options : [];
                    $answers = $getField($latestSub, 'answers');
                    $answers = is_array($answers) ? $answers : [];
                    $notes = $getField($latestSub, 'notes') ?? '';

                    $standardParts = [];
                    $nonStandardParts = [];

                    // Jika ada [IMPORTED FAILURES] dalam notes
                    if (str_contains($notes, '[IMPORTED FAILURES]')) {
                        preg_match('/\[IMPORTED FAILURES\]:\s*(.*)/', $notes, $matches);
                        $failures = [];
                        if (! empty($matches[1])) {
                            $failures = explode(', ', $matches[1]);
                        }

                        foreach ($options as $opt) {
                            $isFail = false;
                            foreach ($failures as $f) {
                                if (str_contains($f, $opt)) {
                                    $isFail = true;
                                    break;
                                }
                            }
                            if ($isFail) {
                                $nonStandardParts[] = $opt;
                            } else {
                                $standardParts[] = $opt;
                            }
                        }
                        if (empty($nonStandardParts) && ! empty($failures)) {
                            $nonStandardParts = $failures;
                        }
                    } else {
                        // Standard flow
                        foreach ($options as $opt) {
                            if (in_array($opt, $answers)) {
                                $standardParts[] = $opt;
                            } else {
                                $nonStandardParts[] = $opt;
                            }
                        }
                    }

                    $isGood = empty($nonStandardParts);

                    $formData = [
                        'id' => $leaf->id,
                        'title' => $leaf->title,
                        'path' => $path,
                        'standard' => $standardParts,
                        'non_standard' => $nonStandardParts,
                        'notes' => $notes,
                    ];

                    if ($isGood) {
                        $goodForms[] = $formData;
                    } else {
                        $notGoodForms[] = $formData;
                    }
                }
            }

            $total = count($leaves);
            $filled = $total - count($unfilledForms);

            $filledPercent = $total > 0 ? round(($filled / $total) * 100) : 0;
            $unfilledPercent = $total > 0 ? 100 - $filledPercent : 0;

            $goodPercent = $filled > 0 ? round((count($goodForms) / $filled) * 100) : 0;
            $notGoodPercent = $filled > 0 ? 100 - $goodPercent : 0;

            $summary[$monthKey] = [
                'total' => $total,
                'filled_count' => $filled,
                'unfilled_count' => count($unfilledForms),
                'filled_percent' => $filledPercent,
                'unfilled_percent' => $unfilledPercent,
                'unfilled_forms' => $unfilledForms,

                'good_count' => count($goodForms),
                'not_good_count' => count($notGoodForms),
                'good_percent' => $goodPercent,
                'not_good_percent' => $notGoodPercent,
                'good_forms' => $goodForms,
                'not_good_forms' => $notGoodForms,
            ];
        }

        return $summary;
    }

    /**
     * Build breadcrumb path for a leaf form.
     * Includes circular-reference protection to prevent infinite loops.
     */
    private function getFormPath($form): string
    {
        $path = [];
        $current = $form->parent;
        $visitedIds = [];
        $maxDepth = 10;

        while ($current && count($path) < $maxDepth) {
            // Guard: stop if we detect a circular parent reference in the DB
            if (in_array($current->id, $visitedIds)) {
                break;
            }
            $visitedIds[] = $current->id;
            array_unshift($path, $current->title);
            $current = $current->parent;
        }

        return empty($path) ? '-' : implode(' > ', $path);
    }
}

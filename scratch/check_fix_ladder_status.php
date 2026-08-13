<?php

use App\Http\Controllers\GuestMatrixController;
use App\Models\Form;
use App\Services\GoogleFormsSyncService;
use Illuminate\Contracts\Console\Kernel;
use Carbon\Carbon;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$controller = app(GuestMatrixController::class);
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('fetchFreshSubmissions');
$method->setAccessible(true);

echo "Fetching fresh submissions (might take a moment since it only runs what's in config)...\n";
// Actually, let's fetch responses for all forms in config, but we can do it faster by using the cached one if available, or just fetch
$submissions = $method->invoke($controller, app(GoogleFormsSyncService::class));
echo "Total submissions fetched: " . $submissions->count() . "\n";

$lookupMethod = $reflection->getMethod('buildFilledLookupFromArrays');
$lookupMethod->setAccessible(true);
$filledLookup = $lookupMethod->invoke($controller, $submissions);

$fixLadder = Form::where('slug', 'fix-ladder')->first();
$months = ['2026-08' => 'August 2026'];

$getLeafDescendantIds = $reflection->getMethod('getLeafDescendantIds');
$getLeafDescendantIds->setAccessible(true);

$buildMatrixRows = $reflection->getMethod('buildMatrixRows');
$buildMatrixRows->setAccessible(true);

$matrix = $buildMatrixRows->invoke($controller, [$fixLadder], $months, $filledLookup);

function printRows($rows) {
    foreach ($rows as $row) {
        $indent = str_repeat("  ", $row['level']);
        $status = $row['status']['2026-08'] ?? 'none';
        echo "{$indent}ID: {$row['id']} | Title: {$row['title']} | Status (2026-08): {$status}\n";
        
        // Let's print details if category
        if ($row['is_category']) {
            $form = Form::find($row['id']);
            $children = Form::where('parent_id', $form->id)->orderBy('order')->get();
            // Let's count leaves
            $leafIds = [];
            $getLeafIds = function($f) use (&$getLeafIds, &$leafIds) {
                if ($f->children->count() === 0) {
                    $leafIds[] = $f->id;
                } else {
                    foreach ($f->children as $child) {
                        $getLeafIds($child);
                    }
                }
            };
            $getLeafIds($form);
            $total = count($leafIds);
            
            // Check how many are filled
            $filled = 0;
            $filledNames = [];
            foreach ($leafIds as $lid) {
                global $filledLookup;
                if (isset($filledLookup[$lid]['2026-08'])) {
                    $filled++;
                    $filledNames[] = Form::find($lid)->title;
                }
            }
            echo "{$indent}  -> Leaves filled: {$filled} / {$total}\n";
            if ($filled > 0) {
                echo "{$indent}  -> Filled leaves: " . implode(', ', $filledNames) . "\n";
            }
        }
    }
}

// We need to flatten the recursive tree returned by buildMatrixRows if we want to print it
function flattenMatrix($matrix) {
    $flat = [];
    foreach ($matrix as $item) {
        // buildMatrixRows returns child rows inline, or wait:
        // Let's see if child rows are returned as part of the array or if they are separate
        // Let's check GuestMatrixController lines 448-450:
        // $childRows = $this->buildMatrixRows($form->children, $months, $filledLookup, $level + 1, $form->id, $newAncestors);
        // $rows = array_merge($rows, $childRows);
        // Yes, array_merge is used, so it's already flattened!
        $flat[] = $item;
    }
    return $flat;
}

printRows(flattenMatrix($matrix));

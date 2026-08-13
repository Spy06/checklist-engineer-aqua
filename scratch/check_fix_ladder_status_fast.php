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

echo "Fetching responses for Fix Ladder only...\n";
$submissions = app(GoogleFormsSyncService::class)->getLiveResponses("1WHfZggFDdRvZ-M-RyAZsZy0d7UVdL3y7iUvxjMbSIXA", 'fix-ladder');
echo "Total submissions fetched for Fix Ladder: " . $submissions->count() . "\n";

$lookupMethod = $reflection->getMethod('buildFilledLookupFromArrays');
$lookupMethod->setAccessible(true);
$filledLookup = $lookupMethod->invoke($controller, $submissions);

$fixLadder = Form::where('slug', 'fix-ladder')->first();
$months = ['2026-08' => 'August 2026'];

$buildMatrixRows = $reflection->getMethod('buildMatrixRows');
$buildMatrixRows->setAccessible(true);

$matrix = $buildMatrixRows->invoke($controller, [$fixLadder], $months, $filledLookup);

function printRows($rows, $filledLookup) {
    foreach ($rows as $row) {
        $indent = str_repeat("  ", $row['level']);
        $status = $row['status']['2026-08'] ?? 'none';
        echo "{$indent}ID: {$row['id']} | Title: {$row['title']} | Status (2026-08): {$status}\n";
        
        // Let's print details if category
        if ($row['is_category']) {
            $form = Form::find($row['id']);
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

// buildMatrixRows returns child rows inline (flat)
printRows($matrix, $filledLookup);

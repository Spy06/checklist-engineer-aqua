<?php

use App\Http\Controllers\GuestMatrixController;
use App\Models\Form;
use App\Services\GoogleFormsSyncService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$controller = app(GuestMatrixController::class);
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('fetchFreshSubmissions');
$method->setAccessible(true);
$submissions = $method->invoke($controller, app(GoogleFormsSyncService::class));

$lookupMethod = $reflection->getMethod('buildFilledLookupFromArrays');
$lookupMethod->setAccessible(true);
$filledLookup = $lookupMethod->invoke($controller, $submissions);

$forms = Form::orderBy('order')->get();
$statusMethod = $reflection->getMethod('buildStatusMap');
$statusMethod->setAccessible(true);
$matrixStatusMap = $statusMethod->invoke($controller, $forms, $filledLookup, ['2026-08', '2026-07']);

$fle37Id = Form::where('title', 'FLE 37 OFFICE')->first()->id;
echo "FLE 37 OFFICE (ID: $fle37Id) Status July: ".($matrixStatusMap[$fle37Id]['2026-07'] ?? 'none')."\n";
echo "FLE 37 OFFICE (ID: $fle37Id) Status August: ".($matrixStatusMap[$fle37Id]['2026-08'] ?? 'none')."\n";

$fle48Id = Form::where('title', 'FLE 48 INFEED PALLET  CARTON FEEDER A')->first();
if ($fle48Id) {
    echo "FLE 48 (ID: {$fle48Id->id}) Status August: ".($matrixStatusMap[$fle48Id->id]['2026-08'] ?? 'none')."\n";
} else {
    echo "FLE 48 not found in DB\n";
}

$fixLadderId = Form::where('slug', 'fix-ladder')->first()->id;
echo "Fix Ladder (ID: $fixLadderId) Status July: ".($matrixStatusMap[$fixLadderId]['2026-07'] ?? 'none')."\n";
echo "Fix Ladder (ID: $fixLadderId) Status August: ".($matrixStatusMap[$fixLadderId]['2026-08'] ?? 'none')."\n";

<?php

use App\Services\GoogleFormsSyncService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$syncService = app(GoogleFormsSyncService::class);
$googleFormId = "1AJ-2475yjGAVJmVtwWJaFmcdEL0gBmFmfa4pst96zZE"; // Work at Height

try {
    echo "Fetching responses for Work at Height Google Form...\n";
    $responses = $syncService->getLiveResponses($googleFormId, 'work-at-height');
    echo "Total responses: " . $responses->count() . "\n";
    if ($responses->count() > 0) {
        $dates = $responses->pluck('date')->unique()->sort()->values()->toArray();
        echo "Min date: " . min($dates) . " | Max date: " . max($dates) . "\n\n";
        echo "--- Example Response Row ---\n";
        echo json_encode($responses->first(), JSON_PRETTY_PRINT) . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

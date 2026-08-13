<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\GoogleFormsSyncService;
use Illuminate\Support\Facades\Cache;

$syncService = app(GoogleFormsSyncService::class);
$googleFormId = "1SV4vR2g-STWWSEPgSp0yjy774ai0AtEjgI1wplvFNcU"; // Lifter
$slug = "mobile-elevation-lifter";

echo "Running test sync for: {$slug} (ID: {$googleFormId})\n";

try {
    // 1. Fetch responses
    $responses = $syncService->getLiveResponses($googleFormId, $slug);
    echo "Successfully fetched " . $responses->count() . " responses!\n";
    
    if ($responses->count() > 0) {
        $first = $responses->first();
        echo "First response date: " . $first['date'] . " | Form ID: " . $first['form_id'] . " | User: " . $first['user_name'] . "\n";
    }

    // 2. Try writing to Cache (file driver now)
    echo "Attempting to write to Cache under key 'test_lifter_cache'...\n";
    Cache::put('test_lifter_cache', $responses->values()->toArray(), 600);
    echo "Cache write SUCCESSFUL!\n";

    // 3. Read back
    $cached = Cache::get('test_lifter_cache');
    echo "Read from Cache: " . count($cached) . " items.\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

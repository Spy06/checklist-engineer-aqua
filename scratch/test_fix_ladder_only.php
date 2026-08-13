<?php

use App\Services\GoogleFormsSyncService;
use Illuminate\Contracts\Console\Kernel;
use Carbon\Carbon;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$syncService = app(GoogleFormsSyncService::class);
$googleFormId = "1WHfZggFDdRvZ-M-RyAZsZy0d7UVdL3y7iUvxjMbSIXA"; // Fix Ladder

try {
    echo "Fetching responses for Fix Ladder...\n";
    $responses = $syncService->getLiveResponses($googleFormId, 'fix-ladder');
    echo "Success! Fetched " . $responses->count() . " responses.\n";
    
    if ($responses->count() > 0) {
        $dates = $responses->pluck('date')->unique()->sort()->values()->toArray();
        echo "Total unique dates: " . count($dates) . "\n";
        echo "Min date in responses: " . min($dates) . "\n";
        echo "Max date in responses: " . max($dates) . "\n";
        
        $today = Carbon::now()->format('Y-m-d');
        echo "Today is: " . $today . "\n";
        
        $todayResponses = $responses->filter(fn($r) => $r['date'] === $today);
        echo "Responses for today (" . $today . "): " . $todayResponses->count() . "\n";
        if ($todayResponses->count() > 0) {
            foreach ($todayResponses as $r) {
                echo " - User: {$r['user_name']}, Form ID: {$r['form_id']}\n";
            }
        }
        
        $augustResponses = $responses->filter(fn($r) => str_starts_with($r['date'], '2026-08'));
        echo "Responses in August 2026: " . $augustResponses->count() . "\n";
        
        // Let's check how many forms are actually mapped to form IDs
        $mappedCount = $responses->filter(fn($r) => !empty($r['form_id']))->count();
        echo "Mapped responses count: " . $mappedCount . "\n";
        
        // Find if there are any unmapped ones (e.g. form_id is null or empty)
        $unmappedCount = $responses->filter(fn($r) => empty($r['form_id']))->count();
        echo "Unmapped responses count: " . $unmappedCount . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

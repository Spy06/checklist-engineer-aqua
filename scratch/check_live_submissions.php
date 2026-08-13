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

echo 'Total live submissions fetched: '.$submissions->count()."\n";

$lookupMethod = $reflection->getMethod('buildFilledLookupFromArrays');
$lookupMethod->setAccessible(true);
$filledLookup = $lookupMethod->invoke($controller, $submissions);

$fixLadder = Form::where('slug', 'fix-ladder')->first();
$leaves = Form::whereNotNull('parent_id')
    ->where('is_category', false)
    ->get();

$fixLadderLeaves = [];
foreach ($leaves as $lf) {
    $ancestor = $lf->parent;
    while ($ancestor) {
        if ($ancestor->id === $fixLadder->id) {
            $fixLadderLeaves[] = $lf;
            break;
        }
        $ancestor = $ancestor->parent;
    }
}

echo 'Total leaf forms under Fix Ladder: '.count($fixLadderLeaves)."\n";

$filledCount = 0;
foreach ($fixLadderLeaves as $lf) {
    if (isset($filledLookup[$lf->id])) {
        echo "Leaf [{$lf->title}] (ID: {$lf->id}) is filled in: ".implode(', ', array_keys($filledLookup[$lf->id]))."\n";
        $filledCount++;
    }
}
echo "Total filled leaves under Fix Ladder: $filledCount\n";

<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\GoogleFormsSyncService;

$syncService = app(GoogleFormsSyncService::class);
$googleFormId = "1BYpbO1V16_Iakso7P0AD6rTXScjnkb2d3zYlwnP6dvE"; // Sodu Gas
$slug = "sodu-gas";

echo "Running test sync for Sodu Gas: {$slug} (ID: {$googleFormId})\n";

try {
    $responses = $syncService->getLiveResponses($googleFormId, $slug);
    echo "Successfully fetched " . $responses->count() . " responses!\n";
    
    // Group by Form ID to see where they are mapped
    $grouped = $responses->groupBy('form_id');
    foreach ($grouped as $formId => $items) {
        $form = App\Models\Form::find($formId);
        $title = $form ? $form->title : "Unknown Form";
        $parent = $form && $form->parent ? $form->parent->title : "No Parent";
        echo "Form ID: {$formId} | Title: {$title} (Parent: {$parent}) | Count: " . $items->count() . " submissions\n";
        
        // Show details of the first submission in this group
        $first = $items->first();
        echo "  First Sub Date: " . $first['date'] . " | User: " . $first['user_name'] . "\n";
        echo "  Notes: " . ($first['notes'] ?? 'None') . "\n";
        echo "  --------------------------------------------------\n";
    }

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

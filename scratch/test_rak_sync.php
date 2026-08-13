<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\GoogleFormsSyncService;
use App\Models\Form;
use App\Models\FormSubmission;

// 1. Manually find the rack-and-pallets form
$form = Form::where('slug', 'rack-and-pallets')->first();
if (!$form) {
    echo "Form 'rack-and-pallets' not found in database!\n";
    exit(1);
}

echo "Target Form - ID: {$form->id} | Title: {$form->title} | Slug: {$form->slug}\n";

$syncService = app(GoogleFormsSyncService::class);
$googleFormId = "1htAepCxxLAnj3Ty1WAf64EbVLyXYhyOsriOfUZ7N4fw"; // Rak Facility

try {
    // We will simulate the sync by running syncForm with 'rack-and-pallets'
    $syncService->syncForm($googleFormId, 'rack-and-pallets');
    
    // Check submissions in DB
    $subs = FormSubmission::where('form_id', $form->id)->get();
    echo "Successfully synced " . $subs->count() . " submissions under rack-and-pallets (ID: {$form->id})!\n";
    if ($subs->count() > 0) {
        $first = $subs->first();
        echo "First Sub Date: {$first->date} | User: " . ($first->answers['user_name'] ?? $first->answers['Nama pemeriksaan'] ?? 'N/A') . "\n";
        echo "Answers Keys: " . implode(', ', array_keys($first->answers)) . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

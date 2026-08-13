<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Form;
use Illuminate\Contracts\Console\Kernel;

$slugsToCheck = [
    'mobile-ladder', 'lifeline', 'barrier-dan-self-door-closer', 'full-body-harness',
    'work-at-height', 'mesin-bor-duduk', 'mesin-press', 'pintu-geser', 'rolling-door',
    'rak-facility', 'skylife-protection', 'lemari-apd-workshop', 'jh-makita-01',
    'lemari-tool-3', 'sodu-gas', 'lemari-tool-4', 'mesin-gerinda-duduk', 'lemari-tool-2',
    'lemari-tool-1', 'mesin-las-argon', 'forklift', 'argon-dan-nitrogen-storage-gas',
    'freon-storage', 'scaffolding', 'fix-ladder', 'jh-krisbow-01',
];

foreach ($slugsToCheck as $slug) {
    $form = Form::where('slug', $slug)->first();
    if ($form) {
        $parent = $form->parent ? $form->parent->title : '(root)';
        echo "✅ {$slug} → [{$form->title}] (parent: {$parent}) is_category=".($form->is_category ? 'YES' : 'NO')."\n";
    } else {
        echo "❌ {$slug} NOT FOUND IN DB\n";
    }
}

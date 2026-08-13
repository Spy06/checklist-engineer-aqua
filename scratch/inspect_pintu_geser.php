<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\GoogleFormsSyncService;

$syncService = app(GoogleFormsSyncService::class);
$ref = new ReflectionClass($syncService);
$prop = $ref->getProperty('formsService');
$prop->setAccessible(true);
$formsService = $prop->getValue($syncService);

$googleFormId = "1vTUjTavOsNVw98R9VNKrts5xEmPi2mfiZIZGRx5gWD8"; // Pintu Geser

try {
    $googleForm = $formsService->forms->get($googleFormId);
    $items = $googleForm->getItems();
    
    echo "--- Google Form Items for Pintu Geser ---\n";
    foreach ($items as $item) {
        $title = $item->getTitle();
        $questionItem = $item->getQuestionItem();
        if ($questionItem) {
            $q = $questionItem->getQuestion();
            $choiceQ = $q->getChoiceQuestion();
            echo "  Question: {$title} (Type: " . ($choiceQ ? "Choice" : "Text") . ")\n";
            if ($choiceQ) {
                $opts = [];
                foreach ($choiceQ->getOptions() as $opt) {
                    $opts[] = $opt->getValue();
                }
                echo "    Options: [" . implode(', ', $opts) . "]\n";
            }
        }
    }
    
    // Also list DB forms under Pintu Geser (slug: pintu-geser)
    $parent = App\Models\Form::where('slug', 'pintu-geser')->first();
    if ($parent) {
        echo "\n--- DB Forms under Pintu Geser (ID: {$parent->id}) ---\n";
        $children = App\Models\Form::where('parent_id', $parent->id)->get();
        foreach ($children as $c) {
            echo "Category: {$c->title} (ID: {$c->id}) (is_category: {$c->is_category})\n";
            $leaves = App\Models\Form::where('parent_id', $c->id)->get();
            foreach ($leaves as $l) {
                echo "  Leaf: {$l->title} (Slug: {$l->slug}) (ID: {$l->id})\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

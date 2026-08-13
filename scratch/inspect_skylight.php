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

$googleFormId = "1gE_BEpadasEfm-RlJN8d-if9_5njmrrZI8x6pwck5HY"; // Skylight Protection

try {
    $googleForm = $formsService->forms->get($googleFormId);
    $items = $googleForm->getItems();
    
    echo "--- Google Form Items for Skylight Protection ---\n";
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
    
    // Also list DB forms under Skylight Protection (slug: skylife-protection)
    $parent = App\Models\Form::where('slug', 'skylife-protection')->first();
    if ($parent) {
        echo "\n--- DB Forms under Skylight Protection (ID: {$parent->id}) ---\n";
        $children = App\Models\Form::where('parent_id', $parent->id)->get();
        foreach ($children as $c) {
            echo "  Leaf/Child: {$c->title} (Slug: {$c->slug}) (ID: {$c->id}) (is_category: {$c->is_category})\n";
        }
    } else {
        echo "\nParent form 'skylife-protection' not found!\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

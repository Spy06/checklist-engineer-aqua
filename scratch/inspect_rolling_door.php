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

$googleFormId = "1MwhcNfWvirO004XnANXDG5p4OzAmKbxsAyHmkPGG3qs"; // Rolling Door

try {
    $googleForm = $formsService->forms->get($googleFormId);
    $items = $googleForm->getItems();
    
    echo "--- Google Form Items for Rolling Door ---\n";
    $sectionName = "Root";
    foreach ($items as $item) {
        $title = $item->getTitle();
        if ($item->getPageBreakItem()) {
            $sectionName = $title ?: "Unnamed Section";
            echo "\n[Section Page Break]: {$sectionName}\n";
        }
        
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
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

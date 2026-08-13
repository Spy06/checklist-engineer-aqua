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
    
    foreach ($items as $item) {
        $title = $item->getTitle();
        $questionItem = $item->getQuestionItem();
        if ($questionItem) {
            $q = $questionItem->getQuestion();
            $choiceQ = $q->getChoiceQuestion();
            if ($choiceQ && (str_contains(strtolower($title), 'conventional') || str_contains(strtolower($title), 'speed'))) {
                echo "Question: {$title}\n";
                foreach ($choiceQ->getOptions() as $opt) {
                    echo "  Option: " . $opt->getValue() . "\n";
                }
            }
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

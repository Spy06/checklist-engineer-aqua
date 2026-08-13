<?php

use App\Services\GoogleFormsSyncService;
use Illuminate\Contracts\Console\Kernel;
use ReflectionClass;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$syncService = app(GoogleFormsSyncService::class);
$ref = new ReflectionClass($syncService);
$prop = $ref->getProperty('formsService');
$prop->setAccessible(true);
$formsService = $prop->getValue($syncService);

$googleFormId = "1WHfZggFDdRvZ-M-RyAZsZy0d7UVdL3y7iUvxjMbSIXA"; // Fix Ladder

try {
    $googleForm = $formsService->forms->get($googleFormId);
    $items = $googleForm->getItems();
    
    echo "--- Google Form Items for Fix Ladder ---\n";
    foreach ($items as $item) {
        $title = $item->getTitle();
        if ($item->getPageBreakItem()) {
            echo "[Page Break] {$title}\n";
        }
        
        $questionItem = $item->getQuestionItem();
        if ($questionItem) {
            $q = $questionItem->getQuestion();
            $choiceQ = $q->getChoiceQuestion();
            if ($choiceQ) {
                echo "  Question: {$title}\n";
                foreach ($choiceQ->getOptions() as $opt) {
                    $val = $opt->getValue();
                    if (str_contains($val, 'FLE 33')) {
                        echo "    * Option: {$val}\n";
                    }
                }
            }
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

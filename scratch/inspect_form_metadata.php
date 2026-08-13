<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Services\GoogleFormsSyncService;
use Google\Service\Forms;
use Illuminate\Contracts\Console\Kernel;

$syncService = app(GoogleFormsSyncService::class);

// Let's use the Forms API directly or via syncService
// We want to inspect the structure of the Fix Ladder Google Form
$googleFormId = '1WHfZggFDdRvZ-M-RyAZsZy0d7UVdL3y7iUvxjMbSIXA'; // Fix Ladder

// We can get the client and service from syncService
$ref = new ReflectionClass($syncService);
$prop = $ref->getProperty('formsService');
$prop->setAccessible(true);
$formsService = $prop->getValue($syncService);

if (! $formsService) {
    echo "formsService not initialized\n";
    exit;
}

try {
    $form = $formsService->forms->get($googleFormId);
    echo 'Title: '.$form->getInfo()->getTitle()."\n";
    $items = $form->getItems();
    foreach ($items as $item) {
        $title = $item->getTitle();
        $itemId = $item->getItemId();

        $questionItem = $item->getQuestionItem();
        $questionGroupItem = $item->getQuestionGroupItem();
        $pageBreakItem = $item->getPageBreakItem();

        echo "Item: {$title} (ID: {$itemId})\n";
        if ($pageBreakItem) {
            echo "  [Page Break]\n";
        }
        if ($questionItem) {
            $question = $questionItem->getQuestion();
            $choiceQuestion = $question->getChoiceQuestion();
            if ($choiceQuestion) {
                echo '  [Choice Question] Type: '.$choiceQuestion->getType()."\n";
                foreach ($choiceQuestion->getOptions() as $option) {
                    echo '    - Option: '.$option->getValue()."\n";
                }
            } else {
                echo "  [Question] Type: Other\n";
            }
        }
        if ($questionGroupItem) {
            echo "  [Question Group]\n";
        }
    }
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}

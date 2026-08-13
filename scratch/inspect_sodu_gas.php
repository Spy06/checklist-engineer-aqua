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

$googleFormId = "1BYpbO1V16_Iakso7P0AD6rTXScjnkb2d3zYlwnP6dvE"; // Sodu Gas

try {
    $googleForm = $formsService->forms->get($googleFormId);
    $items = $googleForm->getItems();
    
    echo "--- Google Form Items for Sodu Gas ---\n";
    foreach ($items as $item) {
        $title = $item->getTitle();
        $questionItem = $item->getQuestionItem();
        if ($questionItem) {
            $q = $questionItem->getQuestion();
            $choiceQ = $q->getChoiceQuestion();
            echo "  Question: {$title} (Type: " . ($choiceQ ? "Choice" : "Text") . ")\n";
        }
    }
    
    $responses = $formsService->forms_responses->listFormsResponses($googleFormId);
    echo "\n--- Google Form Responses for Sodu Gas ---\n";
    $questionMap = [];
    foreach ($items as $item) {
        $questionItem = $item->getQuestionItem();
        if ($questionItem) {
            $questionMap[$questionItem->getQuestion()->getQuestionId()] = $item->getTitle();
        }
    }
    
    foreach ($responses->getResponses() as $r) {
        $answers = $r->getAnswers();
        echo "Response ID: " . $r->getResponseId() . " | CreateTime: " . $r->getCreateTime() . "\n";
        foreach ($answers as $qId => $ans) {
            $qTitle = $questionMap[$qId] ?? $qId;
            $txtAns = $ans->getTextAnswers();
            $val = $txtAns && count($txtAns->getAnswers()) > 0 ? $txtAns->getAnswers()[0]->getValue() : '';
            echo "  {$qTitle}: {$val}\n";
        }
        echo "---------------------------------\n";
        break; // Just show the first response for inspection
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

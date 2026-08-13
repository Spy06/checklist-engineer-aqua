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
    $questionMap = [];
    foreach ($items as $item) {
        $questionItem = $item->getQuestionItem();
        if ($questionItem) {
            $questionMap[$questionItem->getQuestion()->getQuestionId()] = $item->getTitle();
        }
    }
    
    $responses = $formsService->forms_responses->listFormsResponses($googleFormId);
    
    echo "--- Distinct Answers for Nomer/Nomor Conventional/Speed Rolling Door ---\n";
    $distinct = [];
    foreach ($responses->getResponses() as $r) {
        $answers = $r->getAnswers();
        foreach ($answers as $qId => $ans) {
            $qTitle = $questionMap[$qId] ?? $qId;
            if (str_contains(strtolower($qTitle), 'nomer') || str_contains(strtolower($qTitle), 'nomor')) {
                $txtAns = $ans->getTextAnswers();
                $val = $txtAns && count($txtAns->getAnswers()) > 0 ? $txtAns->getAnswers()[0]->getValue() : '';
                $distinct[$qTitle][$val] = ($distinct[$qTitle][$val] ?? 0) + 1;
            }
        }
    }
    
    print_r($distinct);
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

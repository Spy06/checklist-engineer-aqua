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

$googleFormId = "1s9312KChUe8_mH49zxCWygtSS1LhGS3XRFUYvGex_6k"; // Scaffolding

try {
    $googleForm = $formsService->forms->get($googleFormId);
    $items = $googleForm->getItems();
    
    echo "--- Google Form Items for Scaffolding ---\n";
    foreach ($items as $item) {
        $title = $item->getTitle();
        $questionItem = $item->getQuestionItem();
        if ($questionItem) {
            $q = $questionItem->getQuestion();
            $choiceQ = $q->getChoiceQuestion();
            if ($choiceQ) {
                echo "  Question: {$title}\n";
                foreach ($choiceQ->getOptions() as $opt) {
                    echo "    * Option: " . $opt->getValue() . "\n";
                }
            }
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

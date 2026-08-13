<?php

use App\Services\GoogleFormsSyncService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$service = app(GoogleFormsSyncService::class);
$reflection = new ReflectionClass($service);
$prop = $reflection->getProperty('formsService');
$prop->setAccessible(true);
$googleService = $prop->getValue($service);

$formId = '1WHfZggFDdRvZ-M-RyAZsZy0d7UVdL3y7iUvxjMbSIXA'; // Fix ladder

try {
    $formInfo = $googleService->forms->get($formId);
    echo 'Form Title: '.$formInfo->getInfo()->getTitle()."\n";
    $response = $googleService->forms_responses->listFormsResponses($formId);
    $responses = $response->getResponses();
    echo 'Count in first page: '.($responses ? count($responses) : 0)."\n";
    echo 'Next Page Token: '.$response->getNextPageToken()."\n";

    $mapMethod = $reflection->getMethod('buildQuestionMap');
    $mapMethod->setAccessible(true);
    $questionMap = $mapMethod->invoke($service, $formInfo);
    if ($responses && count($responses) > 0) {
        $found = false;
        foreach ($responses as $r) {
            $answers = $r->getAnswers();
            if ($answers) {
                $row = [];
                foreach ($answers as $qId => $ans) {
                    $qTitle = $questionMap[$qId] ?? 'Unknown';
                    $txt = $ans->getTextAnswers();
                    $val = '';
                    if ($txt && count($txt->getAnswers()) > 0) {
                        $val = $txt->getAnswers()[0]->getValue();
                    }
                    $row[$qTitle] = $val;
                }
                if (strtolower($row['Nama pemeriksaan'] ?? '') === 'encep') {
                    echo 'Tanggal pemeriksaan for encep: '.($row['Tanggal pemeriksaan'] ?? 'NULL')."\n";
                    echo 'Cap waktu: '.$r->getCreateTime()."\n";
                    $found = true;
                }
            }
        }
        if (! $found) {
            echo "encep not found in API!\n";
        }
    }
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}

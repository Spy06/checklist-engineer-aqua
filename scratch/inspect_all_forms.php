<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Services\GoogleFormsSyncService;
use Illuminate\Contracts\Console\Kernel;

$syncService = app(GoogleFormsSyncService::class);

$ref = new ReflectionClass($syncService);
$prop = $ref->getProperty('formsService');
$prop->setAccessible(true);
$formsService = $prop->getValue($syncService);

if (! $formsService) {
    echo "formsService not initialized\n";
    exit;
}

$googleForms = [
    ['id' => '1TsEx5jisS8tlPVuu79qK380fQpdZ2Oh5Y2T48s_ed9E', 'slug' => 'mobile-ladder'],
    ['id' => '1ncZ9JGBDePI1FZVe-8ptAJrO4p3pP_Da5_-StZN6EDQ', 'slug' => 'lifeline'],
    ['id' => '1mwfNlvPwjj4jqvZbg6OVj4-7Eva5aGwG21eafPfSuBw', 'slug' => 'barrier-dan-self-door-closer'],
    ['id' => '1x0YCDoV8wDVuUN1IRWsUxYBFyJvfx_dZMb-93lv8UUo', 'slug' => 'full-body-harness'],
    ['id' => '1AJ-2475yjGAVJmVtwWJaFmcdEL0gBmFmfa4pst96zZE', 'slug' => 'work-at-height'],
    ['id' => '1PaPGizj_H39oSx_28lmJM280sPhAULUP8KLcyVFg5i8', 'slug' => 'mesin-bor-duduk'],
    ['id' => '1s9NFNDRHNtYwukvVrdicKZ9gar-HQcHiiDhznY0EbcU', 'slug' => 'mesin-press'],
    ['id' => '1vTUjTavOsNVw98R9VNKrts5xEmPi2mfiZIZGRx5gWD8', 'slug' => 'pintu-geser'],
    ['id' => '1MwhcNfWvirO004XnANXDG5p4OzAmKbxsAyHmkPGG3qs', 'slug' => 'rolling-door'],
    ['id' => '1htAepCxxLAnj3Ty1WAf64EbVLyXYhyOsriOfUZ7N4fw', 'slug' => 'rak-facility'],
    ['id' => '1gE_BEpadasEfm-RlJN8d-if9_5njmrrZI8x6pwck5HY', 'slug' => 'skylife-protection'],
    ['id' => '1-DeqGzFxS6PhLoRYUAiS7Azk-UBfndier1v5D0QxAac', 'slug' => 'lemari-apd-workshop'],
    ['id' => '1zLNvQkVSLY659Uho-sS5rE7GcB5Hvbf6uetemyo_PsM', 'slug' => 'jh-makita-01'],
    ['id' => '14NNApxwgulJ1f42qvo_SJfMgxwXeyM2Y1_4F_rm46GA', 'slug' => 'lemari-tool-3'],
    ['id' => '1BYpbO1V16_Iakso7P0AD6rTXScjnkb2d3zYlwnP6dvE', 'slug' => 'sodu-gas'],
    ['id' => '1GecukQ9tplYowYaapyealj5CmRXUCpeLyTwgy-6_eVQ', 'slug' => 'lemari-tool-4'],
    ['id' => '1k299QYDsIMWByKtxlK8ufdZg86XEMqF7Ho79idH0LaY', 'slug' => 'mesin-gerinda-duduk'],
    ['id' => '1__y-rJb347p6e1araQE9re9A_2FfXvqIw3-1NjXQCJ0', 'slug' => 'lemari-tool-2'],
    ['id' => '1Ds5H-ThjSrV-BRZss8WVGkm88LojE7uO4EB5ioHXvzE', 'slug' => 'lemari-tool-1'],
    ['id' => '16Oay89rcSyZRIK8WSYb2gVbEIuVzxjh8ljonQP5Orow', 'slug' => 'mesin-las-argon'],
    ['id' => '1SV4vR2g-STWWSEPgSp0yjy774ai0AtEjgI1wplvFNcU', 'slug' => 'forklift'],
    ['id' => '1VS8xfUuc_Ny2KU2vSrR7v-OtF-JfUpcSOupXUE2Yl8Q', 'slug' => 'argon-dan-nitrogen-storage-gas'],
    ['id' => '1AjN3QObwEfhOdpqvyP1ipjNKrYU9ce_4VS8Dnxgll38', 'slug' => 'freon-storage'],
    ['id' => '1s9312KChUe8_mH49zxCWygtSS1LhGS3XRFUYvGex_6k', 'slug' => 'scaffolding'],
    ['id' => '1WHfZggFDdRvZ-M-RyAZsZy0d7UVdL3y7iUvxjMbSIXA', 'slug' => 'fix-ladder'],
    ['id' => '1xsy0UHCslOddC_APZxhEikz-Sx07hfaC_VB17WPJZ28', 'slug' => 'jh-krisbow-01'],
];

$outputFile = __DIR__.'/../scratch/forms_structure.txt';
$fp = fopen($outputFile, 'w');

foreach ($googleForms as $gf) {
    fwrite($fp, "========================================\n");
    fwrite($fp, "SLUG: {$gf['slug']} | ID: {$gf['id']}\n");
    try {
        $form = $formsService->forms->get($gf['id']);
        fwrite($fp, 'TITLE: '.$form->getInfo()->getTitle()."\n");
        $items = $form->getItems();
        if ($items) {
            foreach ($items as $item) {
                $title = $item->getTitle();
                $questionItem = $item->getQuestionItem();
                $pageBreak = $item->getPageBreakItem();

                if ($pageBreak) {
                    fwrite($fp, "  [Page Break] Title: {$title}\n");
                }
                if ($questionItem) {
                    $q = $questionItem->getQuestion();
                    $choiceQ = $q->getChoiceQuestion();
                    if ($choiceQ) {
                        fwrite($fp, "  [Choice Question] Name: {$title} (Type: ".$choiceQ->getType().")\n");
                        foreach ($choiceQ->getOptions() as $opt) {
                            fwrite($fp, '    - '.$opt->getValue()."\n");
                        }
                    } else {
                        fwrite($fp, "  [Question] Name: {$title} (Type: Other)\n");
                    }
                }
            }
        }
    } catch (Exception $e) {
        fwrite($fp, 'ERROR: '.$e->getMessage()."\n");
    }
}

fclose($fp);
echo "Structures dumped to scratch/forms_structure.txt\n";

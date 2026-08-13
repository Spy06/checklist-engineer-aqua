<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Form;
use App\Models\FormSubmission;

$titlesToDelete = [
    'Confined Space',
    'Fire Evacuation',
    'Work Permit',
    'Truck Loading Unloading',
    'Chemical Product',
    'Atex',
    'LOTO (Log Out Take Out)',
    'LOTO'
];

$roots = Form::whereNull('parent_id')->get();
$formsToDelete = [];

foreach ($roots as $root) {
    foreach ($titlesToDelete as $title) {
        if (stripos(trim($root->title), $title) !== false) {
            $formsToDelete[] = $root;
        }
    }
}

echo "Forms found matching the deletion criteria:\n";
foreach ($formsToDelete as $form) {
    // Collect all leaf IDs under this root
    $leafIds = [];
    $getLeaves = function($f) use (&$getLeaves, &$leafIds) {
        if ($f->children->count() === 0) {
            $leafIds[] = $f->id;
        } else {
            foreach ($f->children as $c) {
                $getLeaves($c);
            }
        }
    };
    $getLeaves($form);
    
    // Check submissions
    $subsCount = FormSubmission::whereIn('form_id', $leafIds)->count();
    
    echo "- ID: {$form->id} | Title: {$form->title} | Leaves: " . count($leafIds) . " | DB Submissions: {$subsCount}\n";
}

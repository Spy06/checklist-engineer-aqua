<?php

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$forms = Form::whereNotNull('parent_id')->get();
echo "Total sub-forms: " . $forms->count() . "\n";

$unsubmitted = [];
foreach ($forms as $form) {
    $count = FormSubmission::where('form_id', $form->id)->count();
    if ($count === 0) {
        $unsubmitted[] = $form;
    }
}

echo "Total forms with 0 submissions: " . count($unsubmitted) . "\n";
if (count($unsubmitted) > 0) {
    echo "First 10 forms with 0 submissions:\n";
    for ($i = 0; $i < min(10, count($unsubmitted)); $i++) {
        $f = $unsubmitted[$i];
        echo " - ID: {$f->id} | Title: {$f->title} | Slug: {$f->slug} | Parent: {$f->parent->title}\n";
    }
}

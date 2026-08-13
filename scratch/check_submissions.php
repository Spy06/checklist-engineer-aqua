<?php

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$slug = 'fix-ladder';
$form = Form::where('slug', $slug)->first();
if (! $form) {
    exit("Form not found\n");
}

function countSubmissions($formId)
{
    return FormSubmission::where('form_id', $formId)->count();
}

echo "Total submissions for $slug directly: ".countSubmissions($form->id)."\n";
$children = Form::where('parent_id', $form->id)->get();
foreach ($children as $child) {
    echo "  Child {$child->slug}: ".countSubmissions($child->id)." submissions. (is_category: {$child->is_category})\n";
    if ($child->is_category) {
        $leaves = Form::where('parent_id', $child->id)->get();
        foreach ($leaves as $leaf) {
            $cnt = countSubmissions($leaf->id);
            if ($cnt > 0) {
                echo "    Leaf {$leaf->slug}: $cnt submissions.\n";
            }
        }
    }
}

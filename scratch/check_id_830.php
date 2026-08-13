<?php

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$f830 = Form::find(830);
$f996 = Form::find(996);

echo "Form 830 (PERKIRAN): " . ($f830 ? "Exists! Parent ID: {$f830->parent_id}, Slug: {$f830->slug}" : "Deleted") . "\n";
echo "Form 996 (PARKIRAN): " . ($f996 ? "Exists! Parent ID: {$f996->parent_id}, Slug: {$f996->slug}" : "Deleted") . "\n";

// Let's run the sync command's cleanup logic manually on form 830 to see what happens
if ($f830) {
    $submissionsCount = FormSubmission::where('form_id', 830)->count();
    echo "Form 830 submissions count: " . $submissionsCount . "\n";
}

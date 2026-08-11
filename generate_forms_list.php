<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$output = "## Total Forms: " . App\Models\Form::count() . "\n\n";

$categories = App\Models\Form::whereNull('parent_id')->get();
foreach ($categories as $cat) {
    $subForms = App\Models\Form::where('parent_id', $cat->id)->get();
    
    $leafCount = 0;
    $details = [];
    foreach ($subForms as $sub) {
        $leaves = App\Models\Form::where('parent_id', $sub->id)->get();
        if ($leaves->count() > 0) {
            $leafCount += $leaves->count();
            $details[] = "  - **{$sub->title}** (" . $leaves->count() . " lokasi/item)";
        } else {
            $leafCount++;
            $details[] = "  - **{$sub->title}**";
        }
    }
    
    if (count($details) > 0) {
        $output .= "### {$cat->title} ($leafCount sub-forms)\n";
        $output .= implode("\n", $details) . "\n\n";
    } else {
        $output .= "### {$cat->title} (No sub-forms)\n\n";
    }
}

file_put_contents('forms_list.md', $output);

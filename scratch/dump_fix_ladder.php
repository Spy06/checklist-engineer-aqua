<?php

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$fixLadder = Form::where('slug', 'fix-ladder')->first();
if (!$fixLadder) {
    echo "Fix Ladder form not found in database!\n";
    exit;
}

$out = "Root Form:\n";
$out .= "ID: {$fixLadder->id} | Title: {$fixLadder->title} | Slug: {$fixLadder->slug} | IsCategory: " . ($fixLadder->is_category ? 'true' : 'false') . "\n\n";

function dumpChildren($parent, $indent = "  ") {
    $out = "";
    $children = Form::where('parent_id', $parent->id)->orderBy('order')->get();
    foreach ($children as $child) {
        $subCount = FormSubmission::where('form_id', $child->id)->count();
        $out .= "{$indent}ID: {$child->id} | Title: {$child->title} | IsCategory: " . ($child->is_category ? 'true' : 'false') . " | Submissions: {$subCount}\n";
        $out .= dumpChildren($child, $indent . "  ");
    }
    return $out;
}

$out .= dumpChildren($fixLadder);
file_put_contents(__DIR__.'/dump_output_utf8.txt', $out);
echo "Written to dump_output_utf8.txt\n";

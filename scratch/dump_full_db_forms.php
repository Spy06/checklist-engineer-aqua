<?php

use App\Models\Form;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

function dumpNode($node, $indent = 0)
{
    $res = str_repeat('  ', $indent)."- ID: {$node->id} | Slug: {$node->slug} | Title: {$node->title} | is_category: ".($node->is_category ? 'YES' : 'NO')."\n";
    foreach ($node->children()->orderBy('order')->get() as $child) {
        $res .= dumpNode($child, $indent + 1);
    }

    return $res;
}

$output = '';
$roots = Form::whereNull('parent_id')->orderBy('order')->get();
foreach ($roots as $root) {
    $output .= dumpNode($root);
}

file_put_contents(__DIR__.'/../scratch/db_forms.txt', $output);
echo "Full DB structure dumped to scratch/db_forms.txt\n";

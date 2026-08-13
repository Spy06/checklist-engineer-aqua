<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Form;
use Illuminate\Contracts\Console\Kernel;

function dumpNode($node, $indent = 0)
{
    echo str_repeat('  ', $indent)."- ID: {$node->id} | Slug: {$node->slug} | Title: {$node->title} | is_category: ".($node->is_category ? 'YES' : 'NO')."\n";
    foreach ($node->children()->orderBy('order')->get() as $child) {
        dumpNode($child, $indent + 1);
    }
}

$roots = Form::whereNull('parent_id')->orderBy('order')->get();
foreach ($roots as $root) {
    dumpNode($root);
}

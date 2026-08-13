<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$parent = App\Models\Form::where('slug', 'rolling-door')->first();

if ($parent) {
    echo "Parent: {$parent->title} (ID: {$parent->id})\n";
    $categories = App\Models\Form::where('parent_id', $parent->id)->get();
    foreach ($categories as $cat) {
        echo "  Category: {$cat->title} (ID: {$cat->id})\n";
        $leaves = App\Models\Form::where('parent_id', $cat->id)->get();
        foreach ($leaves as $leaf) {
            echo "    Leaf: {$leaf->title} (Slug: {$leaf->slug}) (ID: {$leaf->id})\n";
        }
    }
} else {
    echo "Parent form 'rolling-door' not found!\n";
}

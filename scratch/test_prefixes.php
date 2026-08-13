<?php

use App\Models\Form;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$fixLadder = Form::where('slug', 'fix-ladder')->first();
$leaves = Form::whereNotNull('parent_id')
    ->where('is_category', false)
    ->get();

$fixLadderLeaves = [];
foreach ($leaves as $lf) {
    $ancestor = $lf->parent;
    while ($ancestor) {
        if ($ancestor->id === $fixLadder->id) {
            $fixLadderLeaves[] = $lf;
            break;
        }
        $ancestor = $ancestor->parent;
    }
}

echo "Total leaf forms under Fix Ladder: " . count($fixLadderLeaves) . "\n";

$pattern = '/^([A-Z]{3,4}\s?\d+[A-Za-z]?)\b/';

$mapped = [];
foreach ($fixLadderLeaves as $lf) {
    $title = trim($lf->title);
    if (preg_match($pattern, $title, $matches)) {
        $prefix = strtoupper(str_replace(' ', '', $matches[1]));
        $mapped[$prefix][] = [
            'id' => $lf->id,
            'title' => $lf->title,
            'slug' => $lf->slug,
            'parent' => $lf->parent->title
        ];
    } else {
        echo "⚠️ No match pattern for title: '{$title}'\n";
    }
}

echo "\n--- Prefixes with multiple forms ---\n";
foreach ($mapped as $prefix => $forms) {
    if (count($forms) > 1) {
        echo "Prefix: {$prefix} (Count: " . count($forms) . ")\n";
        foreach ($forms as $f) {
            echo "  - ID: {$f['id']} | Parent: {$f['parent']} | Title: {$f['title']} | Slug: {$f['slug']}\n";
        }
    }
}

<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Form;

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
$deletedCount = 0;

foreach ($roots as $root) {
    foreach ($titlesToDelete as $title) {
        if (stripos(trim($root->title), $title) !== false) {
            echo "Deleting form: {$root->title}\n";
            $root->delete(); // This will cascade delete its children because of the DB constraints/eloquent
            $deletedCount++;
            break;
        }
    }
}

echo "Total forms deleted: {$deletedCount}\n";

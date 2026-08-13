<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Form;

$parent = Form::find(797);
echo "Parent: " . $parent->title . "\n";

Form::create(['title' => 'FLE 48 CARTON FEEDER L2', 'parent_id' => 797, 'is_category' => false, 'order' => 100]);
Form::create(['title' => 'FLE 49 CARTON FEEDER L3', 'parent_id' => 797, 'is_category' => false, 'order' => 101]);
Form::create(['title' => 'FLE 50 CARTON FEEDER LI', 'parent_id' => 797, 'is_category' => false, 'order' => 102]);

echo "Berhasil menambah 3 form Carton Feeder ke database.\n";

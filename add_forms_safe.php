<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Form;

try {
    Form::create(['title' => 'FLE 48 CARTON FEEDER L2', 'slug' => 'fle-48-carton-feeder-l2', 'parent_id' => 797, 'is_category' => false, 'order' => 100]);
    Form::create(['title' => 'FLE 49 CARTON FEEDER L3', 'slug' => 'fle-49-carton-feeder-l3', 'parent_id' => 797, 'is_category' => false, 'order' => 101]);
    Form::create(['title' => 'FLE 50 CARTON FEEDER L1', 'slug' => 'fle-50-carton-feeder-l1', 'parent_id' => 797, 'is_category' => false, 'order' => 102]);
    echo "BERHASIL SIMPAN KE DB!\n";
} catch (\Exception $e) {
    echo "GAGAL: " . $e->getMessage() . "\n";
}

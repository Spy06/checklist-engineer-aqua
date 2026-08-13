<?php

use App\Services\GoogleFormsSyncService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$service = app(GoogleFormsSyncService::class);

$slug = 'fix-ladder';
$row = [
    'Cap waktu' => '2026/08/12 4:25:06 PM GMT+7',
    'Nama pemeriksaan' => 'Iwan R',
    'Tanggal pemeriksaan' => '2026-07-24',
    'Area Pemeriksaan' => 'Existing',
    'Existing' => 'FLE 37 OFFICE',
];

$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('resolveLeafForm');
$method->setAccessible(true);

$form = $method->invoke($service, $slug, $row);

if ($form) {
    echo 'Resolved Form: '.$form->title.' (ID: '.$form->id.")\n";
} else {
    echo "Failed to resolve form!\n";
}

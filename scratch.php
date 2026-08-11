<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$sync = app(\App\Services\GoogleFormsSyncService::class);
$data = $sync->getLiveResponses('1bJXgi5GMANXfesZAXkn77f4KFugpXCwLN7nLkEIKQjk', 'mobile-ladder');
echo json_encode($data->toArray(), JSON_PRETTY_PRINT);

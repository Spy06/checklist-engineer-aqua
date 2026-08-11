<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$users = \Illuminate\Support\Facades\DB::table('users')->where('name', 'like', '%tangga%')->get(['name'])->toArray();
echo json_encode($users, JSON_PRETTY_PRINT);

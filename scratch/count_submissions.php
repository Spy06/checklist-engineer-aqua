<?php

use App\Models\FormSubmission;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "Total submissions in database: " . FormSubmission::count() . "\n";
$latest = FormSubmission::orderBy('id', 'desc')->take(5)->get();
foreach ($latest as $sub) {
    echo "ID: {$sub->id} | User: {$sub->user->name} | Form: {$sub->form->title} | Date: {$sub->date}\n";
}

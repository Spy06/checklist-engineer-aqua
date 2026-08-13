<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FormSubmission;

$sub = FormSubmission::whereNull('form_id')->first();
if ($sub) {
    echo json_encode($sub->toArray(), JSON_PRETTY_PRINT) . "\n";
} else {
    echo "No null form_id submissions found.\n";
}

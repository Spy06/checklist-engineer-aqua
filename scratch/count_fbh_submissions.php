<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\FormSubmission;
use Illuminate\Contracts\Console\Kernel;

echo 'Submissions for Form 108 (Full Body Harness under work-at-height): '.FormSubmission::where('form_id', 108)->count()."\n";
echo 'Submissions for Form 172 (Full Body Harness under safety-machines): '.FormSubmission::where('form_id', 172)->count()."\n";

<?php

use App\Models\FormSubmission;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$subs = FormSubmission::all();
$counts = [];
foreach ($subs as $sub) {
    if (!$sub->form) {
        $fid = $sub->form_id;
        $key = $fid === null ? 'NULL' : (string)$fid;
        if (!isset($counts[$key])) {
            $counts[$key] = 0;
        }
        $counts[$key]++;
    }
}

arsort($counts);

echo "Top 20 missing form IDs in submissions:\n";
$i = 0;
foreach ($counts as $fid => $count) {
    echo "Form ID: " . $fid . " | Count: " . $count . "\n";
    $i++;
    if ($i >= 20) break;
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo App\Models\FormSubmission::count() . " submissions\n";
echo App\Models\User::where('role', 'responden')->count() . " users\n";
App\Models\FormSubmission::with('user', 'form')->latest('date')->take(5)->get()->each(function($s) {
    echo $s->form->title . ' by ' . $s->user->name . ' on ' . $s->date . ' (' . (is_array($s->answers) ? count($s->answers) : 0) . " checked)\n";
});

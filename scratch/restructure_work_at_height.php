<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Form;

try {
    // 1. Make Work at height (ID 1) a category
    $wah = Form::find(1);
    if ($wah) {
        $wah->update(['is_category' => true]);
        echo "Work at height (ID 1) updated to category.\n";
    } else {
        throw new \Exception("Work at height (ID 1) not found!");
    }

    // 2. Set parent of all siblings to Work at height (ID 1)
    $siblingIds = [
        672, // Fix Ladder
        677, // Mobile Ladder
        681, // Full Body Harness
        678, // Lifeline
        679, // Skylight Protection
        107, // Scaffolding
        244, // Barrier Dan Self Door Closer
    ];

    foreach ($siblingIds as $id) {
        $form = Form::find($id);
        if ($form) {
            $form->update(['parent_id' => 1]);
            echo "Form '{$form->title}' (ID {$id}) parent set to Work at height.\n";
        } else {
            echo "Warning: Form ID {$id} not found.\n";
        }
    }

    // 3. Rename Barrier Dan Self Door Closer to Barrier / Railing
    $barrier = Form::find(244);
    if ($barrier) {
        $barrier->update(['title' => 'Barrier / Railing']);
        echo "Renamed Barrier Dan Self Door Closer to 'Barrier / Railing'.\n";
    }

    // 4. Create Mobile Elevation (Lifter) if not exists
    $lifter = Form::updateOrCreate(
        ['parent_id' => 1, 'slug' => 'mobile-elevation-lifter'],
        [
            'title' => 'Mobile Elevation (Lifter)',
            'is_category' => false,
            'options' => [
                'Hydraulic System',
                'Platform',
                'Base & Foot',
                'Electric',
                'Pembatasan atau pengamanan'
            ],
            'order' => 110
        ]
    );
    echo "Mobile Elevation (Lifter) form created/updated.\n";

    echo "DATABASE RESTUCTURING SUCCESSFUL!\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

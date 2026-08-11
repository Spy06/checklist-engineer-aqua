<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$mappings = [
    'Dadang Hermawan' => [
        'Dadang Hermawan', 'Dadang Hermaan', 'Dadang Hermwan', 'Dadang H', 'Dadang hermawanH', 
        'Dadang', 'DadangH', 'ddg', 'dd', 'Dadanv Hermawan', 'Dadanv H', 
        'Dadang Heremawan', 'Dadang heramwan'
    ],
    'Iwan Ridwan' => [
        'Iwsn Ridwan', 'Iwan Ridwan', 'Iwan'
    ],
    'Feri Setyo P.' => [
        'Feri Setyo P', 'Feri', 'Feri Setyo P.'
    ],
    'Badru Prahmana' => [
        'Badru prahmana', 'Badru p', 'Badru '
    ],
    'Idar Muhammad' => [
        'idar muhammad', 'idar muhammaf', 'idar muhmmad'
    ],
    'Pipin Diana' => [
        'Pipin diana', 'Pipin'
    ],
    'Chandra Budiana' => [
        'Chandra budiyana', 'Chandra', 'Chandra Budiana'
    ],
    'Lutfi Arham' => [
        'Lufti arham', 'Lufti', 'Lutfi Arham'
    ],
    'Ukat Abdul Aziz' => [
        'Abdul Aziz, Ukat', 'Ukat Abdul Aziz'
    ],
    'Opan Sopandi' => [
        'Opan sopiandi ', 'Opan Sopandi'
    ],
    'Muhammad Zaenal' => [
        'MUHAMMAD ZAENAL ', 'M. Zaenal'
    ],
    'Sesuai Standard' => [
        'Sesuai Standard', 'Sesuai'
    ]
];

DB::beginTransaction();

try {
    foreach ($mappings as $primaryName => $aliases) {
        $primaryName = trim($primaryName);
        
        $primaryUser = User::where('name', $primaryName)->first();
        if (!$primaryUser) {
            $primaryUser = User::whereIn('name', $aliases)->orderBy('id')->first();
            
            if (!$primaryUser) {
                $primaryUser = User::create([
                    'name' => $primaryName,
                    'username' => Str::slug($primaryName) . '_' . uniqid(),
                    'password' => bcrypt('password123'),
                    'role' => 'responden'
                ]);
            } else {
                $primaryUser->update(['name' => $primaryName]);
            }
        }

        $aliasUsers = User::whereIn('name', $aliases)->get();

        foreach ($aliasUsers as $aliasUser) {
            if ($aliasUser->id === $primaryUser->id) continue;

            echo "Merging '{$aliasUser->name}' (ID: {$aliasUser->id}) into '{$primaryUser->name}' (ID: {$primaryUser->id})\n";

            // 1. Move Form Submissions
            FormSubmission::where('user_id', $aliasUser->id)
                          ->update(['user_id' => $primaryUser->id]);

            // 2. Move User Form Assignments
            $assignments = DB::table('user_form_assignments')->where('user_id', $aliasUser->id)->get();
            foreach ($assignments as $assignment) {
                $exists = DB::table('user_form_assignments')
                            ->where('user_id', $primaryUser->id)
                            ->where('form_id', $assignment->form_id)
                            ->exists();
                if (!$exists) {
                    DB::table('user_form_assignments')->insert([
                        'user_id' => $primaryUser->id,
                        'form_id' => $assignment->form_id
                    ]);
                }
            }
            DB::table('user_form_assignments')->where('user_id', $aliasUser->id)->delete();

            // 3. Delete Alias User
            $aliasUser->delete();
        }
    }
    
    // Trim names for remaining users as well
    $allUsers = User::where('role', 'responden')->get();
    foreach ($allUsers as $u) {
        if (trim($u->name) !== $u->name) {
            $u->update(['name' => trim($u->name)]);
        }
    }

    DB::commit();
    echo "Merge completed successfully.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}

<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates demo accounts for all 3 roles:
     * - Super Admin : username: superadmin  / password: password
     * - Admin       : username: admin       / password: password  (assigned: Work Permit, Safety Machines)
     * - Responden   : username: responden   / password: password  (assigned: Work at Height, Confined Space)
     *
     * Also updates existing user "engineer" → super_admin.
     */
    public function run(): void
    {
        // ─────────────────────────────────────────────
        // 1. Make sure "engineer" is super_admin
        // ─────────────────────────────────────────────
        User::where('username', 'engineer')->update(['role' => 'super_admin']);

        // ─────────────────────────────────────────────
        // 2. Super Admin demo account
        // ─────────────────────────────────────────────
        User::updateOrCreate(
            ['username' => 'superadmin'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('password'),
                'role'     => 'super_admin',
            ]
        );

        // ─────────────────────────────────────────────
        // 3. Admin demo account (assigned to Work Permit & Safety Machines)
        // ─────────────────────────────────────────────
        $adminUser = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name'     => 'Admin Demo',
                'password' => Hash::make('password'),
                'role'     => 'admin',
            ]
        );

        // Assign root forms to admin
        $adminFormIds = Form::whereNull('parent_id')
            ->whereIn('title', ['Work Permit', 'Safety Machines'])
            ->pluck('id')
            ->toArray();

        $adminUser->assignedForms()->sync($adminFormIds);

        // ─────────────────────────────────────────────
        // 4. Responden demo account (assigned to Work at Height & Confined Space)
        // ─────────────────────────────────────────────
        $respondenUser = User::updateOrCreate(
            ['username' => 'responden'],
            [
                'name'     => 'Responden Demo',
                'password' => Hash::make('password'),
                'role'     => 'responden',
            ]
        );

        // Assign root forms to responden
        $respondenFormIds = Form::whereNull('parent_id')
            ->whereIn('title', ['Work at height', 'Confined Space'])
            ->pluck('id')
            ->toArray();

        $respondenUser->assignedForms()->sync($respondenFormIds);

        // ─────────────────────────────────────────────
        // 5. Print summary
        // ─────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('✅ User seeder selesai! Akun yang tersedia:');
        $this->command->table(
            ['Role', 'Username', 'Password', 'Assigned Forms'],
            [
                ['Super Admin', 'superadmin',  'password', '— Semua Forms —'],
                ['Super Admin', 'engineer',    '(tidak berubah)', '— Semua Forms —'],
                ['Admin',       'admin',       'password', 'Work Permit, Safety Machines'],
                ['Responden',   'responden',   'password', 'Work at Height, Confined Space'],
            ]
        );
        $this->command->info('');
    }
}

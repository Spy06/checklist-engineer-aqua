<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Engineer Aqua',
            'username' => 'engineer',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'username' => 'test',
            'password' => bcrypt('password'),
            'role' => 'responden',
        ]);

        $this->call([
            FormSeeder::class,
            UserSeeder::class,
        ]);
    }
}

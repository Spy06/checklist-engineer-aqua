<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add new role column
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('responden')->after('password');
        });

        // Step 2: Migrate existing data
        // Users with is_admin = 1 become super_admin, others become responden
        DB::table('users')->where('is_admin', 1)->update(['role' => 'super_admin']);
        DB::table('users')->where('is_admin', 0)->update(['role' => 'responden']);

        // Step 3: Drop old is_admin column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
        });

        DB::table('users')->where('role', 'super_admin')->update(['is_admin' => 1]);
        DB::table('users')->where('role', '!=', 'super_admin')->update(['is_admin' => 0]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};

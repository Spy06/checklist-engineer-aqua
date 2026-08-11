<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enforce 1 PIC per form by replacing the composite unique index
     * with a unique constraint on form_id alone.
     *
     * MySQL does not allow dropping an index used by a foreign key directly,
     * so we must drop the foreign keys first, then swap the indexes, then
     * re-add the foreign keys.
     */
    public function up(): void
    {
        // 1. Clean up duplicate assignments — keep the one with the lowest ID
        DB::statement("
            DELETE a FROM user_form_assignments a
            INNER JOIN user_form_assignments b
            ON a.form_id = b.form_id AND a.id > b.id
        ");

        Schema::table('user_form_assignments', function (Blueprint $table) {
            // Drop foreign keys first (MySQL requirement)
            $table->dropForeign(['user_id']);
            $table->dropForeign(['form_id']);

            // Drop the old composite unique index
            $table->dropUnique(['user_id', 'form_id']);

            // Add unique on form_id only → 1 PIC per form
            $table->unique('form_id');

            // Re-add foreign keys
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('form_id')->references('id')->on('forms')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_form_assignments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['form_id']);

            $table->dropUnique(['form_id']);
            $table->unique(['user_id', 'form_id']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('form_id')->references('id')->on('forms')->cascadeOnDelete();
        });
    }
};

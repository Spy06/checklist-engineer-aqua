<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only run if the table is empty to prevent duplicates
        if (DB::table('forms')->count() > 0) {
            $this->command->info('Forms table is not empty, skipping seeder.');

            return;
        }

        $formsConfig = config('forms');
        if (! $formsConfig) {
            $this->command->warn('No config/forms.php found or it is empty.');

            return;
        }

        $this->seedLevel($formsConfig, null);
        $this->command->info('Forms seeded successfully.');

        $this->migrateSubmissions();
    }

    /**
     * @param  array<string, mixed>  $items
     */
    private function seedLevel(array $items, ?int $parentId): void
    {
        $order = 1;
        foreach ($items as $slug => $def) {
            // Some lists might just be a string if it's options array, but config('forms') keys at root are string slugs.
            if (! is_array($def)) {
                continue;
            }

            $isCategory = $def['is_category'] ?? false;
            if (isset($def['items']) && ! isset($def['options'])) {
                $isCategory = true;
            }

            $id = DB::table('forms')->insertGetId([
                'parent_id' => $parentId,
                'slug' => $slug,
                'title' => $def['title'] ?? Str::title(str_replace('-', ' ', $slug)),
                'description' => $def['description'] ?? null,
                'icon' => $def['icon'] ?? null,
                'color' => $def['color'] ?? null,
                'bg_color' => $def['bg_color'] ?? null,
                'border_color' => $def['border_color'] ?? null,
                'text_color' => $def['text_color'] ?? null,
                'is_category' => $isCategory,
                'options' => isset($def['options']) ? json_encode($def['options']) : null,
                'order' => $order++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (isset($def['items']) && is_array($def['items'])) {
                $this->seedLevel($def['items'], $id);
            }
        }
    }

    private function migrateSubmissions(): void
    {
        $submissions = DB::table('form_submissions')->whereNull('form_id')->get();
        $forms = DB::table('forms')->get();

        $formPaths = [];

        // Helper to build path recursively
        $buildPath = function ($form) use ($forms, &$buildPath, &$formPaths) {
            if (isset($formPaths[$form->id])) {
                return $formPaths[$form->id];
            }

            if (! $form->parent_id) {
                $path = $form->slug;
            } else {
                $parent = $forms->firstWhere('id', $form->parent_id);
                $path = $parent ? $buildPath($parent).'.'.$form->slug : $form->slug;
            }

            $formPaths[$form->id] = $path;

            return $path;
        };

        foreach ($forms as $form) {
            $buildPath($form);
        }

        // Now map submissions
        $updated = 0;
        foreach ($submissions as $sub) {
            $matchingFormId = null;
            foreach ($formPaths as $id => $path) {
                if ($path === $sub->form_type) {
                    $matchingFormId = $id;
                    break;
                }
            }

            if ($matchingFormId) {
                DB::table('form_submissions')
                    ->where('id', $sub->id)
                    ->update(['form_id' => $matchingFormId]);
                $updated++;
            }
        }

        $this->command->info("Migrated $updated existing submissions to use form_id.");
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixFormCircularRefs extends Command
{
    protected $signature = 'forms:fix-circular';
    protected $description = 'Show all forms and fix circular parent_id references';

    public function handle()
    {
        // Show all forms
        $rows = DB::select('SELECT id, parent_id, title, is_category FROM forms ORDER BY id');
        
        $this->table(['ID', 'Parent ID', 'Category?', 'Title'], array_map(function ($r) {
            return [$r->id, $r->parent_id ?? 'NULL', $r->is_category, $r->title];
        }, $rows));

        // Fix self-referencing (parent_id = id)
        $fixed = DB::update('UPDATE forms SET parent_id = NULL WHERE parent_id = id');
        $this->info("Fixed self-references: {$fixed}");

        return 0;
    }
}

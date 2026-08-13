<?php

namespace App\Console\Commands;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Services\GoogleFormsSyncService;
use Google\Service\Forms;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class SyncFormsStructure extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forms:sync-structure';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Synchronize the database forms structure (categories and leaves) from the registered Google Forms API';

    /**
     * Execute the console command.
     */
    public function handle(GoogleFormsSyncService $syncService)
    {
        $this->info('Starting forms structure synchronization...');

        // Bypass time limits
        set_time_limit(0);
        ini_set('max_execution_time', 0);

        // Access Google Forms service from SyncService
        $ref = new ReflectionClass($syncService);
        $prop = $ref->getProperty('formsService');
        $prop->setAccessible(true);
        $formsService = $prop->getValue($syncService);

        if (! $formsService) {
            $this->error('Google Forms Service is not configured properly in GoogleFormsSyncService.');

            return 1;
        }

        // Load forms configuration from pandu_config.json
        $configPath = storage_path('app/pandu_config.json');
        if (! File::exists($configPath)) {
            $this->error('pandu_config.json not found in storage/app.');

            return 1;
        }

        $config = json_decode(File::get($configPath), true);
        $googleForms = $config['forms'] ?? [];

        if (empty($googleForms)) {
            $this->warn('No forms registered in pandu_config.json.');

            return 0;
        }

        $syncedFormIds = [];

        foreach ($googleForms as $gf) {
            $this->info("Fetching structure for Google Form: {$gf['slug']} (ID: {$gf['id']})");

            try {
                $googleForm = $formsService->forms->get($gf['id']);
                $items = $googleForm->getItems();
                if (! $items) {
                    $this->warn("No items found in form: {$gf['slug']}");

                    continue;
                }

                $this->syncGoogleFormStructure($items, $gf['slug'], $syncedFormIds);

            } catch (\Exception $e) {
                $this->error("Failed to sync form {$gf['slug']}: ".$e->getMessage());
            }
        }

        // Cleanup obsolete forms (leaves/subcategories that have 0 submissions and were not synced)
        $this->cleanupObsoleteForms($syncedFormIds);

        $this->info('Forms structure synchronization completed successfully.');

        return 0;
    }

    /**
     * Sync structure of a specific Google Form into the database
     */
    protected function syncGoogleFormStructure($items, $slug, &$syncedFormIds)
    {
        // 1. Resolve Parent/Root Forms in the DB
        $dbForms = Form::where('slug', $slug)->get();

        if ($dbForms->isEmpty()) {
            // Get the title from the first form item or a default
            $formTitle = Str::headline(str_replace('-', ' ', $slug));
            $this->info("Parent form not found for slug {$slug}. Creating it automatically: {$formTitle}");

            $newForm = Form::create([
                'slug' => $slug,
                'title' => $formTitle,
                'is_category' => true,
                'order' => 0,
            ]);
            $dbForms = collect([$newForm]);
        }

        foreach ($dbForms as $dbForm) {
            $syncedFormIds[] = $dbForm->id;

            // Update checklist items (options) in the parent itself if it's a leaf
            $checklistOptions = $this->getChecklistOptions($items);
            if (! empty($checklistOptions)) {
                $dbForm->update(['options' => $checklistOptions]);
            }

            // Define the structure of child/leaf categories
            $structure = [];

            if ($slug === 'fix-ladder') {
                $structure['Cimex'] = $this->getOptionsAfterPageBreak($items, 'Cimex');
                $structure['Existing'] = $this->getOptionsAfterPageBreak($items, 'Existing');
                $structure['Gede'] = $this->getOptionsAfterPageBreak($items, 'Gede');
                $structure['Sumber'] = $this->getOptionsAfterPageBreak($items, 'Sumber');
            } elseif ($slug === 'mobile-ladder') {
                $structure['Cimex'] = $this->getOptionsAfterPageBreak($items, 'Cimex');
                $structure['Existing'] = $this->getOptionsAfterPageBreak($items, 'Existing');
                $structure['Gede'] = $this->getOptionsAfterPageBreak($items, 'Gedel')
                    ?: $this->getOptionsAfterPageBreak($items, 'Gede');
            } elseif ($slug === 'lifeline') {
                $options = $this->getFirstChoiceQuestionOptions($items);
                foreach ($options as $opt) {
                    $cat = 'Existing';
                    if (Str::startsWith($opt, ['LLC', 'ABC'])) {
                        $cat = 'Cimex';
                    } elseif (Str::startsWith($opt, ['LLG', 'ABG', 'LL '])) {
                        $cat = 'Gede';
                    } elseif (Str::startsWith($opt, ['LLS', 'ABS'])) {
                        $cat = 'Sumber';
                    }
                    $structure[$cat][] = $opt;
                }
            } elseif ($slug === 'barrier-dan-self-door-closer') {
                $structure['Barrier & Railing (BR)'] = $this->getOptionsAfterPageBreak($items, 'Barrier & Railing')
                    ?: $this->getOptionsAfterPageBreak($items, 'BARRIER & RAILING');
                $structure['Self Door Closer (SDR)'] = $this->getOptionsAfterPageBreak($items, 'SELF DOOR CLOSER');
            } elseif ($slug === 'full-body-harness') {
                $options = $this->getFirstChoiceQuestionOptions($items);
                foreach ($options as $opt) {
                    $cat = 'Existing';
                    if (Str::contains(strtolower($opt), 'cimex')) {
                        $cat = 'Cimex';
                    } elseif (Str::contains(strtolower($opt), 'gede')) {
                        $cat = 'Gede';
                    }
                    $structure[$cat][] = $opt;
                }
            } elseif ($slug === 'pintu-geser') {
                $options = $this->getOptionsAfterPageBreak($items, 'Nomor Pintu Geser')
                    ?: $this->getOptionsAfterPageBreak($items, 'Nomor pintu geser')
                    ?: $this->getFirstChoiceQuestionOptions($items);
                foreach ($options as $opt) {
                    $cat = 'Existing';
                    if (Str::contains(strtolower($opt), 'cimex')) {
                        $cat = 'Cimex';
                    } elseif (Str::contains(strtolower($opt), 'gede') || Str::contains(strtolower($opt), 'l5')) {
                        $cat = 'Gede';
                    }
                    $structure[$cat][] = $opt;
                }
            } elseif ($slug === 'rolling-door') {
                $structure['Conventional Rolling Door'] = $this->getOptionsAfterPageBreak($items, 'Nomor Conventional Rolling Door')
                    ?: $this->getOptionsAfterPageBreak($items, 'Nomer Conventional Rolling Door');
                $structure['Speed Rolling Door'] = $this->getOptionsAfterPageBreak($items, 'Nomor Speed Rolling Door')
                    ?: $this->getOptionsAfterPageBreak($items, 'Nomer Speed Rolling Door');

                // Override the incorrect names from Google Form to correct physical names
                if (!empty($structure['Conventional Rolling Door'])) {
                    foreach ($structure['Conventional Rolling Door'] as $key => $opt) {
                        if (str_contains(strtolower($opt), 'rd 22')) {
                            $structure['Conventional Rolling Door'][$key] = 'RD 22 Storage preform Cimex 2';
                        } elseif (str_contains(strtolower($opt), 'rd 23')) {
                            $structure['Conventional Rolling Door'][$key] = 'RD 23 Storage Preform Gede 1';
                        }
                    }
                }

                $conventionalOptions = $this->getChecklistOptionsAfterPageBreak($items, 'Checklist Conventional Rolling door');
                $speedOptions = $this->getChecklistOptionsAfterPageBreak($items, 'Checklist Speed Rolling door');

                $dbForm->update(['is_category' => true]);
                $syncedFormIds[] = $dbForm->id;

                $this->syncStructureToDb($dbForm, ['Conventional Rolling Door' => $structure['Conventional Rolling Door']], $conventionalOptions, $syncedFormIds);
                $this->syncStructureToDb($dbForm, ['Speed Rolling Door' => $structure['Speed Rolling Door']], $speedOptions, $syncedFormIds);

                continue;
            } elseif ($slug === 'skylife-protection') {
                $options = $this->getFirstChoiceQuestionOptions($items);
                foreach ($options as $opt) {
                    $structure[''][] = $opt;
                }
            } elseif ($slug === 'work-at-height') {
                // Sibling routing of work-at-height options
                // $this->routeWorkAtHeightOptions($items, $syncedFormIds);

                continue;
            }

            // If structure is still empty after hardcoded checks, try to auto-discover
            if (empty($structure) && $slug !== 'work-at-height') {
                $options = $this->getFirstChoiceQuestionOptions($items);
                if (! empty($options)) {
                    foreach ($options as $opt) {
                        $structure[''][] = $opt;
                    }
                }
            }

            // Sync the parsed structure to DB under this form
            if (! empty($structure)) {
                $dbForm->update(['is_category' => true]);
                $this->syncStructureToDb($dbForm, $structure, $checklistOptions, $syncedFormIds);
            } else {
                // If it has no categories or sub-options, make it a leaf itself
                // (Unless it has parent/child legacy categories we want to preserve like jack-hammer / litter)
                $legacyParents = ['jack-hammer', 'litter'];
                if (! in_array($dbForm->slug, $legacyParents)) {
                    $dbForm->update(['is_category' => false]);
                }
            }
        }
    }

    /**
     * Route options from the multi-form Work at Height checklist into their sibling forms
     */
    protected function routeWorkAtHeightOptions($items, &$syncedFormIds)
    {
        $mappings = [
            'FIX LADDER CIMEX (FLC)' => ['parent_slug' => 'fix-ladder', 'cat_name' => 'Cimex'],
            'FIX LADDER EXISTING (FLE)' => ['parent_slug' => 'fix-ladder', 'cat_name' => 'Existing'],
            'FIX LADDER GEDE (FLG)' => ['parent_slug' => 'fix-ladder', 'cat_name' => 'Gede'],
            'FIX LADDER SUMBER & RWH (FLS)' => ['parent_slug' => 'fix-ladder', 'cat_name' => 'Sumber'],
            'MOBILE LADDER CIMEX (MLC)' => ['parent_slug' => 'mobile-ladder', 'cat_name' => 'Cimex'],
            'MOBILE LADDER EXISTING (MLE)' => ['parent_slug' => 'mobile-ladder', 'cat_name' => 'Existing'],
            'MOBILE LEDDER  GEDE (MLG)' => ['parent_slug' => 'mobile-ladder', 'cat_name' => 'Gede'],
            'FULL BODY HARNESS (FBH) EXISTING' => ['parent_slug' => 'full-body-harness', 'cat_name' => 'Existing'],
            'FULL BODY HARNESS (FBH) GEDE' => ['parent_slug' => 'full-body-harness', 'cat_name' => 'Gede'],
            'FULL BODY HARNESS (FBH) CIMEX' => ['parent_slug' => 'full-body-harness', 'cat_name' => 'Cimex'],
            'LIFELINE GEDE (LLG) & ANCOR BOLT GEDE(ABG)' => ['parent_slug' => 'lifeline', 'cat_name' => 'Gede'],
            'LIFELINE EXISTING (LLE) & ANCHOR BOLT EXISTING (ABE)' => ['parent_slug' => 'lifeline', 'cat_name' => 'Existing'],
            'LIFELINE CIMEX (LLC) & ANCHOR BOLT (ABC)' => ['parent_slug' => 'lifeline', 'cat_name' => 'Cimex'],
            'LIFELINE SUMBER (LLS) & ANCHOR BOLT SUMBER (ABS)' => ['parent_slug' => 'lifeline', 'cat_name' => 'Sumber'],
            'SKYLIGHT PROTECTION (SP)' => ['parent_slug' => 'skylife-protection', 'cat_name' => ''],
            'BARRIER & RAILING (BR)' => ['parent_slug' => 'barrier-dan-self-door-closer', 'cat_name' => 'Barrier & Railing (BR)'],
            'SELF DOOR CLOSER (SDR)' => ['parent_slug' => 'barrier-dan-self-door-closer', 'cat_name' => 'Self Door Closer (SDR)'],
        ];

        foreach ($mappings as $pbTitle => $map) {
            $options = $this->getOptionsAfterPageBreak($items, $pbTitle);
            if (empty($options)) {
                continue;
            }

            $parents = Form::where('slug', $map['parent_slug'])->get();
            foreach ($parents as $parentForm) {
                $structure = [$map['cat_name'] => $options];
                $this->syncStructureToDb($parentForm, $structure, [], $syncedFormIds);
            }
        }
    }

    /**
     * Update/Insert category and leaf forms under a parent Form
     */
    protected function syncStructureToDb(Form $parentForm, array $structure, array $defaultOptions, &$syncedFormIds)
    {
        $catOrder = 1;
        foreach ($structure as $catName => $leafOptions) {
            if ($catName !== '') {
                // Find or create Category Form
                $catSlug = $parentForm->slug.'-'.Str::slug($catName);
                $catForm = Form::updateOrCreate(
                    ['parent_id' => $parentForm->id, 'slug' => $catSlug],
                    ['title' => Str::startsWith($catName, 'Area') ? $catName : 'Area '.$catName, 'is_category' => true, 'order' => $catOrder++]
                );
                $syncedFormIds[] = $catForm->id;
                $targetParentId = $catForm->id;
            } else {
                $targetParentId = $parentForm->id;
            }

            $leafOrder = 1;
            foreach ($leafOptions as $leafOpt) {
                $leafSlug = Str::slug($leafOpt);
                if (empty($leafSlug)) {
                    continue;
                }

                // Find or create Leaf Form
                $leafForm = Form::updateOrCreate(
                    ['parent_id' => $targetParentId, 'slug' => $leafSlug],
                    [
                        'title' => $leafOpt,
                        'is_category' => false,
                        'options' => $defaultOptions ?: null,
                        'order' => $leafOrder++,
                    ]
                );
                $syncedFormIds[] = $leafForm->id;
            }
        }
    }

    /**
     * Cleanup forms that no longer exist and have 0 submissions
     */
    protected function cleanupObsoleteForms(array $syncedFormIds)
    {
        // Get all forms except root categories
        $forms = Form::whereNotNull('parent_id')->get();

        foreach ($forms as $form) {
            // Guard: Never delete forms that belong to protected trees!
            if ($this->isProtectedFromCleanup($form)) {
                continue;
            }

            if (! in_array($form->id, $syncedFormIds)) {
                // Verify if it has submissions
                $submissionsCount = FormSubmission::where('form_id', $form->id)->count();
                if ($submissionsCount === 0) {
                    $this->info("Deleting obsolete form: {$form->title} (Slug: {$form->slug})");
                    $form->delete();
                } else {
                    $this->warn("Cannot delete obsolete form because it has submissions: {$form->title} (ID: {$form->id})");
                }
            }
        }
    }

    private function isProtectedFromCleanup(Form $form): bool
    {
        $protectedRoots = [1, 672, 677, 679]; // Work at height, Fix Ladder, Mobile Ladder, Skylight Protection
        $current = $form;
        while ($current) {
            if (in_array($current->id, $protectedRoots)) {
                return true;
            }
            $current = $current->parent;
        }
        return false;
    }

    // Helper functions

    protected function getOptionsAfterPageBreak($items, $pageBreakTitle)
    {
        $options = [];
        $found = false;
        foreach ($items as $item) {
            $title = $item->getTitle();
            if ($item->getPageBreakItem()) {
                if ($title && Str::contains(strtolower($title), strtolower($pageBreakTitle))) {
                    $found = true;

                    continue;
                } else {
                    $found = false;
                }
            }
            if ($found && $item->getQuestionItem()) {
                $q = $item->getQuestionItem()->getQuestion();
                $choiceQ = $q->getChoiceQuestion();
                if ($choiceQ) {
                    foreach ($choiceQ->getOptions() as $opt) {
                        $options[] = $opt->getValue();
                    }
                    break;
                }
            }
        }

        return $options;
    }

    protected function getFirstChoiceQuestionOptions($items)
    {
        foreach ($items as $item) {
            if ($item->getQuestionItem()) {
                $q = $item->getQuestionItem()->getQuestion();
                $choiceQ = $q->getChoiceQuestion();
                if ($choiceQ) {
                    $options = [];
                    $isChecklist = false;
                    foreach ($choiceQ->getOptions() as $opt) {
                        $val = strtolower($opt->getValue());
                        if (Str::contains($val, 'sesuai') || Str::contains($val, 'baik') || Str::contains($val, 'rusak')) {
                            $isChecklist = true;
                            break;
                        }
                        $options[] = $opt->getValue();
                    }
                    if (! $isChecklist && count($options) > 0) {
                        return $options;
                    }
                }
            }
        }

        return [];
    }

    protected function getChecklistOptions($items)
    {
        $options = [];
        foreach ($items as $item) {
            if ($item->getQuestionItem()) {
                $title = $item->getTitle();
                $q = $item->getQuestionItem()->getQuestion();
                $choiceQ = $q->getChoiceQuestion();
                if ($choiceQ) {
                    $opts = [];
                    foreach ($choiceQ->getOptions() as $o) {
                        $opts[] = strtolower(trim($o->getValue()));
                    }
                    // Checklist questions usually have options like "Sesuai Standard", "Tidak Sesuai Standard"
                    if (in_array('sesuai standard', $opts) || in_array('tidak sesuai standard', $opts)) {
                        $options[] = $title;
                    }
                }
            }
        }

        return $options;
    }

    protected function getChecklistOptionsAfterPageBreak($items, $pageBreakTitle)
    {
        $options = [];
        $found = false;
        foreach ($items as $item) {
            $title = $item->getTitle();
            if ($item->getPageBreakItem()) {
                if ($title && Str::contains(strtolower($title), strtolower($pageBreakTitle))) {
                    $found = true;
                    continue;
                } else {
                    $found = false;
                }
            }
            if ($found && $item->getQuestionItem()) {
                $q = $item->getQuestionItem()->getQuestion();
                $choiceQ = $q->getChoiceQuestion();
                if ($choiceQ) {
                    $opts = [];
                    foreach ($choiceQ->getOptions() as $o) {
                        $opts[] = strtolower(trim($o->getValue()));
                    }
                    if (in_array('sesuai standard', $opts) || in_array('tidak sesuai standard', $opts)) {
                        $options[] = $title;
                    }
                }
            }
        }
        return $options;
    }
}

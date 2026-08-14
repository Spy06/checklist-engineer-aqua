<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Services\GoogleFormsSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ReflectionClass;

class PanduAdminController extends Controller
{
    /**
     * Tampilkan halaman login admin
     */
    public function showLogin()
    {
        if (session('pandu_admin_logged_in')) {
            return redirect()->route('pandu-admin.dashboard');
        }

        return view('pandu-admin.login');
    }

    /**
     * Proses login
     */
    public function processLogin(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $envUsername = env('PANDU_ADMIN_USERNAME');
        $envPassword = env('PANDU_ADMIN_PASSWORD');

        if ($request->username === $envUsername && $request->password === $envPassword) {
            session(['pandu_admin_logged_in' => true]);

            return redirect()->route('pandu-admin.dashboard')->with('success', 'Berhasil login ke Pandu Admin Panel.');
        }

        return back()->with('error', 'Username atau password salah.');
    }

    /**
     * Tampilkan dashboard admin (konfigurasi Google API & Forms)
     */
    public function index()
    {
        $config = $this->readConfig();

        // Load root-level category forms from DB for the "parent" dropdown
        $rootForms = Form::whereNull('parent_id')
            ->orderBy('title')
            ->get(['id', 'slug', 'title']);

        return view('pandu-admin.dashboard', compact('config', 'rootForms'));
    }

    /**
     * Simpan konfigurasi
     */
    public function updateConfig(Request $request)
    {
        $request->validate([
            'google_api_base64' => 'nullable|string',
            'forms'             => 'nullable|array',
            'forms.*.id'        => 'required|string',
            'forms.*.slug'      => 'required|string',
        ]);

        // Filter and re-index forms
        $forms = [];
        if ($request->has('forms') && is_array($request->forms)) {
            foreach ($request->forms as $f) {
                if (! empty($f['id']) && ! empty($f['slug'])) {
                    $forms[] = [
                        'id'          => trim($f['id']),
                        'slug'        => Str::slug(trim($f['slug'])),
                        'name'        => $f['name'] ?? '',
                        'parent_slug' => $f['parent_slug'] ?? '',
                    ];
                }
            }
        }

        $config = [
            'google_api_base64' => $request->input('google_api_base64', ''),
            'forms'             => $forms,
        ];

        try {
            $path = storage_path('app/pandu_config.json');
            File::put($path, json_encode($config, JSON_PRETTY_PRINT));

            // Hapus form dari database (dan cache) jika sudah tidak ada di config
            $configSlugs = array_column($forms, 'slug');
            $deletedForms = Form::whereNull('parent_id')
                ->whereNotIn('slug', $configSlugs)
                ->get();
            
            if ($deletedForms->count() > 0) {
                // Delete cascaded children in DB automatically (onDelete cascade)
                Form::whereIn('id', $deletedForms->pluck('id'))->delete();

                // Clear live submission cache to remove orphaned forms data
                Artisan::call('cache:clear');
            }

            return back()->with('success', 'Konfigurasi berhasil disimpan. Klik "Preview & Sinkronkan" pada form baru untuk melanjutkan.');
        } catch (\Exception $e) {
            Log::error('Failed to save pandu config: '.$e->getMessage());

            return back()->with('error', 'Gagal menyimpan konfigurasi.');
        }
    }

    /**
     * Preview form structure + recent responses from Google Forms API (before finalizing)
     * GET /pandu-admin/form/preview?slug=xxx
     */
    public function previewForm(Request $request, GoogleFormsSyncService $syncService)
    {
        $slug = $request->query('slug');
        if (! $slug) {
            return back()->with('error', 'Slug form tidak ditemukan.');
        }

        $config     = $this->readConfig();
        $formConfig = collect($config['forms'] ?? [])->firstWhere('slug', $slug);

        // Allow previewing even before config is saved — use google_form_id from URL directly
        $googleFormId = $request->query('google_form_id')
            ?? ($formConfig['id'] ?? null);

        if (! $googleFormId) {
            return back()->with('error', "Google Form ID untuk slug '{$slug}' tidak ditemukan. Pastikan Anda mengisi Google Form ID sebelum klik Preview.");
        }

        // Build a minimal formConfig if not yet saved
        if (! $formConfig) {
            $formConfig = [
                'id'          => $googleFormId,
                'slug'        => $slug,
                'name'        => '',
                'parent_slug' => '',
            ];
        }

        // Access raw Google Forms service via reflection
        $ref  = new ReflectionClass($syncService);
        $prop = $ref->getProperty('formsService');
        $prop->setAccessible(true);
        $formsService = $prop->getValue($syncService);

        if (! $formsService) {
            return back()->with('error', 'Kredensial Google API belum dikonfigurasi dengan benar.');
        }

        try {
            // 1. Fetch raw form structure (questions / items)
            $googleForm = $formsService->forms->get($googleFormId);
            $rawItems   = $googleForm->getItems() ?? [];

            // Build a human-readable structure tree
            $structureTree = [];
            foreach ($rawItems as $item) {
                $title = $item->getTitle() ?? '(tanpa judul)';
                $type  = 'text';
                $opts  = [];

                if ($item->getPageBreakItem()) {
                    $type = 'section';
                } elseif ($item->getQuestionItem()) {
                    $q       = $item->getQuestionItem()->getQuestion();
                    $choiceQ = $q ? $q->getChoiceQuestion() : null;
                    if ($choiceQ) {
                        $opts = array_map(fn ($o) => $o->getValue(), $choiceQ->getOptions());
                        $isChecklistOpts = array_filter($opts, fn ($o) => Str::contains(strtolower($o), ['sesuai', 'baik', 'rusak']));
                        $type = (count($opts) <= 4 && count($isChecklistOpts) > 0) ? 'checklist' : 'choice';
                    }
                }

                $structureTree[] = [
                    'title' => $title,
                    'type'  => $type,
                    'opts'  => $opts,
                ];
            }

            // 2. Fetch last 20 responses
            $responseList = $formsService->forms_responses->listFormsResponses($googleFormId, ['pageSize' => 20]);
            $rawResponses = $responseList->getResponses() ?? [];

            $previewResponses = [];
            foreach ($rawResponses as $resp) {
                $answers   = $resp->getAnswers() ?? [];
                $parsedRow = [];
                foreach ($answers as $answer) {
                    $textAnswers = $answer->getTextAnswers();
                    $value       = '';
                    if ($textAnswers && count($textAnswers->getAnswers()) > 0) {
                        $value = $textAnswers->getAnswers()[0]->getValue();
                    }
                    $parsedRow[] = $value;
                }
                $previewResponses[] = [
                    'response_id'  => $resp->getResponseId(),
                    'submitted_at' => $resp->getLastSubmittedTime(),
                    'answers'      => $parsedRow,
                ];
            }

            // 3. Load existing DB structure for this slug (if already synced before)
            $existingForm = Form::where('slug', $slug)->with(['children.children'])->first();

            // 4. Load root forms for dropdown in finalize form
            $rootForms = Form::whereNull('parent_id')->orderBy('title')->get(['id', 'slug', 'title']);

        } catch (\Exception $e) {
            Log::error("Preview failed for slug {$slug}: ".$e->getMessage());

            return back()->with('error', 'Gagal mengambil data dari Google Forms: '.$e->getMessage());
        }

        return view('pandu-admin.form-preview', compact(
            'slug', 'formConfig', 'structureTree', 'previewResponses', 'existingForm', 'rootForms'
        ));
    }

    /**
     * Run forms:sync-structure --slug=xxx (AJAX endpoint)
     * POST /pandu-admin/form/sync
     */
    public function syncNewForm(Request $request)
    {
        $request->validate(['slug' => 'required|string']);
        $slug         = Str::slug($request->slug);
        $googleFormId = trim($request->input('google_form_id', ''));

        try {
            $args = ['--slug' => $slug];
            if ($googleFormId) {
                $args['--id'] = $googleFormId;
            }

            $exitCode = Artisan::call('forms:sync-structure', $args);
            $output   = Artisan::output();

            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => "Struktur form '{$slug}' berhasil disinkronkan ke database.",
                    'output'  => $output,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => "Sinkronisasi form '{$slug}' gagal.",
                'output'  => $output,
            ], 500);

        } catch (\Exception $e) {
            Log::error("Sync new form failed for slug {$slug}: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Finalize: set parent in DB, rename title, redirect to matrix
     * POST /pandu-admin/form/finalize
     */
    public function finalizeForm(Request $request)
    {
        $request->validate([
            'slug'        => 'required|string',
            'name'        => 'required|string',
            'parent_slug' => 'nullable|string',
        ]);

        $slug       = Str::slug($request->slug);
        $name       = trim($request->name);
        $parentSlug = $request->parent_slug ? Str::slug($request->parent_slug) : null;

        try {
            $form = Form::where('slug', $slug)->first();
            if (! $form) {
                return back()->with('error', "Form '{$slug}' belum disinkronkan. Jalankan sinkronisasi terlebih dahulu.");
            }

            $form->title = $name;

            if ($parentSlug) {
                $parentForm = Form::where('slug', $parentSlug)->whereNull('parent_id')->first();
                if ($parentForm) {
                    $form->parent_id = $parentForm->id;
                    $form->order     = ($parentForm->children()->max('order') ?? 0) + 1;
                }
            }

            $form->save();

            return redirect()->route('home')->with('success', "Form '{$name}' berhasil difinalisasi dan kini tampil di Matriks!");

        } catch (\Exception $e) {
            Log::error("Finalize form failed for slug {$slug}: ".$e->getMessage());

            return back()->with('error', 'Gagal memfinalisasi form: '.$e->getMessage());
        }
    }

    /**
     * Logout
     */
    public function logout()
    {
        session()->forget('pandu_admin_logged_in');

        return redirect()->route('pandu-admin.login');
    }

    /**
     * Helper to read JSON config
     */
    private function readConfig()
    {
        $path = storage_path('app/pandu_config.json');
        if (File::exists($path)) {
            $content = File::get($path);
            $data    = json_decode($content, true);
            if (is_array($data)) {
                return $data;
            }
        }

        return [
            'google_api_base64' => '',
            'forms'             => [],
        ];
    }
}

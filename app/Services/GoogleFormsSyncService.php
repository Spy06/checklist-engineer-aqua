<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Forms;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleFormsSyncService
{
    protected $client;

    protected $formsService;

    public function __construct()
    {

        $this->client = new Client;
        $this->client->setApplicationName('Checklist Engineer Sync');

        // Explicit HTTP timeout: 20s connect + 20s read.
        // This ensures the Google API call always fails gracefully
        // before PHP's 30s max_execution_time is hit.
        $this->client->setHttpClient(new \GuzzleHttp\Client([
            'timeout' => 20,
            'connect_timeout' => 10,
        ]));

        $base64Creds = '';
        $configPath = storage_path('app/pandu_config.json');
        if (File::exists($configPath)) {
            $config = json_decode(File::get($configPath), true);
            $base64Creds = $config['google_api_base64'] ?? '';
        }

        // Fallback to original .env config if pandu config is empty
        if (empty($base64Creds)) {
            $base64Creds = config('services.google.base64_credentials');
        }

        $credentialsPath = config('services.google.credentials');

        if ($base64Creds) {
            $decoded = json_decode(base64_decode($base64Creds), true);
            if ($decoded) {
                $this->client->setAuthConfig($decoded);
                $this->client->addScope(Forms::FORMS_RESPONSES_READONLY);
                $this->client->addScope(Forms::FORMS_BODY_READONLY);
                $this->formsService = new Forms($this->client);

                return;
            } else {
                Log::error('Google credentials base64 is invalid.');
            }
        }

        if (file_exists($credentialsPath)) {
            $this->client->setAuthConfig($credentialsPath);
            $this->client->addScope(Forms::FORMS_RESPONSES_READONLY);
            $this->client->addScope(Forms::FORMS_BODY_READONLY);
            $this->formsService = new Forms($this->client);
        } else {
            Log::error('Google credentials file not found at: '.$credentialsPath);
        }
    }

    public function syncForm($googleFormId, $internalFormSlug = null)
    {
        if (! $this->formsService) {
            return [
                'status' => 'error',
                'message' => 'Google Client not configured properly. Check credentials.',
            ];
        }

        try {
            // 1. Get Form Metadata (to map Question IDs to Titles)
            $form = $this->formsService->forms->get($googleFormId);
            $questionMap = $this->buildQuestionMap($form);
            $formTitle = $form->getInfo()->getTitle();

            // 2. Get Responses
            $responses = $this->formsService->forms_responses->listFormsResponses($googleFormId);
            $totalSynced = 0;

            // Format responses like CSV array to reuse logic
            $formattedRows = [];

            foreach ($responses->getResponses() as $response) {
                $row = [];
                $row['Cap waktu'] = $response->getCreateTime();

                $answers = $response->getAnswers();
                if ($answers) {
                    foreach ($answers as $questionId => $answer) {
                        $questionTitle = $questionMap[$questionId] ?? 'Unknown Question';

                        // Extract text value
                        $textAnswers = $answer->getTextAnswers();
                        $value = '';
                        if ($textAnswers && count($textAnswers->getAnswers()) > 0) {
                            $value = $textAnswers->getAnswers()[0]->getValue();
                        }

                        if (array_key_exists($questionTitle, $row)) {
                            $existingCatatan = $row['Catatan'] ?? '';
                            $row['Catatan'] = trim($existingCatatan . "\n" . $questionTitle . ' (Catatan): ' . $value);
                        } else {
                            $row[$questionTitle] = $value;
                        }
                    }
                }
                $formattedRows[] = $row;
            }

            // 3. Process formatted rows
            Log::info('Google Forms API Rows: '.json_encode($formattedRows, JSON_PRETTY_PRINT));
            $totalSynced = $this->processRows($formattedRows, $formTitle, $internalFormSlug);

            return [
                'status' => 'success',
                'message' => "Successfully synced {$totalSynced} responses from '{$formTitle}'",
                'synced_count' => $totalSynced,
            ];

        } catch (\Exception $e) {
            Log::error('Google Forms Sync Error: '.$e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getLiveResponses($googleFormId, $internalFormSlug = null)
    {
        if (! $this->formsService) {
            return collect();
        }

        try {
            $form = $this->formsService->forms->get($googleFormId);
            $questionMap = $this->buildQuestionMap($form);
            $formTitle = $form->getInfo()->getTitle();

            $responses = [];
            $pageToken = null;

            do {
                $optParams = [];
                if ($pageToken) {
                    $optParams['pageToken'] = $pageToken;
                }

                $response = $this->formsService->forms_responses->listFormsResponses($googleFormId, $optParams);
                $pageResponses = $response->getResponses();

                if ($pageResponses) {
                    $responses = array_merge($responses, $pageResponses);
                }

                $pageToken = $response->getNextPageToken();
            } while ($pageToken);

            $formattedRows = [];

            foreach ($responses as $responseItem) {
                $row = [
                    'Cap waktu' => $responseItem->getCreateTime(),
                ];

                $answers = $responseItem->getAnswers();
                if ($answers) {
                    foreach ($answers as $questionId => $answer) {
                        $questionTitle = $questionMap[$questionId] ?? 'Unknown Question';
                        $textAnswers = $answer->getTextAnswers();
                        $value = '';
                        if ($textAnswers && count($textAnswers->getAnswers()) > 0) {
                            $value = $textAnswers->getAnswers()[0]->getValue();
                        }

                        // Handle duplicate question titles (e.g. second "Kebersihan" which is actually a notes field)
                        if (array_key_exists($questionTitle, $row)) {
                            // Append to Catatan so it becomes a responder note
                            $existingCatatan = $row['Catatan'] ?? '';
                            $row['Catatan'] = trim($existingCatatan . "\n" . $questionTitle . ' (Catatan): ' . $value);
                        } else {
                            $row[$questionTitle] = $value;
                        }
                    }
                }
                $formattedRows[] = $row;
            }

            return $this->processLiveRows($formattedRows, $formTitle, $internalFormSlug);
        } catch (\Exception $e) {
            Log::error('Google Forms Live Fetch Error: '.$e->getMessage());

            return collect();
        }
    }

    protected function buildQuestionMap($form)
    {
        $map = [];
        $items = $form->getItems();

        if (! $items) {
            return $map;
        }

        $lastTitle = null;

        foreach ($items as $item) {
            $title = $item->getTitle();
            if ($title) {
                $lastTitle = $title;
            }

            $questionItem = $item->getQuestionItem();
            if ($questionItem) {
                $question = $questionItem->getQuestion();
                if ($question) {
                    $resolvedTitle = $title;
                    if (!$resolvedTitle) {
                        $avoidInherit = ['tanggal pemeriksaan', 'nama pemeriksa', 'nama pemeriksaan', 'cap waktu'];
                        if ($lastTitle && !in_array(strtolower(trim($lastTitle)), $avoidInherit)) {
                            $resolvedTitle = $lastTitle;
                        } else {
                            $resolvedTitle = 'Pilihan';
                        }
                    }
                    $map[$question->getQuestionId()] = $resolvedTitle;
                }
            } else {
                // sometimes questions are in groups
                $questionGroupItem = $item->getQuestionGroupItem();
                if ($questionGroupItem && $questionGroupItem->getQuestions()) {
                    foreach ($questionGroupItem->getQuestions() as $q) {
                        $resolvedTitle = $title;
                        if (!$resolvedTitle) {
                            $avoidInherit = ['tanggal pemeriksaan', 'nama pemeriksa', 'nama pemeriksaan', 'cap waktu'];
                            if ($lastTitle && !in_array(strtolower(trim($lastTitle)), $avoidInherit)) {
                                $resolvedTitle = $lastTitle;
                            } else {
                                $resolvedTitle = 'Pilihan';
                            }
                        }
                        $map[$q->getQuestionId()] = $resolvedTitle;
                    }
                }
            }
        }

        return $map;
    }

    protected function processRows($rows, $formTitle, $internalFormSlug = null)
    {
        $totalInserted = 0;

        foreach ($rows as $row) {
            // Reusing logic from ImportCsvSubmissions
            $userName = $row['Nama pemeriksaan'] ?? ($row['Nama pemeriksa'] ?? 'Unknown User');
            $dateStr = $row['Tanggal pemeriksaan'] ?? ($row['Cap waktu'] ?? null);
            $notes = $row['Catatan'] ?? null;

            $date = Carbon::now()->format('Y-m-d');
            if ($dateStr) {
                try {
                    $date = Carbon::parse($dateStr)->format('Y-m-d');
                } catch (\Exception $e) {
                }
            }

            // Get or create User
            $userKey = strtolower(trim($userName));
            $user = User::where('name', $userName)->first();
            if (! $user) {
                $user = User::create([
                    'name' => $userName,
                    'username' => Str::slug($userName).'_'.uniqid(),
                    'email' => Str::slug($userName).'_'.uniqid().'@example.com',
                    'password' => bcrypt('password'),
                    'role' => 'responden',
                ]);
            }
            $userId = $user->id;

            // Mapping Google Form Title to our Form logic
            $leafForm = null;
            if ($internalFormSlug) {
                $leafForm = $this->resolveLeafForm($internalFormSlug, $row);
            }

            if (! $leafForm) {
                continue;
            }

            // Check if already synced (prevent duplicate by user, form, and date)
            // Ideally, we'd have a google_response_id, but for now we rely on date and user
            $exists = FormSubmission::where('user_id', $userId)
                ->where('form_id', $leafForm->id)
                ->where('date', $date)
                ->exists();

            if ($exists) {
                continue; // Skip if already imported for that date
            }

            // Process Options
            $metadataCols = ['Cap waktu', 'Nama pemeriksaan', 'Nama pemeriksa', 'Tanggal pemeriksaan', 'Area Pemeriksaan', 'Cimex', 'Existing', 'Gede', 'Sumber', 'Catatan', 'Lokasi Pemeriksaan', 'Nomer Conventional Rolling Door', 'Nomer Speed Rolling Door', 'Nomor Conventional Rolling Door', 'Nomor Speed Rolling Door', 'Jenis Rolling Door', 'Nomor pintu geser', 'Nomer pintu geser', 'Skylight Protection (SP)', 'Area storage gas', 'Pilihan', 'Pilihan Pintu', ''];
            $metadataColsLower = array_map(fn($c) => strtolower(trim($c)), $metadataCols);

            $hasFailure = false;
            $failureNotes = [];
            $jumlahNotes = [];
            foreach ($row as $colName => $val) {
                if (in_array(strtolower(trim($colName)), $metadataColsLower)) {
                    continue;
                }
                if (str_contains(strtolower($colName), 'jumlah')) {
                    if (!empty(trim($val))) {
                        $jumlahNotes[] = "$colName: $val";
                    }
                    continue;
                }

                $valLower = strtolower(trim($val));
                if (empty($valLower) || $valLower == 'sesuai standard' || $valLower == 'sesuai standar' || $valLower == 'ok' || $valLower == 'kondisi baik') {
                    continue;
                } else {
                    $hasFailure = true;
                    $failureNotes[] = "$colName: $val";
                }
            }

            $options = $leafForm->options ?? [];
            $answers = [];

            if (! $hasFailure) {
                $answers = $options;
            } else {
                $answers = $options;
                $notes = trim(($notes ?? '')."\n\n[API FAILURES]: ".implode(', ', $failureNotes));
            }

            if (!empty($jumlahNotes)) {
                $notes = trim(($notes ?? '')."\n\nINFO JUMLAH:\n- ".implode("\n- ", $jumlahNotes));
            }

            FormSubmission::create([
                'user_id' => $userId,
                'form_id' => $leafForm->id,
                'form_type' => 'App\Models\Form',
                'date' => $date,
                'answers' => $answers,
                'notes' => $notes,
            ]);

            $totalInserted++;
        }

        return $totalInserted;
    }

    protected function processLiveRows($rows, $formTitle, $internalFormSlug = null)
    {
        $liveSubmissions = collect();
        $idCounter = 1;

        foreach ($rows as $row) {
            $userName = $row['Nama pemeriksaan'] ?? ($row['Nama pemeriksa'] ?? 'Unknown User');
            $dateStr = $row['Tanggal pemeriksaan'] ?? ($row['Cap waktu'] ?? null);
            $notes = $row['Catatan'] ?? null;

            $date = Carbon::now()->format('Y-m-d');
            if ($dateStr) {
                try {
                    $date = Carbon::parse($dateStr)->format('Y-m-d');
                } catch (\Exception $e) {
                }
            }

            $leafForm = null;
            if ($internalFormSlug) {
                $leafForm = $this->resolveLeafForm($internalFormSlug, $row);
            }

            if (! $leafForm) {
                continue;
            }

            $metadataCols = ['Cap waktu', 'Nama pemeriksaan', 'Nama pemeriksa', 'Tanggal pemeriksaan', 'Area Pemeriksaan', 'Cimex', 'Existing', 'Gede', 'Sumber', 'Catatan', 'Lokasi Pemeriksaan', 'Nomer Conventional Rolling Door', 'Nomer Speed Rolling Door', 'Nomor Conventional Rolling Door', 'Nomor Speed Rolling Door', 'Jenis Rolling Door', 'Nomor pintu geser', 'Nomer pintu geser', 'Skylight Protection (SP)', 'Area storage gas', 'Pilihan', 'Pilihan Pintu', ''];
            $metadataColsLower = array_map(fn($c) => strtolower(trim($c)), $metadataCols);

            $hasFailure = false;
            $failureNotes = [];
            $jumlahNotes = [];
            foreach ($row as $colName => $val) {
                if (in_array(strtolower(trim($colName)), $metadataColsLower)) {
                    continue;
                }
                if (str_contains(strtolower($colName), 'jumlah')) {
                    if (!empty(trim($val))) {
                        $jumlahNotes[] = "$colName: $val";
                    }
                    continue;
                }

                $valLower = strtolower(trim($val));
                if (empty($valLower) || $valLower == 'sesuai standard' || $valLower == 'sesuai standar' || $valLower == 'ok' || $valLower == 'kondisi baik') {
                    continue;
                } else {
                    $hasFailure = true;
                    $failureNotes[] = "$colName: $val";
                }
            }

            $options = $leafForm->options ?? [];
            $answers = [];

            if (! $hasFailure) {
                $answers = $options;
            } else {
                $answers = $options;
                $notes = trim(($notes ?? '')."\n\n[IMPORTED FAILURES]: ".implode(', ', $failureNotes));
            }

            if (!empty($jumlahNotes)) {
                $notes = trim(($notes ?? '')."\n\nINFO JUMLAH:\n- ".implode("\n- ", $jumlahNotes));
            }

            // Store as a plain associative array — NO Eloquent models, safe for cache serialization
            $liveSubmissions->push([
                'id' => 'live_'.$idCounter++,
                'form_id' => $leafForm->id,
                'date' => $date,
                'answers' => is_array($answers) ? $answers : [],
                'notes' => $notes,
                'user_name' => $userName,
            ]);
        }

        return $liveSubmissions;
    }

    /**
     * Resolve the internal leaf Form model from a slug and a response row.
     *
     * Logic:
     * 1. If slug maps to a leaf (is_category = false) → return it directly.
     * 2. If slug maps to a category:
     *    a. Try to match 'Area Pemeriksaan' / 'Lokasi Pemeriksaan' to a leaf title under that parent.
     *    b. Fallback: return the first leaf descendant of the category (handles single-child categories).
     */
    protected function resolveLeafForm(string $slug, array $row): ?Form
    {
        $formBySlug = Form::where('slug', $slug)->first();
        if (! $formBySlug) {
            return null;
        }

        // Direct leaf — just return it
        if (! $formBySlug->is_category) {
            return $formBySlug;
        }

        $parent = $formBySlug;

        // If the parent is Work at Height, try to narrow down the category using 'Jenis Pemeriksaan'
        if ($parent->slug === 'work-at-height') {
            $jenisVal = $row['Jenis Pemeriksaan'] ?? null;
            if ($jenisVal) {
                $jenisLower = strtolower(trim($jenisVal));
                
                $slugMap = [
                    'full body haerness (fbh)' => 'full-body-harness',
                    'mobilr elevation (lifter)' => 'mobile-elevation-lifter',
                    'barrier / railing' => 'barrier-dan-self-door-closer',
                    'skylight protection' => 'skylife-protection',
                ];
                
                $targetSlug = $slugMap[$jenisLower] ?? Str::slug($jenisLower);
                $subParent = Form::where('parent_id', $parent->id)
                    ->where(function($q) use ($targetSlug, $jenisLower) {
                        $q->where('slug', $targetSlug)
                          ->orWhere('title', 'like', "%{$jenisLower}%");
                    })
                    ->first();
                if ($subParent) {
                    $parent = $subParent;
                    if (! $parent->is_category) {
                        return $parent;
                    }
                } else {
                    // Do not route if subcategory has been detached (e.g. Fix Ladder, Mobile Ladder)
                    return null;
                }
            }
        }

        // Try to match by area field in the response
        $locValue = '';
        if ($parent->slug === 'rolling-door') {
            $locValue = $row['Nomer Conventional Rolling Door'] 
                     ?? ($row['Nomer Speed Rolling Door'] 
                     ?? ($row['Nomor Conventional Rolling Door'] 
                     ?? ($row['Nomor Speed Rolling Door'] ?? '')));
            
            $locLower = strtolower(trim((string) $locValue));
            if (preg_match('/^(rd\s*\d+)/i', $locLower, $matches)) {
                $code = str_replace(' ', '', $matches[1]);
                foreach ($parent->children as $cat) {
                    foreach ($cat->children as $leaf) {
                        $leafCode = strtolower(trim($leaf->title));
                        if (preg_match('/^(rd\s*\d+)/i', $leafCode, $leafMatches)) {
                            $lCode = str_replace(' ', '', $leafMatches[1]);
                            if ($lCode === $code) {
                                return $leaf;
                            }
                        }
                    }
                }
            }
        } elseif ($parent->slug === 'pintu-geser') {
            // Robust check for Pintu Geser code (starts with PG)
            foreach ($row as $k => $v) {
                if (preg_match('/^pg\s*\d+/i', trim((string)$v))) {
                    $locValue = $v;
                    break;
                }
            }
        } elseif ($parent->slug === 'skylife-protection') {
            $locValue = $row['Skylight Protection (SP)'] ?? '';
        } else {
            $areaValue = $row['Area Pemeriksaan'] ?? ($row['Lokasi Pemeriksaan'] ?? '');
            $locValue = $row[$areaValue] ?? $areaValue;
            
            if (empty($locValue)) {
                $locValue = $row['Pilihan'] ?? ($row['Pilihan Pintu'] ?? '');
            }
            
            if (empty($locValue)) {
                $locValue = $row['Nama pemeriksaan'] ?? ($row['Nama pemeriksa'] ?? '');
            }
        }
        $locLower = strtolower(trim((string) $locValue));

        if ($locLower) {
            $leaves = Form::where('is_category', false)->get();

            // Pass 1: Exact match
            foreach ($leaves as $lf) {
                $titleLower = strtolower(trim($lf->title));
                if ($titleLower === $locLower) {
                    // Verify it belongs to our parent tree
                    $ancestor = $lf->parent;
                    while ($ancestor) {
                        if ($ancestor->id === $parent->id) {
                            return $lf;
                        }
                        $ancestor = $ancestor->parent;
                    }
                }
            }

            // Pass 2: Substring match fallback
            foreach ($leaves as $lf) {
                $titleLower = strtolower($lf->title);
                if (str_contains($titleLower, $locLower) || str_contains($locLower, $titleLower)) {
                    // Verify it belongs to our parent tree
                    $ancestor = $lf->parent;
                    while ($ancestor) {
                        if ($ancestor->id === $parent->id) {
                            return $lf;
                        }
                        $ancestor = $ancestor->parent;
                    }
                }
            }
        }

        // Fallback: find the first leaf descendant of this parent (BFS)
        return $this->firstLeafDescendant($parent);
    }

    /**
     * BFS to find the first leaf (no children) under a given Form.
     */
    protected function firstLeafDescendant(Form $form): ?Form
    {
        $children = Form::where('parent_id', $form->id)->orderBy('order')->get();
        foreach ($children as $child) {
            if (! $child->is_category) {
                return $child; // This child IS the leaf
            }
            // Recurse into category children
            $leaf = $this->firstLeafDescendant($child);
            if ($leaf) {
                return $leaf;
            }
        }
        // form itself might be a leaf if it has no children but is not marked category
        if (! $form->is_category) {
            return $form;
        }

        return null;
    }
}

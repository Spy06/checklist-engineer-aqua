<?php

namespace App\Services;

use Google\Client;
use Google\Service\Forms;
use App\Models\User;
use App\Models\Form;
use App\Models\FormSubmission;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class GoogleFormsSyncService
{
    protected $client;
    protected $formsService;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName('Checklist Engineer Sync');
        
        $base64Creds = config('services.google.base64_credentials');
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
            Log::error('Google credentials file not found at: ' . $credentialsPath);
        }
    }

    public function syncForm($googleFormId, $internalFormSlug = null)
    {
        if (!$this->formsService) {
            return [
                'status' => 'error',
                'message' => 'Google Client not configured properly. Check credentials.'
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
                        
                        $row[$questionTitle] = $value;
                    }
                }
                $formattedRows[] = $row;
            }

            // 3. Process formatted rows
            \Illuminate\Support\Facades\Log::info("Google Forms API Rows: " . json_encode($formattedRows, JSON_PRETTY_PRINT));
            $totalSynced = $this->processRows($formattedRows, $formTitle, $internalFormSlug);

            return [
                'status' => 'success',
                'message' => "Successfully synced {$totalSynced} responses from '{$formTitle}'",
                'synced_count' => $totalSynced
            ];
            
        } catch (\Exception $e) {
            Log::error('Google Forms Sync Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function getLiveResponses($googleFormId, $internalFormSlug = null)
    {
        if (!$this->formsService) {
            return collect();
        }

        try {
            $form = $this->formsService->forms->get($googleFormId);
            $questionMap = $this->buildQuestionMap($form);
            $formTitle = $form->getInfo()->getTitle();

            $responses = $this->formsService->forms_responses->listFormsResponses($googleFormId);
            $formattedRows = [];
            
            foreach ($responses->getResponses() as $response) {
                $row = [];
                $row['Cap waktu'] = $response->getCreateTime();
                
                $answers = $response->getAnswers();
                if ($answers) {
                    foreach ($answers as $questionId => $answer) {
                        $questionTitle = $questionMap[$questionId] ?? 'Unknown Question';
                        $textAnswers = $answer->getTextAnswers();
                        $value = '';
                        if ($textAnswers && count($textAnswers->getAnswers()) > 0) {
                            $value = $textAnswers->getAnswers()[0]->getValue();
                        }
                        $row[$questionTitle] = $value;
                    }
                }
                $formattedRows[] = $row;
            }

            return $this->processLiveRows($formattedRows, $formTitle, $internalFormSlug);
        } catch (\Exception $e) {
            Log::error('Google Forms Live Fetch Error: ' . $e->getMessage());
            return collect();
        }
    }

    protected function buildQuestionMap($form)
    {
        $map = [];
        $items = $form->getItems();
        
        if (!$items) return $map;
        
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
                    $map[$question->getQuestionId()] = $title ?: $lastTitle;
                }
            } else {
                // sometimes questions are in groups
                $questionGroupItem = $item->getQuestionGroupItem();
                if ($questionGroupItem && $questionGroupItem->getQuestions()) {
                    foreach ($questionGroupItem->getQuestions() as $q) {
                        $map[$q->getQuestionId()] = $title ?: $lastTitle;
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
                } catch (\Exception $e) {}
            }

            // Get or create User
            $userKey = strtolower(trim($userName));
            $user = User::where('name', $userName)->first();
            if (!$user) {
                $user = User::create([
                    'name' => $userName,
                    'username' => Str::slug($userName) . '_' . uniqid(),
                    'email' => Str::slug($userName) . '_' . uniqid() . '@example.com',
                    'password' => bcrypt('password'),
                    'role' => 'responden',
                ]);
            }
            $userId = $user->id;

            // Mapping Google Form Title to our Form logic
            // Assuming $internalFormSlug is provided, if not we try to guess
            $leafForm = null;
            if ($internalFormSlug) {
                // E.g., mobile-ladder
                $parent = Form::where('slug', $internalFormSlug)->first();
                if ($parent) {
                    $areaValue = $row['Area Pemeriksaan'] ?? ($row['Lokasi Pemeriksaan'] ?? '');
                    // For forms that use routing (e.g. choosing 'Existing' then answering in 'Existing' section)
                    $locValue = $row[$areaValue] ?? $areaValue;
                    
                    // Search leaf among all forms, since it's a small dataset we can just find any leaf matching the title
                    // For better accuracy we should find leaves under this parent, but a global leaf search is fine for unique names
                    $leaves = Form::where('is_category', false)->get();
                    $locValueLower = strtolower(trim($locValue));
                    
                    foreach ($leaves as $lf) {
                        if ($locValueLower && (str_contains(strtolower($lf->title), $locValueLower) || str_contains($locValueLower, strtolower($lf->title)))) {
                            // Check if this leaf actually belongs to our parent tree
                            $ancestor = $lf->parent;
                            $belongsToParent = false;
                            while ($ancestor) {
                                if ($ancestor->id === $parent->id) {
                                    $belongsToParent = true;
                                    break;
                                }
                                $ancestor = $ancestor->parent;
                            }
                            
                            if ($belongsToParent) {
                                $leafForm = $lf;
                                break;
                            }
                        }
                    }
                    if (!$leafForm) {
                        // fallback to a default leaf in this parent
                        $firstCategory = Form::where('parent_id', $parent->id)->first();
                        if ($firstCategory) {
                            $leafForm = Form::where('parent_id', $firstCategory->id)->first();
                        }
                    }
                } else {
                    $leafForm = Form::where('slug', $internalFormSlug)->first();
                }
            }

            if (!$leafForm) {
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
            $metadataCols = ['Cap waktu', 'Nama pemeriksaan', 'Nama pemeriksa', 'Tanggal pemeriksaan', 'Area Pemeriksaan', 'Cimex', 'Existing', 'Gede', 'Sumber', 'Catatan', 'Lokasi Pemeriksaan', 'Nomer Conventional Rolling Door', 'Nomer Speed Rolling Door', 'Area storage gas', ''];
            
            $hasFailure = false;
            $failureNotes = [];
            foreach ($row as $colName => $val) {
                if (in_array(trim($colName), $metadataCols)) continue;
                if (str_contains(strtolower($colName), 'jumlah')) continue; 
                
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

            if (!$hasFailure) {
                $answers = $options;
            } else {
                if (count($options) > 0) {
                    $answers = array_slice($options, 1);
                }
                $notes = trim(($notes ?? '') . "\n\n[API FAILURES]: " . implode(", ", $failureNotes));
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
                } catch (\Exception $e) {}
            }

            $leafForm = null;
            if ($internalFormSlug) {
                $parent = Form::where('slug', $internalFormSlug)->first();
                if ($parent) {
                    $areaValue = $row['Area Pemeriksaan'] ?? ($row['Lokasi Pemeriksaan'] ?? '');
                    $locValue = $row[$areaValue] ?? $areaValue;
                    
                    $leaves = Form::where('is_category', false)->get();
                    $locValueLower = strtolower(trim($locValue));
                    
                    foreach ($leaves as $lf) {
                        if ($locValueLower && (str_contains(strtolower($lf->title), $locValueLower) || str_contains($locValueLower, strtolower($lf->title)))) {
                            $ancestor = $lf->parent;
                            $belongsToParent = false;
                            while ($ancestor) {
                                if ($ancestor->id === $parent->id) {
                                    $belongsToParent = true;
                                    break;
                                }
                                $ancestor = $ancestor->parent;
                            }
                            if ($belongsToParent) {
                                $leafForm = $lf;
                                break;
                            }
                        }
                    }
                    if (!$leafForm) {
                        $firstCategory = Form::where('parent_id', $parent->id)->first();
                        if ($firstCategory) {
                            $leafForm = Form::where('parent_id', $firstCategory->id)->first();
                        }
                    }
                } else {
                    $leafForm = Form::where('slug', $internalFormSlug)->first();
                }
            }

            if (!$leafForm) continue;

            $metadataCols = ['Cap waktu', 'Nama pemeriksaan', 'Nama pemeriksa', 'Tanggal pemeriksaan', 'Area Pemeriksaan', 'Cimex', 'Existing', 'Gede', 'Sumber', 'Catatan', 'Lokasi Pemeriksaan', 'Nomer Conventional Rolling Door', 'Nomer Speed Rolling Door', 'Area storage gas', ''];
            
            $hasFailure = false;
            $failureNotes = [];
            foreach ($row as $colName => $val) {
                if (in_array(trim($colName), $metadataCols)) continue;
                if (str_contains(strtolower($colName), 'jumlah')) continue; 
                
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

            if (!$hasFailure) {
                $answers = $options;
            } else {
                if (count($options) > 0) {
                    $answers = array_slice($options, 1);
                }
                $notes = trim(($notes ?? '') . "\n\n[IMPORTED FAILURES]: " . implode(", ", $failureNotes));
            }

            // Store as a plain associative array — NO Eloquent models, safe for cache serialization
            $liveSubmissions->push([
                'id'        => 'live_' . $idCounter++,
                'form_id'   => $leafForm->id,
                'date'      => $date,
                'answers'   => is_array($answers) ? $answers : [],
                'notes'     => $notes,
                'user_name' => $userName,
            ]);
        }

        return $liveSubmissions;
    }
}

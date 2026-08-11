<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ImportCsvSubmissions extends Command
{
    protected $signature = 'app:import-csv-submissions';
    protected $description = 'Import old CSV submissions into the new forms structure';

    public function handle()
    {
        $csvDir = base_path('Dari Pak Dadang/Jawaban');
        $files = glob($csvDir . '/*.csv');

        $this->info("Found " . count($files) . " CSV files.");

        // Define mapping from CSV filename to form slugs (parent categories or leaf nodes)
        $fileMap = [
            'Form Pemeriksaan Bor Duduk .csv' => ['slug' => 'mesin-bor-duduk', 'leaf' => 'bd-workshop-01'],
            'Form Pemeriksaan Gerinda Duduk.csv' => ['slug' => 'mesin-gerinda-duduk', 'leaf' => 'gd-workshop-01'],
            'Form Pemeriksaan Jack Hammer Krisbow.csv' => ['slug' => 'jack-hammer', 'leaf' => 'jh-krisbow-01'],
            'Form Pemeriksaan Jack Hammer Makita.csv' => ['slug' => 'jack-hammer', 'leaf' => 'jh-makita-01'],
            'Form Pemeriksaan Mesin Las Argon.csv' => ['slug' => 'mesin-las-argon', 'leaf' => 'las-argon-workshop-01'],
            'Form Pemeriksaan Mesin Press.csv' => ['slug' => 'mesin-press', 'leaf' => 'mp-workshop-01'],
            'Form Pemeriksaan Storage Gas (sodu Gas).csv' => ['slug' => 'sodu-gas', 'leaf' => 'sodu-gas'],
            'Formulir pengecekan Lemari APD.csv' => ['slug' => 'litter', 'leaf' => 'lemari-apd-workshop'],
            'Formulir pengecekan Lemari tool 1.csv' => ['slug' => 'litter', 'leaf' => 'lemari-tool-1'],
            'Formulir pengecekan Lemari tool 2.csv' => ['slug' => 'litter', 'leaf' => 'lemari-tool-2'],
            'Formulir pengecekan Lemari tool 3.csv' => ['slug' => 'litter', 'leaf' => 'lemari-tool-3'],
            'Formulir pengecekan Lemari tool 4.csv' => ['slug' => 'litter', 'leaf' => 'lemari-tool-4'],
            'Formulir pengecekan Rak Facility.csv' => ['slug' => 'rak-facility', 'leaf' => 'rak-facility-sparepart'],
            'Form Pemeriksaan Lifter.csv' => ['slug' => 'rak-facility', 'leaf' => 'rak-facility-workshop'],
            'Formulir pengecekan scaffolding .csv' => ['slug' => 'work-at-height', 'leaf' => 'scaffolding'], // wait, scaffolding is a direct item under work-at-height
            
            // Files with locations
            'Form Pemeriksaan Lifeline.csv' => ['slug' => 'lifeline', 'loc_col' => 'Lokasi Pemeriksaan'],
            'Form Pemeriksaan Mobile Ladder .csv' => ['slug' => 'mobile-ladder', 'loc_col' => 'Area Pemeriksaan'],
            'Form pemeriksaan pintu geser.csv' => ['slug' => 'pintu-geser', 'loc_col_idx' => 3], // Unnamed column
            'Form Pemeriksaan Rolling Door.csv' => ['slug' => 'rolling-door', 'loc_col_multi' => ['Nomer Conventional Rolling Door', 'Nomer Speed Rolling Door']],
            'Form Pemeriksaan Skylight Protection.csv' => ['slug' => 'skylife-protection', 'loc_col_idx' => 3],
            'Form Pemeriksaan Storage Gas (Ar & N2).csv' => ['slug' => 'argon-dan-nitrogen-storage-gas', 'loc_col' => 'Area storage gas'],
            'Form Pemeriksaan Storage Gas (Fr).csv' => ['slug' => 'freon-storage', 'loc_col' => 'Area storage gas'],
            'Form pemeriksaan FBH.csv' => ['slug' => 'full-body-harness', 'loc_col' => 'Lokasi Pemeriksaan'],
            'Form pemeriksaan Fix Ladder.csv' => ['slug' => 'fix-ladder', 'loc_col_multi' => ['Cimex', 'Existing', 'Gede', 'Sumber']],
        ];

        $users = []; // cache users

        $totalInserted = 0;

        foreach ($files as $file) {
            $basename = basename($file);
            if (!isset($fileMap[$basename])) {
                $this->warn("No mapping for $basename");
                continue;
            }

            $map = $fileMap[$basename];
            
            if (($handle = fopen($file, "r")) !== false) {
                $headers = fgetcsv($handle);
                // clean BOM if exists
                $headers[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $headers[0]);
                
                $rowNum = 0;
                while (($data = fgetcsv($handle)) !== false) {
                    $rowNum++;
                    $row = array_combine($headers, $data);
                    if ($row === false) {
                        $this->error("Row column mismatch in $basename at row $rowNum");
                        continue;
                    }

                    // Extract Metadata
                    // "Nama pemeriksaan" or "Nama pemeriksa"
                    $userName = $row['Nama pemeriksaan'] ?? ($row['Nama pemeriksa'] ?? 'Unknown User');
                    $dateStr = $row['Tanggal pemeriksaan'] ?? null;
                    $notes = $row['Catatan'] ?? null;

                    // Parse Date
                    $date = null;
                    if ($dateStr) {
                        try {
                            $date = Carbon::parse($dateStr)->format('Y-m-d');
                        } catch (\Exception $e) {
                            $date = Carbon::now()->format('Y-m-d');
                        }
                    }

                    // Get or create User
                    $userKey = strtolower(trim($userName));
                    if (!isset($users[$userKey])) {
                        $user = User::where('name', $userName)->first();
                        if (!$user) {
                            $user = User::create([
                                'name' => $userName,
                                'username' => Str::slug($userName) . '_' . uniqid(),
                                'email' => Str::slug($userName) . '_' . uniqid() . '@example.com',
                                'password' => bcrypt('password'),
                                'role' => 'responden', // Default role for imported reviewers
                            ]);
                        }
                        $users[$userKey] = $user->id;
                    }
                    $userId = $users[$userKey];

                    // Find Leaf Form
                    $leafForm = null;
                    if (isset($map['leaf'])) {
                        if ($map['leaf'] === 'scaffolding' || $map['leaf'] === 'sodu-gas') {
                            $leafForm = Form::where('slug', $map['leaf'])->first();
                        } else {
                            $leafForm = Form::where('slug', $map['leaf'])->first();
                        }
                    } else {
                        // Dynamic location
                        $locValue = '';
                        if (isset($map['loc_col'])) {
                            $locValue = $row[$map['loc_col']] ?? '';
                        } elseif (isset($map['loc_col_idx'])) {
                            // By index
                            $locValue = $data[$map['loc_col_idx']] ?? '';
                        } elseif (isset($map['loc_col_multi'])) {
                            foreach ($map['loc_col_multi'] as $c) {
                                if (!empty($row[$c])) {
                                    $locValue = $row[$c];
                                    break;
                                }
                            }
                        }

                        // Search leaf by checking if locValue substring matches slug or title loosely
                        $parent = Form::where('slug', $map['slug'])->first();
                        if ($parent) {
                            // Get all leaves under this parent
                            $leaves = Form::where('parent_id', $parent->id)->get();
                            if ($leaves->isEmpty()) {
                                // Maybe one more level deep
                                $categories = Form::where('parent_id', $parent->id)->pluck('id');
                                $leaves = Form::whereIn('parent_id', $categories)->get();
                            }
                            
                            $locValueLower = strtolower(trim($locValue));
                            // Try exact match on title or slug
                            foreach ($leaves as $lf) {
                                if (str_contains(strtolower($lf->title), $locValueLower) || str_contains($locValueLower, strtolower($lf->title))) {
                                    $leafForm = $lf;
                                    break;
                                }
                            }
                            
                            // If still not found, try code matching (e.g., FLC 01)
                            if (!$leafForm) {
                                preg_match('/([A-Za-z]+)\s*(\d+)/', $locValue, $matches);
                                if (count($matches) >= 3) {
                                    $code = strtolower($matches[1] . '-' . $matches[2]);
                                    foreach ($leaves as $lf) {
                                        if (str_contains($lf->slug, $code)) {
                                            $leafForm = $lf;
                                            break;
                                        }
                                    }
                                }
                            }
                            
                            // If still not found, pick first just so we don't lose data
                            if (!$leafForm) {
                                $leafForm = $leaves->first();
                            }
                        }
                    }

                    if (!$leafForm) {
                        $this->warn("Leaf form not found for row $rowNum in $basename. LocValue: " . ($locValue ?? ''));
                        continue;
                    }

                    // Check options status
                    // All columns except metadata are considered options
                    $metadataCols = ['Cap waktu', 'Nama pemeriksaan', 'Nama pemeriksa', 'Tanggal pemeriksaan', 'Area Pemeriksaan', 'Cimex', 'Existing', 'Gede', 'Sumber', 'Catatan', 'Lokasi Pemeriksaan', 'Nomer Conventional Rolling Door', 'Nomer Speed Rolling Door', 'Area storage gas', ''];
                    
                    $hasFailure = false;
                    $failureNotes = [];
                    foreach ($row as $colName => $val) {
                        if (in_array(trim($colName), $metadataCols)) continue;
                        if (str_contains(strtolower($colName), 'jumlah')) continue; // Skip "Jumlah X" columns
                        
                        $valLower = strtolower(trim($val));
                        // Consider these as safe
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
                        // Check all
                        $answers = $options;
                    } else {
                        // If failure, check all except first, or randomly, or just drop all.
                        // Best effort: drop the first option to indicate failure, and append notes.
                        if (count($options) > 0) {
                            $answers = array_slice($options, 1);
                        }
                        
                        if ($notes) {
                            $notes .= "\n\n[IMPORTED FAILURES]: " . implode(", ", $failureNotes);
                        } else {
                            $notes = "[IMPORTED FAILURES]: " . implode(", ", $failureNotes);
                        }
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
                fclose($handle);
            }
        }

        $this->info("Import completed! Total submissions inserted: $totalInserted");
    }
}

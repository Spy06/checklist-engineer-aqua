<?php

namespace App\Console\Commands;

use App\Models\Form;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class NotifyIncompleteChecklists extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notify-incomplete-checklists {--force : Jalankan pengecekan tanpa memperhatikan tanggal}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim notifikasi ke admin jika ada checklist yang belum diisi (H-4 sebelum berganti bulan)';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $now = Carbon::now();
        $daysUntilEnd = (int) $now->copy()->endOfMonth()->diffInDays($now);
        
        $force = $this->option('force');

        if ($daysUntilEnd !== 4 && !$force) {
            $this->info("Bukan H-4 sebelum akhir bulan. (Sisa $daysUntilEnd hari). Skipping.");
            return;
        }

        $monthName = $now->translatedFormat('F'); // e.g. Agustus

        // Ambil semua root form (yang harus diisi tiap bulan)
        $rootForms = Form::whereNull('parent_id')->get();
        
        $incompleteForms = [];
        
        foreach ($rootForms as $form) {
            // Collect all descendant IDs + the root form itself
            $formIds = $this->getAllDescendantIds($form);
            $formIds[] = $form->id;
            
            $hasSubmissionThisMonth = \App\Models\FormSubmission::whereIn('form_id', $formIds)
                ->whereMonth('date', $now->month)
                ->whereYear('date', $now->year)
                ->exists();
                
            if (!$hasSubmissionThisMonth) {
                $incompleteForms[] = $form->title;
            }
        }
        
        if (count($incompleteForms) > 0) {
            $admins = User::whereIn('role', ['super_admin', 'admin'])->get();
            $formList = implode(', ', $incompleteForms);
            
            foreach ($admins as $admin) {
                Notification::make()
                    ->title("Peringatan: Checklist Bulan $monthName Belum Lengkap")
                    ->body("Beberapa checklist belum diisi bulan ini: $formList. Segera selesaikan sebelum bulan berganti.")
                    ->warning()
                    ->sendToDatabase($admin);
            }
            
            $this->info("Notifikasi terkirim untuk form: " . $formList);
        } else {
            $this->info("Semua checklist bulan $monthName sudah terisi minimal 1 kali.");
        }
    }
    
    /**
     * @return array<int>
     */
    private function getAllDescendantIds(Form $form): array
    {
        $ids = [];
        foreach ($form->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getAllDescendantIds($child));
        }
        return $ids;
    }
}

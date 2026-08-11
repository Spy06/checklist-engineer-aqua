<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Support\Carbon;
use App\Concerns\MatrixSummary;

class MatrixChecklist extends Page
{
    use MatrixSummary;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';
    protected static ?string $navigationLabel = 'Matriks Checklist';
    protected static ?string $title = 'Matriks Checklist';
    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.matrix-checklist';

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('syncGoogleForm')
                ->label('Sync Google Form')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->form([
                    \Filament\Forms\Components\TextInput::make('form_id')
                        ->label('Google Form ID')
                        ->required()
                        ->default('1bJXgi5GMANXfesZAXkn77f4KFugpXCwLN7nLkEIKQjk')
                        ->helperText('Contoh: 1bJXgi5GMANXfesZAXkn77f4KFugpXCwLN7nLkEIKQjk'),
                    \Filament\Forms\Components\TextInput::make('internal_slug')
                        ->label('Slug Kategori (Opsional)')
                        ->helperText('Contoh: mobile-ladder. Jika dikosongkan, script akan mencoba mencari otomatis.')
                ])
                ->action(function (array $data, \App\Services\GoogleFormsSyncService $syncService) {
                    $result = $syncService->syncForm($data['form_id'], $data['internal_slug'] ?? null);
                    
                    if ($result['status'] === 'success') {
                        \Filament\Notifications\Notification::make()
                            ->title('Sinkronisasi Berhasil')
                            ->body($result['message'])
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Sinkronisasi Gagal')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function getViewData(): array
    {
        $currentYear  = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;

        $months = [];
        for ($i = 1; $i <= $currentMonth; $i++) {
            $months[$i] = Carbon::create()->month($i)->translatedFormat('F');
        }

        $rootForms = Form::whereNull('parent_id')->orderBy('order')->get();

        $matrix  = $this->buildMatrixRows($rootForms, $months, $currentYear);
        $summary = $this->buildMonthlySummary($months, $currentYear);

        return [
            'months'      => $months,
            'matrix'      => $matrix,
            'summary'     => $summary,
            'currentYear' => $currentYear,
        ];
    }

    /**
     * Build flattened matrix rows recursively with 3-state status.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Form>  $forms
     * @param  array<int, string>  $months
     * @return array<int, array<string, mixed>>
     */
    private function buildMatrixRows($forms, array $months, int $currentYear, int $level = 0, ?int $parentId = null, array $ancestors = []): array
    {
        $rows = [];

        foreach ($forms as $form) {
            $hasChildren = $form->children->count() > 0;
            $leafIds     = $this->getLeafDescendantIds($form);

            if (! $hasChildren) {
                $leafIds = [$form->id];
            }

            $status = [];
            foreach ($months as $monthNum => $monthName) {
                if ($hasChildren) {
                    $filledCount = FormSubmission::whereIn('form_id', $leafIds)
                        ->whereMonth('date', $monthNum)
                        ->whereYear('date', $currentYear)
                        ->distinct('form_id')
                        ->count('form_id');

                    $totalLeaves = count($leafIds);

                    if ($filledCount === 0) {
                        $status[$monthNum] = 'none';
                    } elseif ($filledCount >= $totalLeaves) {
                        $status[$monthNum] = 'full';
                    } else {
                        $status[$monthNum] = 'partial';
                    }
                } else {
                    $exists = FormSubmission::where('form_id', $form->id)
                        ->whereMonth('date', $monthNum)
                        ->whereYear('date', $currentYear)
                        ->exists();

                    $status[$monthNum] = $exists ? 'full' : 'none';
                }
            }

            $rows[] = [
                'id'           => $form->id,
                'parent_id'    => $parentId,
                'ancestors'    => $ancestors,
                'level'        => $level,
                'title'        => $form->title,
                'slug'         => $form->slug ?? null,
                'is_category'  => $form->is_category,
                'has_children' => $hasChildren,
                'status'       => $status,
            ];

            if ($hasChildren) {
                $newAncestors = array_merge($ancestors, [$form->id]);
                $childRows    = $this->buildMatrixRows($form->children, $months, $currentYear, $level + 1, $form->id, $newAncestors);
                $rows         = array_merge($rows, $childRows);
            }
        }

        return $rows;
    }

    /**
     * Get IDs of all leaf descendants (forms with no children).
     *
     * @return array<int>
     */
    private function getLeafDescendantIds(Form $form): array
    {
        $ids = [];
        foreach ($form->children as $child) {
            if ($child->children->count() === 0) {
                $ids[] = $child->id;
            } else {
                $ids = array_merge($ids, $this->getLeafDescendantIds($child));
            }
        }
        return $ids;
    }
}

<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Support\Carbon;

class ChecklistMatrix extends Widget
{
    protected string $view = 'filament.widgets.checklist-matrix';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        // Get all months of the current year up to the current month
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        
        $months = [];
        for ($i = 1; $i <= $currentMonth; $i++) {
            $months[$i] = Carbon::create()->month($i)->translatedFormat('F');
        }

        // Get all root forms
        $rootForms = Form::whereNull('parent_id')->orderBy('order')->get();
        
        $matrix = [];

        foreach ($rootForms as $form) {
            $formIds = $this->getAllDescendantIds($form);
            $formIds[] = $form->id;
            
            $matrix[$form->id] = [
                'title' => $form->title,
                'status' => [],
            ];
            
            foreach ($months as $monthNum => $monthName) {
                $hasSubmission = FormSubmission::whereIn('form_id', $formIds)
                    ->whereMonth('date', $monthNum)
                    ->whereYear('date', $currentYear)
                    ->exists();
                    
                $matrix[$form->id]['status'][$monthNum] = $hasSubmission;
            }
        }

        return [
            'months' => $months,
            'matrix' => $matrix,
            'currentYear' => $currentYear,
        ];
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

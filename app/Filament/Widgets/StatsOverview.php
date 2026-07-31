<?php

namespace App\Filament\Widgets;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Submissions', FormSubmission::count())
                ->description('All checklist submissions')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),
            Stat::make('Total Forms', Form::where('is_category', false)->count())
                ->description('Available forms')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
            Stat::make('Registered Users', User::count())
                ->description('Total active users')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),
        ];
    }
}

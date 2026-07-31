<?php

namespace App\Filament\Resources\Forms\Pages;

use App\Filament\Resources\Forms\FormResource;
use App\Models\Form;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditForm extends EditRecord
{
    protected static string $resource = FormResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Form $record */
        $record = $this->getRecord();

        return [
            Action::make('lihat_isi')
                ->label($record->is_category ? 'Lihat Child Forms' : 'Kelola Opsi Checklist')
                ->icon($record->is_category ? 'heroicon-o-folder-open' : 'heroicon-o-list-bullet')
                ->color('info')
                ->url(route('filament.admin.resources.forms.index', ['parent_id' => $record->id]))
                ->visible(fn () => $record->is_category || ! empty($record->options)),

            Action::make('kembali')
                ->label('← Kembali')
                ->color('gray')
                ->url(fn () => $record->parent_id
                    ? route('filament.admin.resources.forms.index', ['parent_id' => $record->parent_id])
                    : route('filament.admin.resources.forms.index')
                ),

            DeleteAction::make(),
        ];
    }
}

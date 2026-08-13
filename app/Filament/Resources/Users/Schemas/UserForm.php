<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),

                TextInput::make('username')
                    ->label('Username')
                    ->required(),

                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state)),

                Select::make('role')
                    ->label('Role')
                    ->required()
                    ->options([
                        'super_admin' => '🔑 Super Admin',
                        'admin' => '🛠️ Admin (Terbatas)',
                        'responden' => '📋 Responden',
                    ])
                    ->default('responden')
                    ->live()
                    ->helperText(fn (?string $state): string => match ($state) {
                        'super_admin' => 'Kendali penuh atas seluruh sistem.',
                        'admin' => 'Hanya bisa CRUD forms pada bagian yang di-assign.',
                        'responden' => 'Hanya bisa mengisi form pada bagian yang di-assign.',
                        default => '',
                    }),

                Select::make('assignedForms')
                    ->label('Form yang Di-assign (PIC)')
                    ->multiple()
                    ->relationship('assignedForms', 'title', fn ($query) => $query->whereNull('parent_id')->orderBy('order'))
                    ->helperText(fn ($livewire): string => 'Setiap form hanya dapat memiliki 1 PIC. Form yang sudah di-assign ke user lain tidak bisa dipilih.')
                    ->visible(fn (callable $get): bool => in_array($get('role'), ['admin', 'responden']))
                    ->getOptionLabelFromRecordUsing(fn (Form $record): string => $record->title)
                    ->options(function ($livewire) {
                        // Get the current record ID (null if creating)
                        $currentRecordId = $livewire->record?->id ?? null;

                        // Get form IDs already assigned to OTHER users
                        $assignedFormIds = \DB::table('user_form_assignments')
                            ->when($currentRecordId, fn ($q) => $q->where('user_id', '!=', $currentRecordId))
                            ->pluck('form_id')
                            ->toArray();

                        return Form::whereNull('parent_id')
                            ->orderBy('order')
                            ->get()
                            ->mapWithKeys(fn (Form $form) => [
                                $form->id => in_array($form->id, $assignedFormIds)
                                    ? "🔒 {$form->title} (Sudah di-assign)"
                                    : $form->title,
                            ]);
                    })
                    ->disableOptionWhen(function (string|int $value, $livewire): bool {
                        $currentRecordId = $livewire->record?->id ?? null;

                        return \DB::table('user_form_assignments')
                            ->where('form_id', $value)
                            ->when($currentRecordId, fn ($q) => $q->where('user_id', '!=', $currentRecordId))
                            ->exists();
                    })
                    ->columnSpanFull(),
            ]);
    }
}

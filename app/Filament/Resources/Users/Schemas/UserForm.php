<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Form;
use Filament\Forms\Components\CheckboxList;
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
                        'admin'       => '🛠️ Admin (Terbatas)',
                        'responden'   => '📋 Responden',
                    ])
                    ->default('responden')
                    ->live()
                    ->helperText(fn (?string $state): string => match ($state) {
                        'super_admin' => 'Kendali penuh atas seluruh sistem.',
                        'admin'       => 'Hanya bisa CRUD forms pada bagian yang di-assign.',
                        'responden'   => 'Hanya bisa mengisi form pada bagian yang di-assign.',
                        default       => '',
                    }),

                CheckboxList::make('assignedForms')
                    ->label('Form yang Di-assign')
                    ->relationship('assignedForms', 'title', fn ($query) => $query->whereNull('parent_id')->orderBy('order'))
                    ->helperText('Pilih form utama (root) yang boleh diakses/dikelola oleh user ini. Berlaku untuk role Admin dan Responden.')
                    ->visible(fn (callable $get): bool => in_array($get('role'), ['admin', 'responden']))
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}

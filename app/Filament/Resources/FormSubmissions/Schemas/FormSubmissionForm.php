<?php

namespace App\Filament\Resources\FormSubmissions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class FormSubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('form_id')
                    ->relationship('form', 'title')
                    ->default(null),
                TextInput::make('form_type')
                    ->required(),
                DatePicker::make('date')
                    ->required(),
                Textarea::make('answers')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}

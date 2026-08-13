<?php

namespace App\Filament\Resources\Forms\Schemas;

use App\Models\Form;
use App\Models\User;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FormForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var User $user */
        $user = Auth::user();

        return $schema
            ->components([
                Section::make('Informasi Dasar')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('parent_id')
                                ->label('Induk (Kategori)')
                                ->relationship(
                                    'parent',
                                    'title',
                                    // Admins can only pick parents within their assigned scope
                                    function ($query) use ($user) {
                                        if ($user->isAdmin()) {
                                            $assignedIds = $user->assignedForms()->pluck('forms.id')->toArray();
                                            // Get all descendant IDs of assigned roots to use as valid parents
                                            $allAllowed = $assignedIds;
                                            $level = $assignedIds;
                                            while (! empty($level)) {
                                                $children = Form::whereIn('parent_id', $level)->pluck('id')->toArray();
                                                $allAllowed = array_merge($allAllowed, $children);
                                                $level = $children;
                                            }

                                            return $query->whereIn('id', $allAllowed);
                                        }

                                        return $query;
                                    }
                                )
                                ->default(fn () => request()->query('parent_id'))
                                ->helperText('Kosongkan jika ini adalah form utama di halaman depan.'),

                            Toggle::make('is_category')
                                ->label('Tipe: Childable?')
                                ->helperText('Aktifkan jika form ini punya sub-form. Matikan jika langsung berisi daftar opsi checklist.')
                                ->default(false)
                                ->required(),

                            TextInput::make('title')
                                ->label('Judul Form/Kategori')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (string $operation, $state, Set $set) {
                                    if ($operation === 'create') {
                                        $set('slug', Str::slug($state));
                                    }
                                }),

                            TextInput::make('slug')
                                ->label('Slug (URL)')
                                ->required(),

                            TextInput::make('order')
                                ->label('Urutan')
                                ->numeric()
                                ->default(0),
                        ]),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->default(null)
                            ->columnSpanFull(),
                    ]),

                Section::make('Daftar Checklist')
                    ->description('Masukkan item checklist untuk form ini. (Abaikan jika ini hanya kategori / childable)')
                    ->schema([
                        TagsInput::make('options')
                            ->label('Item Checklist')
                            ->placeholder('Ketik item checklist lalu tekan Enter')
                            ->splitKeys(['Enter'])
                            ->columnSpanFull(),
                    ]),

                Section::make('Warna Tombol')
                    ->description('Pilih warna aksen untuk tombol form ini di halaman dashboard. Default: hijau emerald.')
                    ->schema([
                        ColorPicker::make('color')
                            ->label('Warna Tombol (hex / rgba)')
                            ->rgba()
                            ->default('#10b981'),
                    ]),
            ]);
    }
}

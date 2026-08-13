<?php

namespace App\Filament\Resources\Forms\Tables;

use App\Models\Form;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class FormsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order')
                    ->label('#')
                    ->sortable()
                    ->width(40),

                TextColumn::make('title')
                    ->label('Nama Form')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('parent.title')
                    ->label('Induk')
                    ->badge()
                    ->color('info')
                    ->placeholder('— Root Form —'),

                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->getStateUsing(fn (Form $record) => $record->is_category ? 'Childable' : 'Option')
                    ->color(fn (string $state) => match ($state) {
                        'Childable' => 'info',
                        'Option' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('children_count')
                    ->label('Jumlah Child')
                    ->counts('children')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('options_count')
                    ->label('Jumlah Opsi')
                    ->getStateUsing(fn (Form $record) => count($record->options ?? []))
                    ->badge()
                    ->color('warning'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('view_children')
                    ->label(fn (Form $record) => $record->is_category ? 'Lihat Child Forms' : 'Kelola Opsi')
                    ->icon(fn (Form $record) => $record->is_category ? 'heroicon-o-folder-open' : 'heroicon-o-list-bullet')
                    ->color('gray')
                    ->url(fn (Form $record) => $record->is_category
                        ? route('filament.admin.resources.forms.index', ['parent_id' => $record->id])
                        : route('filament.admin.resources.forms.edit', ['record' => $record->id])
                    ),

                EditAction::make(),

                DeleteAction::make()
                    ->successNotificationTitle('Form berhasil dihapus beserta seluruh child-nya')
                    // Admins (non-super) cannot delete a root form (parent_id = null) that is assigned to them
                    ->visible(function (Form $record): bool {
                        /** @var User $user */
                        $user = Auth::user();
                        if ($user->isSuperAdmin()) {
                            return true;
                        }

                        // Admin can delete child forms, but NOT the root assigned forms
                        return $record->parent_id !== null;
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order');
    }
}

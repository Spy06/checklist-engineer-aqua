<?php

namespace App\Filament\Resources\FormSubmissions;

use App\Filament\Resources\FormSubmissions\Pages\EditFormSubmission;
use App\Filament\Resources\FormSubmissions\Pages\ListFormSubmissions;
use App\Filament\Resources\FormSubmissions\Schemas\FormSubmissionForm;
use App\Filament\Resources\FormSubmissions\Tables\FormSubmissionsTable;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FormSubmissionResource extends Resource
{
    protected static ?string $model = FormSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Form Submissions';

    /**
     * Scope query so that:
     * - super_admin: sees ALL submissions
     * - admin: sees ONLY submissions for forms under their assigned root forms
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User $user */
        $user = Auth::user();

        if ($user && $user->isAdmin()) {
            // Get all form IDs under the admin's assigned root forms
            $assignedRootIds = $user->assignedForms()->pluck('forms.id')->toArray();

            if (empty($assignedRootIds)) {
                return $query->whereRaw('1 = 0');
            }

            $allFormIds = static::getAllDescendantIds($assignedRootIds);
            // Include the root forms themselves too
            $allFormIds = array_merge($assignedRootIds, $allFormIds);

            return $query->whereIn('form_id', $allFormIds);
        }

        return $query;
    }

    /**
     * Get all descendant form IDs for a list of root IDs.
     *
     * @param  array<int>  $rootIds
     * @return array<int>
     */
    private static function getAllDescendantIds(array $rootIds): array
    {
        $allIds = [];
        $currentLevel = $rootIds;

        while (! empty($currentLevel)) {
            $children = Form::whereIn('parent_id', $currentLevel)->pluck('id')->toArray();
            $allIds = array_merge($allIds, $children);
            $currentLevel = $children;
        }

        return $allIds;
    }

    public static function form(Schema $schema): Schema
    {
        return FormSubmissionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FormSubmissionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFormSubmissions::route('/'),
            'edit'  => EditFormSubmission::route('/{record}/edit'),
        ];
    }
}

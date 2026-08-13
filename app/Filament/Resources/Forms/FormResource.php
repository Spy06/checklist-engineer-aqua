<?php

namespace App\Filament\Resources\Forms;

use App\Filament\Resources\Forms\Pages\CreateForm;
use App\Filament\Resources\Forms\Pages\EditForm;
use App\Filament\Resources\Forms\Pages\ListForms;
use App\Filament\Resources\Forms\Schemas\FormForm;
use App\Filament\Resources\Forms\Tables\FormsTable;
use App\Models\Form;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FormResource extends Resource
{
    protected static ?string $model = Form::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Forms';

    protected static ?string $recordTitleAttribute = 'title';

    /**
     * Scope the query so that:
     * - super_admin: sees ALL forms
     * - admin: sees only forms that are children (or self) of their assigned root forms
     *   (they cannot touch the root form itself, only manage its children)
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User $user */
        $user = Auth::user();

        if ($user && $user->isAdmin()) {
            // Get IDs of root forms assigned to this admin
            $assignedRootIds = $user->assignedForms()->pluck('forms.id')->toArray();

            if (empty($assignedRootIds)) {
                return $query->whereRaw('1 = 0'); // No forms assigned → see nothing
            }

            // Admin can see: all forms whose parent_id is in the assigned root IDs
            // (and recursively deeper children, but for the list we show direct children + descendants)
            // We also allow seeing the root forms themselves (to navigate into them)
            // but delete will be blocked on root forms.
            $descendantIds = static::getAllDescendantIds($assignedRootIds);

            return $query->whereIn('id', array_merge($assignedRootIds, $descendantIds));
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
        return FormForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FormsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForms::route('/'),
            'create' => CreateForm::route('/create'),
            'edit' => EditForm::route('/{record}/edit'),
        ];
    }
}

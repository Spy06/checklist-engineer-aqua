<?php

namespace App\Filament\Resources\Forms\Pages;

use App\Filament\Resources\Forms\FormResource;
use App\Models\Form;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListForms extends ListRecords
{
    protected static string $resource = FormResource::class;

    protected function getHeaderActions(): array
    {
        $parentId = request()->query('parent_id');

        $actions = [];

        // Tombol kembali jika sedang melihat child
        if ($parentId) {
            $parent = Form::find($parentId);
            if ($parent instanceof Form) {
                $backUrl = $parent->parent_id
                    ? route('filament.admin.resources.forms.index', ['parent_id' => $parent->parent_id])
                    : route('filament.admin.resources.forms.index');

                $actions[] = Action::make('kembali')
                    ->label('← Kembali')
                    ->color('gray')
                    ->url($backUrl);
            }
        }

        $actions[] = CreateAction::make()
            ->label('+ Form Baru')
            ->url(fn () => $parentId
                ? route('filament.admin.resources.forms.create', ['parent_id' => $parentId])
                : route('filament.admin.resources.forms.create')
            );

        return $actions;
    }

    /**
     * @return Builder<Form>
     */
    protected function getTableQuery(): Builder
    {
        // Get the base query from the Resource, which already handles admin scoping
        $query = parent::getTableQuery();

        $parentId = request()->query('parent_id');

        if ($parentId) {
            return $query->where('parent_id', $parentId);
        }

        // Default: hanya root-level forms (yang tidak punya parent)
        return $query->whereNull('parent_id');
    }

    public function getHeading(): string
    {
        $parentId = request()->query('parent_id');

        if ($parentId) {
            $parent = Form::find($parentId);
            if ($parent instanceof Form) {
                return 'Isi dari: '.$parent->title;
            }
        }

        return 'Daftar Forms';
    }

    public function getSubheading(): ?string
    {
        $parentId = request()->query('parent_id');

        if ($parentId) {
            $parent = Form::find($parentId);
            if ($parent instanceof Form) {
                $breadcrumbs = [];
                $current = $parent;

                while ($current instanceof Form) {
                    array_unshift($breadcrumbs, $current->title);
                    $current = $current->parent;
                }

                return 'Jalur: '.implode(' › ', $breadcrumbs);
            }
        }

        return 'Menampilkan 12 form utama yang tersedia.';
    }
}

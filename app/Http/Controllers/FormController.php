<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FormController extends Controller
{
    /**
     * Display a listing of the forms.
     * - Super admin: see all root forms
     * - Admin & Responden: see only assigned root forms
     */
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            $forms = Form::whereNull('parent_id')->orderBy('order')->get();
        } else {
            // admin & responden: show only assigned root forms
            $forms = $user->assignedForms()->orderBy('order')->get();
        }

        $formsArray = [];
        foreach ($forms as $form) {
            $formsArray[$form->slug] = $this->formToArray($form);
        }

        return view('dashboard', ['forms' => $formsArray]);
    }

    /**
     * Check if the currently authenticated user is allowed to access a given form (by its root ancestor).
     */
    private function userCanAccessForm(Form $form): bool
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Walk up to find the root form
        $root = $form;
        $visited = [];
        while ($root->parent_id !== null && !in_array($root->id, $visited)) {
            $visited[] = $root->id;
            $root = $root->parent;
            if (!($root instanceof Form)) {
                break;
            }
        }

        // Check if this root form is in the user's assignment
        return $user->assignedForms()->where('forms.id', $root->id)->exists();
    }

    /**
     * Resolve the hex color for a form (walk up to parent if needed).
     *
     * @param  array<int>  $visited
     */
    private function resolveColor(Form $form, array $visited = []): string
    {
        if (in_array($form->id, $visited)) {
            return '#10b981';
        }

        if (! empty($form->color)) {
            $color = strtolower(trim($form->color));

            $tailwindColors = [
                'slate' => '#64748b', 'gray' => '#6b7280', 'zinc' => '#71717a', 'neutral' => '#737373', 'stone' => '#78716c',
                'red' => '#ef4444', 'orange' => '#f97316', 'amber' => '#f59e0b', 'yellow' => '#eab308', 'lime' => '#84cc16',
                'green' => '#22c55e', 'emerald' => '#10b981', 'teal' => '#14b8a6', 'cyan' => '#06b6d4', 'sky' => '#0ea5e9',
                'blue' => '#3b82f6', 'indigo' => '#6366f1', 'violet' => '#8b5cf6', 'purple' => '#a855f7', 'fuchsia' => '#d946ef',
                'pink' => '#ec4899', 'rose' => '#f43f5e',
            ];

            if (isset($tailwindColors[$color])) {
                return $tailwindColors[$color];
            }

            if (! preg_match('/^(#|rgb|hsl)/i', $color) && ctype_xdigit(str_replace(' ', '', $color)) && in_array(strlen($color), [3, 6, 8])) {
                return '#'.$color;
            }

            return $form->color;
        }

        $visited[] = $form->id;

        if ($form->parent_id) {
            $parent = $form->parent;
            if ($parent instanceof Form) {
                return $this->resolveColor($parent, $visited);
            }
        }

        return '#10b981';
    }

    /**
     * Determine the icon based on form type.
     */
    private function resolveIcon(Form $form): string
    {
        if ($form->is_category && $form->children()->exists()) {
            return 'chevron-double-down';
        }

        return 'check';
    }

    /**
     * @param  array<int>  $visited
     * @return array<string, mixed>
     */
    private function formToArray(Form $form, int $depth = 0, array $visited = []): array
    {
        if ($depth > 10 || in_array($form->id, $visited)) {
            return [
                'title'       => $form->title,
                'description' => $form->description ?? '',
                'icon'        => $this->resolveIcon($form),
                'hex_color'   => $this->resolveColor($form),
                'is_category' => $form->is_category,
            ];
        }

        $visited[] = $form->id;

        $data = [
            'title'       => $form->title,
            'description' => $form->description ?? '',
            'icon'        => $this->resolveIcon($form),
            'hex_color'   => $this->resolveColor($form),
            'is_category' => $form->is_category,
        ];

        if ($form->options) {
            $data['options'] = $form->options;
        }

        if ($form->children()->count() > 0) {
            $items = [];
            foreach ($form->children as $child) {
                if (! in_array($child->id, $visited)) {
                    $items[$child->slug] = $this->formToArray($child, $depth + 1, $visited);
                }
            }
            $data['items'] = $items;
        }

        return $data;
    }

    /**
     * Display the specified form or category page based on path.
     */
    public function show(string $path): View
    {
        $segments = explode('/', $path);

        $currentForm = null;
        $parentId = null;

        foreach ($segments as $segment) {
            $query = Form::where('slug', $segment);
            if ($parentId === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $parentId);
            }

            $currentForm = $query->first();

            if (! $currentForm) {
                abort(404, 'Form atau Kategori tidak ditemukan.');
            }
            $parentId = $currentForm->id;
        }

        // Authorization check
        if (! $this->userCanAccessForm($currentForm)) {
            abort(403, 'Anda tidak memiliki akses ke form ini.');
        }

        $hexColor = $this->resolveColor($currentForm);
        $icon = $this->resolveIcon($currentForm);

        $hasChildren = $currentForm->children()->exists();
        $isActuallyChildable = $currentForm->is_category && $hasChildren;

        $currentDefArray = $this->formToArray($currentForm);
        $currentDefArray['hex_color'] = $hexColor;
        $currentDefArray['icon'] = $icon;

        if ($isActuallyChildable) {
            return view('forms.category', [
                'path'       => $path,
                'currentDef' => $currentDefArray,
            ]);
        }

        $defaultOptions = [
            $currentForm->title.' - Opsi 1',
            $currentForm->title.' - Opsi 2',
            $currentForm->title.' - Opsi 3',
        ];

        $formArray = [
            'title'       => $currentForm->title ?? 'Form',
            'description' => 'Checklist untuk '.($currentForm->title ?? ''),
            'icon'        => $icon,
            'hex_color'   => $hexColor,
            'items'       => ! empty($currentForm->options) ? $currentForm->options : $defaultOptions,
        ];

        $submissions = FormSubmission::where('user_id', Auth::id())
            ->where('form_id', $currentForm->id)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('forms.show', [
            'path'        => $path,
            'form'        => $formArray,
            'submissions' => $submissions,
        ]);
    }

    /**
     * Store a newly created form submission.
     */
    public function store(Request $request, string $path): RedirectResponse
    {
        $segments = explode('/', $path);
        $currentForm = null;
        $parentId = null;

        foreach ($segments as $segment) {
            $query = Form::where('slug', $segment);
            if ($parentId === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $parentId);
            }

            $currentForm = $query->first();

            if (! $currentForm) {
                abort(404, 'Form tidak ditemukan.');
            }
            $parentId = $currentForm->id;
        }

        // Authorization check
        if (! $this->userCanAccessForm($currentForm)) {
            abort(403, 'Anda tidak memiliki akses ke form ini.');
        }

        if ($currentForm->is_category && $currentForm->children()->exists()) {
            abort(400, 'Tidak dapat mengirim form pada kategori.');
        }

        $request->validate([
            'date'    => 'required|date',
            'answers' => 'nullable|array',
            'notes'   => 'nullable|string',
        ]);

        $formType = str_replace('/', '.', $path);

        FormSubmission::create([
            'user_id'   => Auth::id(),
            'form_id'   => $currentForm->id,
            'form_type' => $formType,
            'date'      => $request->date,
            'answers'   => $request->input('answers', []),
            'notes'     => $request->notes,
        ]);

        // Notify super_admins + admins assigned to the root form that contains this form
        $this->sendNotifications($currentForm);

        return redirect()->route('forms.show', $path)->with('success', 'Checklist berhasil disimpan ke database!');
    }

    /**
     * Send notifications to super_admins and relevant admins.
     */
    private function sendNotifications(Form $form): void
    {
        // Find the root form for this submission
        $root = $form;
        $visited = [];
        while ($root->parent_id !== null && !in_array($root->id, $visited)) {
            $visited[] = $root->id;
            $parent = $root->parent;
            if (!($parent instanceof Form)) {
                break;
            }
            $root = $parent;
        }

        // Get all super_admins
        $superAdmins = User::where('role', 'super_admin')->get();

        // Get admins assigned to this root form
        $assignedAdmins = $root->assignedUsers()->where('role', 'admin')->get();

        $recipients = $superAdmins->merge($assignedAdmins)->unique('id');

        foreach ($recipients as $recipient) {
            Notification::make()
                ->title('Checklist Baru: '.($form->title ?? 'Form'))
                ->body(Auth::user()->name.' telah mengisi checklist.')
                ->info()
                ->sendToDatabase($recipient);
        }
    }
}

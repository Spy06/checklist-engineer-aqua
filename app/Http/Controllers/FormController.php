<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FormController extends Controller
{
    /**
     * Display a listing of the forms.
     */
    public function index()
    {
        $forms = Form::whereNull('parent_id')->orderBy('order')->get();

        $formsArray = [];
        foreach ($forms as $form) {
            $formsArray[$form->slug] = $this->formToArray($form);
        }

        return view('dashboard', ['forms' => $formsArray]);
    }

    /**
     * Resolve the hex color for a form (walk up to parent if needed).
     * Returns a hex string like "#10b981" or rgba string.
     */
    private function resolveColor(Form $form, array $visited = []): string
    {
        if (in_array($form->id, $visited)) {
            return '#10b981'; // default emerald
        }

        // The color field now stores hex or rgba from ColorPicker
        if (!empty($form->color)) {
            $color = strtolower(trim($form->color));
            
            // Map common color names that users might type (like "emerald") to actual hex values (Tailwind 500 shades)
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
            
            // If they forgot the hash for hex, add it
            if (!preg_match('/^(#|rgb|hsl)/i', $color) && ctype_xdigit(str_replace(' ', '', $color)) && in_array(strlen($color), [3, 6, 8])) {
                return '#' . $color;
            }

            return $form->color;
        }

        $visited[] = $form->id;

        // Walk up to parent
        if ($form->parent_id) {
            $parent = $form->parent;
            if ($parent) {
                return $this->resolveColor($parent, $visited);
            }
        }

        return '#10b981'; // fallback: emerald green
    }

    /**
     * Determine the icon based on form type.
     * - Childable (is_category=true AND has children) → chevron-double-down
     * - Option (is_category=false OR no children) → check
     */
    private function resolveIcon(Form $form): string
    {
        if ($form->is_category && $form->children()->exists()) {
            return 'chevron-double-down';
        }
        return 'check';
    }

    private function formToArray(Form $form, int $depth = 0, array $visited = [])
    {
        // Prevent infinite recursion
        if ($depth > 10 || in_array($form->id, $visited)) {
            return [
                'title' => $form->title,
                'description' => $form->description ?? '',
                'icon' => $this->resolveIcon($form),
                'hex_color' => $this->resolveColor($form),
                'is_category' => $form->is_category,
            ];
        }

        $visited[] = $form->id;

        $data = [
            'title' => $form->title,
            'description' => $form->description ?? '',
            'icon' => $this->resolveIcon($form),
            'hex_color' => $this->resolveColor($form),
            'is_category' => $form->is_category,
        ];

        if ($form->options) {
            $data['options'] = $form->options;
        }

        if ($form->children()->count() > 0) {
            $items = [];
            foreach ($form->children as $child) {
                if (!in_array($child->id, $visited)) {
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
    public function show(string $path)
    {
        $segments = explode('/', $path);

        $currentForm = null;
        $parentId = null;

        // Traverse the database hierarchy
        foreach ($segments as $segment) {
            $query = Form::where('slug', $segment);
            if ($parentId === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $parentId);
            }

            $currentForm = $query->first();

            if (!$currentForm) {
                abort(404, 'Form atau Kategori tidak ditemukan.');
            }
            $parentId = $currentForm->id;
        }

        // Resolve color and icon for this form
        $hexColor = $this->resolveColor($currentForm);
        $icon = $this->resolveIcon($currentForm);

        // A form is "truly childable" only if it IS a category AND actually has children
        $hasChildren = $currentForm->children()->exists();
        $isActuallyChildable = $currentForm->is_category && $hasChildren;

        $currentDefArray = $this->formToArray($currentForm);
        $currentDefArray['hex_color'] = $hexColor;
        $currentDefArray['icon'] = $icon;

        if ($isActuallyChildable) {
            return view('forms.category', [
                'path' => $path,
                'currentDef' => $currentDefArray,
            ]);
        }

        // Default options: if form has no options defined, auto-generate 3 defaults
        $defaultOptions = [
            $currentForm->title . ' - Opsi 1',
            $currentForm->title . ' - Opsi 2',
            $currentForm->title . ' - Opsi 3',
        ];

        $formArray = [
            'title' => $currentForm->title ?? 'Form',
            'description' => "Checklist untuk " . ($currentForm->title ?? ''),
            'icon' => $icon,
            'hex_color' => $hexColor,
            'items' => !empty($currentForm->options) ? $currentForm->options : $defaultOptions
        ];

        $submissions = FormSubmission::where('user_id', Auth::id())
            ->where('form_id', $currentForm->id)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('forms.show', [
            'path' => $path,
            'form' => $formArray,
            'submissions' => $submissions,
        ]);
    }

    /**
     * Store a newly created form submission.
     */
    public function store(Request $request, string $path)
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

            if (!$currentForm) {
                abort(404, 'Form tidak ditemukan.');
            }
            $parentId = $currentForm->id;
        }

        if ($currentForm->is_category && $currentForm->children()->exists()) {
            abort(400, 'Tidak dapat mengirim form pada kategori.');
        }

        $request->validate([
            'date' => 'required|date',
            'answers' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $formType = str_replace('/', '.', $path);

        FormSubmission::create([
            'user_id' => Auth::id(),
            'form_id' => $currentForm->id,
            'form_type' => $formType,
            'date' => $request->date,
            'answers' => $request->input('answers', []),
            'notes' => $request->notes,
        ]);

        return redirect()->route('forms.show', $path)->with('success', 'Checklist berhasil disimpan ke database!');
    }
}

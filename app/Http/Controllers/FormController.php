<?php

namespace App\Http\Controllers;

use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FormController extends Controller
{
    /**
     * Display a listing of the forms.
     */
    public function index()
    {
        $forms = config('forms');

        return view('dashboard', compact('forms'));
    }

    /**
     * Display the specified form or category page based on path.
     */
    public function show(string $path)
    {
        $forms = config('forms');
        $segments = explode('/', $path);
        
        $currentDef = null;
        $parentDef = null;
        $currentList = $forms;
        
        // Traverse the configuration tree
        foreach ($segments as $segment) {
            if (! isset($currentList[$segment])) {
                abort(404, 'Form atau Kategori tidak ditemukan.');
            }
            $parentDef = $currentDef;
            $currentDef = $currentList[$segment];
            
            // Nested items are inside the 'items' key, but for the root, $forms itself is the list.
            $currentList = $currentDef['items'] ?? [];
        }

        // Determine if it is a category or a checklist form
        $isCategory = $currentDef['is_category'] ?? false;
        
        // Implicitly a category if it has 'items' but no explicit 'options'
        if (isset($currentDef['items']) && !isset($currentDef['options'])) {
            $isCategory = true;
        }

        if ($isCategory) {
            // Render category menu
            return view('forms.category', compact('path', 'currentDef', 'parentDef'));
        }

        // Render checklist form
        $form = [
            'title' => $currentDef['title'] ?? 'Form',
            'description' => "Checklist untuk " . ($currentDef['title'] ?? ''),
            'icon' => $currentDef['icon'] ?? ($parentDef['icon'] ?? 'clipboard-document-list'),
            'color' => $currentDef['color'] ?? ($parentDef['color'] ?? 'blue'),
            'bg_color' => $currentDef['bg_color'] ?? ($parentDef['bg_color'] ?? 'bg-blue-50 dark:bg-blue-950/30'),
            'border_color' => $currentDef['border_color'] ?? ($parentDef['border_color'] ?? 'border-blue-200 dark:border-blue-800'),
            'text_color' => $currentDef['text_color'] ?? ($parentDef['text_color'] ?? 'text-blue-700 dark:text-blue-300'),
            'items' => $currentDef['options'] ?? []
        ];

        // Store submissions under a dot-separated path (e.g. 'work-at-height.fix-ladder.cimex')
        $formType = str_replace('/', '.', $path);
        
        $submissions = FormSubmission::where('user_id', Auth::id())
            ->where('form_type', $formType)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('forms.show', compact('path', 'form', 'submissions', 'parentDef'));
    }

    /**
     * Store a newly created form submission.
     */
    public function store(Request $request, string $path)
    {
        $forms = config('forms');
        $segments = explode('/', $path);
        
        $currentDef = null;
        $currentList = $forms;
        
        foreach ($segments as $segment) {
            if (! isset($currentList[$segment])) {
                abort(404, 'Form tidak ditemukan.');
            }
            $currentDef = $currentList[$segment];
            $currentList = $currentDef['items'] ?? [];
        }

        $request->validate([
            'date' => 'required|date',
            'answers' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $formType = str_replace('/', '.', $path);

        FormSubmission::create([
            'user_id' => Auth::id(),
            'form_type' => $formType,
            'date' => $request->date,
            'answers' => $request->input('answers', []),
            'notes' => $request->notes,
        ]);

        return redirect()->route('forms.show', $path)->with('success', 'Checklist berhasil disimpan ke database!');
    }
}

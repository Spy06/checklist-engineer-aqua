use App\Models\Form;
use App\Models\FormSubmission;

echo "--- Current Forms ---\n";
$forms = Form::all();
foreach ($forms as $form) {
    $submissionsCount = FormSubmission::where('form_id', $form->id)->count();
    $googleFormId = isset($form->options['google_form_id']) ? $form->options['google_form_id'] : 'none';
    echo "ID: {$form->id} | Parent: {$form->parent_id} | Title: {$form->title} | IsCategory: {$form->is_category} | Submissions: {$submissionsCount} | GoogleID: {$googleFormId}\n";
}
echo "\n--- End ---\n";

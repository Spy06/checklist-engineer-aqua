<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'form_type', 'date', 'answers', 'notes'])]
class FormSubmission extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'answers' => 'array',
        ];
    }

    /**
     * Get the user who submitted this form.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

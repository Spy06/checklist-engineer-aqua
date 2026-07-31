<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_category' => 'boolean',
        'options' => 'array',
    ];

    /**
     * @return BelongsTo<Form, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'parent_id');
    }

    /**
     * @return HasMany<Form, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Form::class, 'parent_id')->orderBy('order');
    }

    /**
     * @return HasMany<FormSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    /**
     * Users that have been assigned to this (root) form.
     *
     * @return BelongsToMany<User, $this>
     */
    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_form_assignments');
    }
}

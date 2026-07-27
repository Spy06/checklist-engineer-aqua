<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_category' => 'boolean',
        'options' => 'array',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Form::class, 'parent_id')->orderBy('order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }
}

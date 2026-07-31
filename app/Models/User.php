<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string $password
 * @property string $role  (super_admin | admin | responden)
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'username', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * Determine if the user can access the Filament admin panel.
     * Super admin and admin (panel-limited) can both access.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin();
    }

    // -----------------------------------------------
    // Role helpers
    // -----------------------------------------------

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isResponden(): bool
    {
        return $this->role === 'responden';
    }

    // -----------------------------------------------
    // Relationships
    // -----------------------------------------------

    /**
     * The root forms that have been assigned to this user (for admin or responden).
     *
     * @return BelongsToMany<Form, $this>
     */
    public function assignedForms(): BelongsToMany
    {
        return $this->belongsToMany(Form::class, 'user_form_assignments');
    }
}

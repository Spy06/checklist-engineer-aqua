<?php

namespace App\Http\Responses;

use App\Models\User;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        /** @var User|null $user */
        $user = auth()->user();

        // admin and super_admin go directly to the Filament panel
        if ($user && ($user->isSuperAdmin() || $user->isAdmin())) {
            return redirect('/admin');
        }

        // responden go to the frontend dashboard
        return redirect('/dashboard');
    }
}

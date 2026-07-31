<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;

class FilamentLogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        return redirect()->to('/login');
    }
}

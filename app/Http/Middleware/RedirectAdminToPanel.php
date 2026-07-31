<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirect admin and super_admin users to the Filament admin panel
 * when they try to access the frontend /dashboard or / routes.
 * Responden users continue to the frontend as normal.
 */
class RedirectAdminToPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user && ($user->isSuperAdmin() || $user->isAdmin())) {
            // If they're heading to the frontend dashboard, redirect to admin panel
            $path = $request->path();

            if (in_array($path, ['dashboard', '/dashboard', 'matrix', '/matrix'])
                || str_starts_with($path, 'forms/')
            ) {
                return redirect('/admin');
            }
        }

        return $next($request);
    }
}

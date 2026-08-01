<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea el acceso a la app a los usuarios que no están "activos":
 * - pending  → pantalla "Esperando aprobación"
 * - blocked  → pantalla "Bloqueado"
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            return $user->isBlocked()
                ? redirect()->route('blocked')
                : redirect()->route('pending');
        }

        return $next($request);
    }
}

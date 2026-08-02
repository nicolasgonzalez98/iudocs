<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solo corta el paso a los usuarios BLOQUEADOS.
 * Los "pending" navegan libremente (quedan igual en la lista de
 * "Solicitudes pendientes" del panel para que la admin los revise).
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isBlocked()) {
            return redirect()->route('blocked');
        }

        return $next($request);
    }
}

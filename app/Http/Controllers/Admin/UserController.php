<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Panel de admin: pendientes destacados + resto de los usuarios.
     */
    public function index(): Response
    {
        $users = User::orderBy('name')->get();

        $map = fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'avatar' => $u->avatar,
            'role' => $u->role,
            'status' => $u->status,
            'created_at' => $u->created_at?->toDateString(),
        ];

        return Inertia::render('Admin/Users', [
            'pending' => $users->where('status', 'pending')->map($map)->values(),
            'others' => $users->where('status', '!=', 'pending')->map($map)->values(),
            'counts' => [
                'pending' => $users->where('status', 'pending')->count(),
                'active' => $users->where('status', 'active')->count(),
                'blocked' => $users->where('status', 'blocked')->count(),
            ],
        ]);
    }

    /**
     * Aprobar (pending → active) o desbloquear (blocked → active).
     */
    public function activate(User $user): RedirectResponse
    {
        $user->update(['status' => 'active']);

        return back();
    }

    /**
     * Bloquear / rechazar (→ blocked). Un admin no puede bloquearse a sí mismo.
     */
    public function block(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'No podés bloquearte a vos misma.']);
        }

        $user->update(['status' => 'blocked']);

        return back();
    }
}

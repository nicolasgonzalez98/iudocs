<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleController extends Controller
{
    /**
     * Manda al usuario a Google para autenticarse.
     * Si viene ?popup=1, recuerda que el flujo es en ventana emergente.
     */
    public function redirect(Request $request): RedirectResponse
    {
        if ($request->boolean('popup')) {
            $request->session()->put('google_login_popup', true);
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Vuelta de Google: crea o vincula el usuario y lo loguea.
     */
    public function callback(Request $request): RedirectResponse|Responsable|\Illuminate\Http\Response
    {
        $isPopup = (bool) $request->session()->pull('google_login_popup', false);

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            if ($isPopup) {
                // Cierra el popup y manda la ventana principal al login
                return response()->view('auth.google-popup-callback', ['redirect' => route('login')]);
            }

            return redirect()->route('login')->withErrors([
                'email' => 'No pudimos conectar con Google. Probá de nuevo.',
            ]);
        }

        $adminEmail = config('iudocs.admin_email');
        $isAdmin = $adminEmail
            && strtolower((string) $googleUser->getEmail()) === strtolower($adminEmail);

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            // Vincular la cuenta de Google (por si se había registrado con email/password)
            $user->google_id = $googleUser->getId();
            $user->avatar = $googleUser->getAvatar();
            if ($isAdmin) {
                $user->role = 'admin';
                $user->status = 'active';
            }
            $user->save();
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?: 'Estudiante',
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'password' => null,
                'email_verified_at' => now(),
                'role' => $isAdmin ? 'admin' : 'member',
                'status' => $isAdmin ? 'active' : 'pending',
            ]);
        }

        Auth::login($user, remember: true);

        if ($isPopup) {
            // Cierra el popup y lleva la ventana principal al dashboard
            return response()->view('auth.google-popup-callback', ['redirect' => route('dashboard')]);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}

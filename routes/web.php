<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'active'])->name('dashboard');

// Pantallas de acceso (fuera del gating 'active' para no crear loops)
Route::middleware('auth')->group(function () {
    Route::get('/pending', function (Request $request) {
        $user = $request->user();
        if ($user->isActive()) {
            return redirect()->route('dashboard');
        }
        if ($user->isBlocked()) {
            return redirect()->route('blocked');
        }

        return Inertia::render('Auth/PendingApproval');
    })->name('pending');

    Route::get('/blocked', function (Request $request) {
        if (! $request->user()->isBlocked()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/Blocked');
    })->name('blocked');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Panel de admin (solo admins)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users');
    Route::patch('/users/{user}/activate', [AdminUserController::class, 'activate'])->name('users.activate');
    Route::patch('/users/{user}/block', [AdminUserController::class, 'block'])->name('users.block');
});

require __DIR__.'/auth.php';

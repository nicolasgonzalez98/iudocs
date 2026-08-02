<?php

use App\Http\Controllers\Admin\CarreraController as AdminCarreraController;
use App\Http\Controllers\Admin\MateriaController as AdminMateriaController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SubcarpetaController;
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

// Home: grilla de materias (solo usuarios activos)
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [MateriaController::class, 'index'])->name('dashboard');
    Route::get('/buscar', [SearchController::class, 'index'])->name('search');
    Route::get('/materias/{materia}', [MateriaController::class, 'show'])->name('materias.show');

    // Materiales (apuntes / exámenes)
    Route::post('/materias/{materia}/materiales', [MaterialController::class, 'store'])->name('materiales.store');
    Route::get('/materiales/{material}/descargar', [MaterialController::class, 'download'])->name('materiales.download');
    Route::post('/materiales/{material}/voto', [MaterialController::class, 'toggleVote'])->name('materiales.vote');
    Route::post('/materiales/{material}/favorito', [MaterialController::class, 'toggleFavorite'])->name('materiales.favorite');
    Route::get('/mis-apuntes', [MaterialController::class, 'mine'])->name('materiales.mine');
    Route::patch('/materiales/{material}', [MaterialController::class, 'update'])->name('materiales.update');
    Route::patch('/materiales/{material}/mover', [MaterialController::class, 'move'])->name('materiales.move');
    Route::patch('/materias/{materia}/mover-materiales', [MaterialController::class, 'moveBatch'])->name('materiales.move-batch');
    Route::delete('/materiales/{material}', [MaterialController::class, 'destroy'])->name('materiales.destroy');

    // Subcarpetas (crear/renombrar/borrar/ordenar → solo admin, vía Policy)
    Route::post('/materias/{materia}/subcarpetas', [SubcarpetaController::class, 'store'])->name('subcarpetas.store');
    Route::patch('/materias/{materia}/subcarpetas/orden', [SubcarpetaController::class, 'reorder'])->name('subcarpetas.reorder');
    Route::patch('/subcarpetas/{subcarpeta}', [SubcarpetaController::class, 'update'])->name('subcarpetas.update');
    Route::delete('/subcarpetas/{subcarpeta}', [SubcarpetaController::class, 'destroy'])->name('subcarpetas.destroy');

    // Comentarios
    Route::post('/materiales/{material}/comentarios', [CommentController::class, 'store'])->name('comentarios.store');
    Route::delete('/comentarios/{comment}', [CommentController::class, 'destroy'])->name('comentarios.destroy');
});

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
    Route::patch('/users/{user}/role', [AdminUserController::class, 'role'])->name('users.role');

    Route::get('/materias', [AdminMateriaController::class, 'index'])->name('materias');
    Route::post('/materias', [AdminMateriaController::class, 'store'])->name('materias.store');
    Route::patch('/materias/{materia}', [AdminMateriaController::class, 'update'])->name('materias.update');
    Route::delete('/materias/{materia}', [AdminMateriaController::class, 'destroy'])->name('materias.destroy');

    Route::get('/carreras', [AdminCarreraController::class, 'index'])->name('carreras');
    Route::post('/carreras', [AdminCarreraController::class, 'store'])->name('carreras.store');
    Route::patch('/carreras/{carrera}', [AdminCarreraController::class, 'update'])->name('carreras.update');
    Route::delete('/carreras/{carrera}', [AdminCarreraController::class, 'destroy'])->name('carreras.destroy');
});

require __DIR__.'/auth.php';

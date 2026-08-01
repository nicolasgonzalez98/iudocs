<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MateriaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Materias', [
            'materias' => Materia::orderBy('anio')
                ->orderBy('cuatrimestre')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Materia::create($this->validated($request));

        return back();
    }

    public function update(Request $request, Materia $materia): RedirectResponse
    {
        $materia->update($this->validated($request));

        return back();
    }

    public function destroy(Materia $materia): RedirectResponse
    {
        $materia->delete();

        return back();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'anio' => ['nullable', 'integer', 'min:1', 'max:7'],
            'cuatrimestre' => ['nullable', 'integer', 'min:1', 'max:2'],
            'catedra' => ['nullable', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:20'],
            'icon' => ['nullable', 'string', 'max:16'],
        ]);
    }
}

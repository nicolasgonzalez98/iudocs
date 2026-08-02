<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Materia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CarreraController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Carreras', [
            'carreras' => Carrera::with('materias:id')
                ->withCount('materias')
                ->orderBy('nombre')
                ->get()
                ->map(fn (Carrera $c) => [
                    'id' => $c->id,
                    'nombre' => $c->nombre,
                    'materias_count' => $c->materias_count,
                    'materia_ids' => $c->materias->pluck('id'),
                ]),
            'materias' => Materia::orderBy('anio')
                ->orderBy('cuatrimestre')
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'anio', 'cuatrimestre']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $carrera = Carrera::create(['nombre' => $data['nombre']]);
        $carrera->materias()->sync($data['materia_ids'] ?? []);

        return back();
    }

    public function update(Request $request, Carrera $carrera): RedirectResponse
    {
        $data = $this->validated($request);

        $carrera->update(['nombre' => $data['nombre']]);
        $carrera->materias()->sync($data['materia_ids'] ?? []);

        return back();
    }

    public function destroy(Carrera $carrera): RedirectResponse
    {
        // Borra la carrera y sus vínculos (pivote en cascade). Las materias quedan.
        $carrera->delete();

        return back();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'materia_ids' => ['nullable', 'array'],
            'materia_ids.*' => ['integer'],
        ]);
    }
}

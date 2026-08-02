<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Materia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class MateriaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Materias', [
            'materias' => Materia::with('carreras:id')
                ->orderBy('anio')
                ->orderBy('cuatrimestre')
                ->orderBy('nombre')
                ->get()
                ->map(fn (Materia $m) => [
                    'id' => $m->id,
                    'nombre' => $m->nombre,
                    'anio' => $m->anio,
                    'cuatrimestre' => $m->cuatrimestre,
                    'catedra' => $m->catedra,
                    'color' => $m->color,
                    'icon' => $m->icon,
                    'carrera_ids' => $m->carreras->pluck('id'),
                ]),
            'carreras' => Carrera::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $materia = Materia::create(Arr::except($data, 'carrera_ids'));
        $materia->carreras()->sync($data['carrera_ids']);

        return back();
    }

    public function update(Request $request, Materia $materia): RedirectResponse
    {
        $data = $this->validated($request);

        $materia->update(Arr::except($data, 'carrera_ids'));
        $materia->carreras()->sync($data['carrera_ids']);

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
            'carrera_ids' => ['required', 'array', 'min:1'],
            'carrera_ids.*' => ['integer'],
        ], [
            'carrera_ids.required' => 'Elegí al menos una carrera.',
            'carrera_ids.min' => 'Elegí al menos una carrera.',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Materia;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $materias = [];
        $materiales = [];

        if (mb_strlen($q) >= 2) {
            $like = '%'.$q.'%';

            $materias = Materia::where('nombre', 'like', $like)
                ->orderBy('nombre')
                ->limit(20)
                ->get(['id', 'nombre', 'anio', 'cuatrimestre', 'color', 'icon']);

            $materiales = Material::with(['materia:id,nombre,color,icon', 'user:id,name', 'subcarpeta:id,nombre'])
                ->where(function ($w) use ($like) {
                    $w->where('titulo', 'like', $like)
                        ->orWhere('descripcion', 'like', $like);
                })
                ->latest()
                ->limit(40)
                ->get()
                ->map(fn (Material $m) => [
                    'id' => $m->id,
                    'tipo' => $m->tipo,
                    'titulo' => $m->titulo,
                    'descripcion' => $m->descripcion,
                    'uploader' => $m->user->name,
                    'carpeta' => $m->subcarpeta?->nombre,
                    'materia' => [
                        'id' => $m->materia->id,
                        'nombre' => $m->materia->nombre,
                        'color' => $m->materia->color,
                        'icon' => $m->materia->icon,
                    ],
                ]);
        }

        return Inertia::render('Search', [
            'q' => $q,
            'materias' => $materias,
            'materiales' => $materiales,
        ]);
    }
}

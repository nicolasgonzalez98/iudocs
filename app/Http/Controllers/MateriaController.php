<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Materia;
use Inertia\Inertia;
use Inertia\Response;

class MateriaController extends Controller
{
    /**
     * Grilla de materias (home para usuarios activos).
     */
    public function index(): Response
    {
        return Inertia::render('Materias/Index', [
            'materias' => Materia::orderBy('anio')
                ->orderBy('cuatrimestre')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    /**
     * Detalle de una materia con sus apuntes y exámenes.
     */
    public function show(Materia $materia): Response
    {
        $user = auth()->user();

        $materials = $materia->materials()
            ->with([
                'user:id,name,avatar',
                'comments' => fn ($q) => $q->oldest(),
                'comments.user:id,name,avatar',
            ])
            ->latest()
            ->get();

        $map = fn (Material $m) => [
            'id' => $m->id,
            'tipo' => $m->tipo,
            'titulo' => $m->titulo,
            'descripcion' => $m->descripcion,
            'original_name' => $m->original_name,
            'mime' => $m->mime,
            'size' => $m->size,
            'created_at' => $m->created_at->toDateString(),
            'uploader' => [
                'name' => $m->user->name,
                'avatar' => $m->user->avatar,
            ],
            'can_delete' => $user->isAdmin() || $m->user_id === $user->id,
            'comments_count' => $m->comments->count(),
            'comments' => $m->comments->map(fn (\App\Models\Comment $c) => [
                'id' => $c->id,
                'body' => $c->body,
                'created_at' => $c->created_at->format('d/m/Y H:i'),
                'author' => [
                    'name' => $c->user->name,
                    'avatar' => $c->user->avatar,
                ],
                'can_delete' => $user->isAdmin() || $c->user_id === $user->id,
            ])->values(),
        ];

        return Inertia::render('Materias/Show', [
            'materia' => $materia->only('id', 'nombre', 'anio', 'cuatrimestre', 'color', 'icon'),
            'apuntes' => $materials->where('tipo', 'apunte')->map($map)->values(),
            'campus' => $materials->where('tipo', 'campus')->map($map)->values(),
            'examenes' => $materials->where('tipo', 'examen')->map($map)->values(),
        ]);
    }
}

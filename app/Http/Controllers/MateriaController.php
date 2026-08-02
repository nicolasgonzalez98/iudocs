<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Materia;
use App\Models\Subcarpeta;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class MateriaController extends Controller
{
    /**
     * Grilla de materias (home para usuarios activos).
     */
    public function index(): Response
    {
        $recent = Material::with(['materia:id,nombre,color,icon', 'user:id,name'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Material $m) => [
                'id' => $m->id,
                'tipo' => $m->tipo,
                'titulo' => $m->titulo,
                'uploader' => $m->user->name,
                'materia' => [
                    'id' => $m->materia->id,
                    'nombre' => $m->materia->nombre,
                    'color' => $m->materia->color,
                    'icon' => $m->materia->icon,
                ],
            ]);

        $topContributors = User::withCount('materials')
            ->has('materials')
            ->orderByDesc('materials_count')
            ->limit(5)
            ->get(['id', 'name', 'avatar'])
            ->map(fn (User $u) => [
                'name' => $u->name,
                'avatar' => $u->avatar,
                'count' => $u->materials_count,
            ]);

        return Inertia::render('Materias/Index', [
            'materias' => Materia::orderBy('anio')
                ->orderBy('cuatrimestre')
                ->orderBy('nombre')
                ->get(),
            'recent' => $recent,
            'topContributors' => $topContributors,
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
                'voters:id',
                'favoritedBy:id',
            ])
            ->latest()
            ->get();

        $map = fn (Material $m) => [
            'id' => $m->id,
            'tipo' => $m->tipo,
            'subcarpeta_id' => $m->subcarpeta_id,
            'titulo' => $m->titulo,
            'descripcion' => $m->descripcion,
            'original_name' => $m->original_name,
            'mime' => $m->mime,
            'size' => $m->size,
            'downloads' => $m->downloads,
            'helpful_count' => $m->voters->count(),
            'has_voted' => $m->voters->contains('id', $user->id),
            'is_favorite' => $m->favoritedBy->contains('id', $user->id),
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

        $subcarpetas = $materia->subcarpetas()
            ->orderBy('posicion')
            ->orderBy('nombre')
            ->get(['id', 'tipo', 'nombre'])
            ->map(fn (Subcarpeta $s) => [
                'id' => $s->id,
                'tipo' => $s->tipo,
                'nombre' => $s->nombre,
            ]);

        return Inertia::render('Materias/Show', [
            'materia' => $materia->only('id', 'nombre', 'anio', 'cuatrimestre', 'catedra', 'color', 'icon'),
            'apuntes' => $materials->where('tipo', 'apunte')->map($map)->values(),
            'campus' => $materials->where('tipo', 'campus')->map($map)->values(),
            'examenes' => $materials->where('tipo', 'examen')->map($map)->values(),
            'subApuntes' => $subcarpetas->where('tipo', 'apunte')->values(),
            'subCampus' => $subcarpetas->where('tipo', 'campus')->values(),
            'subExamenes' => $subcarpetas->where('tipo', 'examen')->values(),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
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
        $canSeeUploader = auth()->user()->isAdmin();

        $recent = Material::with(['materia:id,nombre,color,icon', 'user:id,name'])
            ->when(! auth()->user()->canSeeExamenes(), fn ($q) => $q->where('tipo', '!=', 'examen'))
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Material $m) => [
                'id' => $m->id,
                'tipo' => $m->tipo,
                'titulo' => $m->titulo,
                'uploader' => $canSeeUploader ? $m->user->name : null,
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
                    'color' => $m->color,
                    'icon' => $m->icon,
                    'carrera_ids' => $m->carreras->pluck('id'),
                ]),
            'carreras' => Carrera::orderBy('nombre')->get(['id', 'nombre']),
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
        // Solo el admin puede ver quién subió cada archivo.
        $canSeeUploader = $user->isAdmin();

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
            'uploader' => $canSeeUploader ? [
                'name' => $m->user->name,
                'avatar' => $m->user->avatar,
            ] : null,
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

        // Los "pending" no ven la sección Exámenes (hasta que la admin los apruebe).
        $canExamenes = $user->canSeeExamenes();

        return Inertia::render('Materias/Show', [
            'materia' => $materia->only('id', 'nombre', 'anio', 'cuatrimestre', 'catedra', 'color', 'icon'),
            'canExamenes' => $canExamenes,
            'apuntes' => $materials->where('tipo', 'apunte')->map($map)->values(),
            'campus' => $materials->where('tipo', 'campus')->map($map)->values(),
            'examenes' => $canExamenes ? $materials->where('tipo', 'examen')->map($map)->values() : [],
            'subApuntes' => $subcarpetas->where('tipo', 'apunte')->values(),
            'subCampus' => $subcarpetas->where('tipo', 'campus')->values(),
            'subExamenes' => $canExamenes ? $subcarpetas->where('tipo', 'examen')->values() : [],
        ]);
    }
}

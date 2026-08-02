<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Materia;
use App\Models\Subcarpeta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    /**
     * Subir uno o varios materiales a una materia (todos del mismo tipo).
     * Cualquier usuario activo puede. Cada archivo lleva su propio título/descripción.
     */
    public function store(Request $request, Materia $materia): RedirectResponse
    {
        $data = $request->validate([
            'tipo' => ['required', 'in:apunte,campus,examen'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => [
                'file',
                'max:102400', // 100 MB c/u
                'mimes:pdf,jpg,jpeg,png,doc,docx,ppt,pptx,xls,xlsx',
            ],
            'titulos' => ['required', 'array'],
            'titulos.*' => ['required', 'string', 'max:255'],
            'descripciones' => ['nullable', 'array'],
            'descripciones.*' => ['nullable', 'string', 'max:1000'],
            'subcarpeta_id' => ['nullable', 'integer'],
        ]);

        // Carpeta destino (opcional): debe ser de esta materia y del mismo tipo.
        $subcarpetaId = null;
        if (! empty($data['subcarpeta_id'])) {
            $subcarpetaId = $materia->subcarpetas()
                ->where('tipo', $data['tipo'])
                ->find($data['subcarpeta_id'])?->id;
        }

        DB::transaction(function () use ($request, $materia, $data, $subcarpetaId) {
            foreach ($data['files'] as $i => $file) {
                $path = $file->store("materiales/{$materia->id}");

                $materia->materials()->create([
                    'user_id' => $request->user()->id,
                    'subcarpeta_id' => $subcarpetaId,
                    'tipo' => $data['tipo'],
                    'titulo' => $data['titulos'][$i],
                    'descripcion' => trim($data['descripciones'][$i] ?? '') ?: null,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        });

        return back();
    }

    /**
     * Editar título / descripción de un material: solo el dueño o un admin.
     */
    public function update(Request $request, Material $material): RedirectResponse
    {
        abort_unless($request->user()->can('update', $material), 403);

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ]);

        $material->update($data);

        return back();
    }

    /**
     * Mover un material a una subcarpeta (o sacarlo → subcarpeta_id null).
     * Permiso: dueño o admin. La carpeta destino debe ser de la misma
     * materia y del mismo tipo que el material.
     */
    public function move(Request $request, Material $material): RedirectResponse
    {
        abort_unless($request->user()->can('move', $material), 403);

        $data = $request->validate([
            'subcarpeta_id' => ['nullable', 'integer'],
        ]);

        $subcarpetaId = null;
        if (! empty($data['subcarpeta_id'])) {
            $sub = Subcarpeta::where('id', $data['subcarpeta_id'])
                ->where('materia_id', $material->materia_id)
                ->where('tipo', $material->tipo)
                ->first();

            abort_unless($sub !== null, 422);
            $subcarpetaId = $sub->id;
        }

        $material->update(['subcarpeta_id' => $subcarpetaId]);

        return back();
    }

    /**
     * Mover varios materiales a la vez a una carpeta (o sacarlos).
     * Solo mueve los que el usuario puede mover; la carpeta destino debe
     * coincidir en materia y tipo con cada material (si no, se saltea).
     */
    public function moveBatch(Request $request, Materia $materia): RedirectResponse
    {
        $data = $request->validate([
            'material_ids' => ['required', 'array', 'min:1'],
            'material_ids.*' => ['integer'],
            'subcarpeta_id' => ['nullable', 'integer'],
        ]);

        $materials = $materia->materials()->whereIn('id', $data['material_ids'])->get();

        DB::transaction(function () use ($request, $materia, $data, $materials) {
            foreach ($materials as $material) {
                if (! $request->user()->can('move', $material)) {
                    continue;
                }

                $subId = null;
                if (! empty($data['subcarpeta_id'])) {
                    $sub = $materia->subcarpetas()->where('tipo', $material->tipo)->find($data['subcarpeta_id']);
                    if (! $sub) {
                        continue;
                    }
                    $subId = $sub->id;
                }

                $material->update(['subcarpeta_id' => $subId]);
            }
        });

        return back();
    }

    /**
     * Descargar un material (archivo privado servido con permisos).
     */
    public function download(Material $material): StreamedResponse
    {
        $material->increment('downloads');

        return response()->streamDownload(function () use ($material) {
            echo \Illuminate\Support\Facades\Storage::get($material->path);
        }, $material->original_name);
    }

    /**
     * Voto "me sirvió" (toggle: si ya votó, lo saca).
     */
    public function toggleVote(Request $request, Material $material): RedirectResponse
    {
        $material->voters()->toggle($request->user()->id);

        return back();
    }

    /**
     * Guardar / quitar de favoritos (toggle).
     */
    public function toggleFavorite(Request $request, Material $material): RedirectResponse
    {
        $material->favoritedBy()->toggle($request->user()->id);

        return back();
    }

    /**
     * "Mis apuntes": lo que subí + lo que guardé.
     */
    public function mine(Request $request): Response
    {
        $user = $request->user();

        $map = fn (Material $m) => [
            'id' => $m->id,
            'tipo' => $m->tipo,
            'titulo' => $m->titulo,
            'mime' => $m->mime,
            'original_name' => $m->original_name,
            'downloads' => $m->downloads,
            'materia' => [
                'id' => $m->materia->id,
                'nombre' => $m->materia->nombre,
                'color' => $m->materia->color,
                'icon' => $m->materia->icon,
            ],
            'carpeta' => $m->subcarpeta?->nombre,
        ];

        $uploads = Material::with(['materia:id,nombre,color,icon', 'subcarpeta:id,nombre'])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map($map);

        $favorites = $user->favoriteMaterials()
            ->with(['materia:id,nombre,color,icon', 'subcarpeta:id,nombre'])
            ->orderByDesc('material_favorites.created_at')
            ->get()
            ->map($map);

        return Inertia::render('MyMaterials', [
            'uploads' => $uploads,
            'favorites' => $favorites,
        ]);
    }

    /**
     * Borrar un material: solo el dueño o un admin.
     */
    public function destroy(Request $request, Material $material): RedirectResponse
    {
        abort_unless($request->user()->can('delete', $material), 403);

        $material->delete();

        return back();
    }
}

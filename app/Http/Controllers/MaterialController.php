<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Materia;
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
        ]);

        DB::transaction(function () use ($request, $materia, $data) {
            foreach ($data['files'] as $i => $file) {
                $path = $file->store("materiales/{$materia->id}");

                $materia->materials()->create([
                    'user_id' => $request->user()->id,
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
        $user = $request->user();

        if (! $user->isAdmin() && $material->user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ]);

        $material->update($data);

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
        ];

        $uploads = Material::with('materia:id,nombre,color,icon')
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map($map);

        $favorites = $user->favoriteMaterials()
            ->with('materia:id,nombre,color,icon')
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
        $user = $request->user();

        if (! $user->isAdmin() && $material->user_id !== $user->id) {
            abort(403);
        }

        $material->delete();

        return back();
    }
}

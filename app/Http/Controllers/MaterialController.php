<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Materia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    /**
     * Subir un apunte o examen a una materia. Cualquier usuario activo puede.
     */
    public function store(Request $request, Materia $materia): RedirectResponse
    {
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'in:apunte,campus,examen'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'file' => [
                'required',
                'file',
                'max:10240', // 10 MB
                'mimes:pdf,jpg,jpeg,png,doc,docx,ppt,pptx,xls,xlsx',
            ],
        ]);

        $file = $request->file('file');
        $path = $file->store("materiales/{$materia->id}");

        $materia->materials()->create([
            'user_id' => $request->user()->id,
            'tipo' => $data['tipo'],
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

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

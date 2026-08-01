<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Materia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     * Descargar un material (archivo privado servido con permisos).
     */
    public function download(Material $material): StreamedResponse
    {
        return response()->streamDownload(function () use ($material) {
            echo \Illuminate\Support\Facades\Storage::get($material->path);
        }, $material->original_name);
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

<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use App\Models\Subcarpeta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubcarpetaController extends Controller
{
    /**
     * Crear una subcarpeta (solo admin). Puede quedar vacía o llevarse
     * archivos sueltos de la misma materia y mismo tipo.
     */
    public function store(Request $request, Materia $materia): RedirectResponse
    {
        abort_unless($request->user()->can('create', Subcarpeta::class), 403);

        $data = $request->validate([
            'tipo' => ['required', 'in:apunte,campus,examen'],
            'nombre' => ['required', 'string', 'max:255'],
            'material_ids' => ['nullable', 'array'],
            'material_ids.*' => ['integer'],
        ]);

        DB::transaction(function () use ($request, $materia, $data) {
            $subcarpeta = $materia->subcarpetas()->create([
                'user_id' => $request->user()->id,
                'tipo' => $data['tipo'],
                'nombre' => $data['nombre'],
            ]);

            if (! empty($data['material_ids'])) {
                // Solo materiales de ESTA materia, del MISMO tipo y que el usuario pueda mover.
                $materials = $materia->materials()
                    ->where('tipo', $data['tipo'])
                    ->whereIn('id', $data['material_ids'])
                    ->get();

                foreach ($materials as $material) {
                    if ($request->user()->can('move', $material)) {
                        $material->update(['subcarpeta_id' => $subcarpeta->id]);
                    }
                }
            }
        });

        return back();
    }

    /**
     * Renombrar una subcarpeta (solo admin).
     */
    public function update(Request $request, Subcarpeta $subcarpeta): RedirectResponse
    {
        abort_unless($request->user()->can('update', $subcarpeta), 403);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
        ]);

        $subcarpeta->update($data);

        return back();
    }

    /**
     * Borrar una subcarpeta (solo admin). Los archivos NO se borran:
     * vuelven a "sueltos" (la FK está en nullOnDelete).
     */
    public function destroy(Request $request, Subcarpeta $subcarpeta): RedirectResponse
    {
        abort_unless($request->user()->can('delete', $subcarpeta), 403);

        $subcarpeta->delete();

        return back();
    }
}

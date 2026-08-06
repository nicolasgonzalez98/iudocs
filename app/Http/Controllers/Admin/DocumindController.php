<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Services\Documind\DocumindClient;
use App\Services\Documind\MaterialSyncService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Panel de admin para la sincronización con DocuMind: estado por material,
 * reindexar uno, sincronizar los pendientes y conciliar el estado real.
 */
class DocumindController extends Controller
{
    public function index(DocumindClient $client): Response
    {
        $materials = Material::with('materia:id,nombre')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Material $m) => [
                'id' => $m->id,
                'titulo' => $m->titulo,
                'original_name' => $m->original_name,
                'tipo' => $m->tipo,
                'materia' => $m->materia?->nombre,
                'status' => $m->documind_status ?: 'unsynced',
                'error' => $m->documind_error,
                'synced_at' => $m->documind_synced_at?->diffForHumans(),
            ]);

        $raw = Material::selectRaw('documind_status, count(*) as n')
            ->groupBy('documind_status')
            ->pluck('n', 'documind_status');

        return Inertia::render('Admin/Documind', [
            'enabled' => $client->enabled(),
            'materials' => $materials,
            'counts' => [
                'total' => $materials->count(),
                'synced' => (int) ($raw[Material::DOCUMIND_SYNCED] ?? 0),
                'pending' => (int) ($raw[Material::DOCUMIND_PENDING] ?? 0),
                'error' => (int) ($raw[Material::DOCUMIND_ERROR] ?? 0),
                'skipped' => (int) ($raw[Material::DOCUMIND_SKIPPED] ?? 0),
            ],
        ]);
    }

    public function resync(Material $material, MaterialSyncService $sync): RedirectResponse
    {
        $sync->queueOne($material);

        return back();
    }

    public function syncAll(MaterialSyncService $sync): RedirectResponse
    {
        $sync->queuePending();

        return back();
    }

    public function reconcile(MaterialSyncService $sync): RedirectResponse
    {
        $sync->reconcile();

        return back();
    }
}

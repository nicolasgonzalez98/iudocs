<?php

namespace App\Services\Documind;

use App\Jobs\SyncMaterialToDocumind;
use App\Models\Material;
use Illuminate\Support\Carbon;

/**
 * Orquesta la sincronización de materiales con DocuMind (encolar + conciliar estado).
 * Lo usan tanto los comandos artisan como el panel de admin, para no duplicar lógica.
 */
class MaterialSyncService
{
    public function __construct(private readonly DocumindClient $client)
    {
    }

    /** Encola el (re)sync de un material y lo deja en 'pending'. */
    public function queueOne(Material $material): void
    {
        $material->forceFill(['documind_status' => Material::DOCUMIND_PENDING])->save();
        SyncMaterialToDocumind::dispatch($material->id);
    }

    /**
     * Encola los materiales que faltan o fallaron (status null/pending/error),
     * espaciados para no reventar el rate-limit del free-tier. Devuelve cuántos.
     */
    public function queuePending(?int $materiaId = null, int $spacing = 5): int
    {
        $ids = Material::query()
            ->when($materiaId, fn ($q) => $q->where('materia_id', $materiaId))
            ->where(function ($q) {
                $q->whereNull('documind_status')
                    ->orWhereIn('documind_status', [
                        Material::DOCUMIND_PENDING,
                        Material::DOCUMIND_ERROR,
                    ]);
            })
            ->orderBy('id')
            ->pluck('id');

        foreach ($ids as $i => $id) {
            Material::whereKey($id)->update(['documind_status' => Material::DOCUMIND_PENDING]);
            SyncMaterialToDocumind::dispatch($id)->delay(now()->addSeconds($i * $spacing));
        }

        return $ids->count();
    }

    /**
     * Concilia el estado local con el estado REAL de DocuMind (lee una sola vez).
     * Devuelve el resumen por estado (['synced' => 3, 'error' => 1, ...]).
     *
     * @return array<string, int>
     */
    public function reconcile(?int $materiaId = null): array
    {
        $remote = collect($this->client->listDocuments())->keyBy('id');

        $materials = Material::whereNotNull('documind_document_id')
            ->when($materiaId, fn ($q) => $q->where('materia_id', $materiaId))
            ->get();

        $summary = [];
        foreach ($materials as $material) {
            [$status, $error, $syncedAt] = $this->mapStatus($remote->get($material->documind_document_id));

            $material->forceFill([
                'documind_status' => $status,
                'documind_error' => $error,
                'documind_synced_at' => $syncedAt,
            ])->save();

            $summary[$status] = ($summary[$status] ?? 0) + 1;
        }

        return $summary;
    }

    /**
     * Mapea el estado de un documento de DocuMind al estado local del material.
     *
     * @param  array<string, mixed>|null  $doc
     * @return array{0: string, 1: ?string, 2: ?Carbon}
     */
    public function mapStatus(?array $doc): array
    {
        if ($doc === null) {
            return [Material::DOCUMIND_ERROR, 'No está en DocuMind (¿borrado del lado del motor?)', null];
        }

        $status = $doc['status'] ?? '';
        $error = $doc['error_message'] ?? null;

        if ($status === 'ready') {
            return [Material::DOCUMIND_SYNCED, null, now()];
        }

        if ($status === 'error') {
            $lower = mb_strtolower((string) $error);
            $needsOcr = str_contains($lower, 'ocr') || str_contains($lower, 'escane');

            return [
                $needsOcr ? Material::DOCUMIND_SKIPPED : Material::DOCUMIND_ERROR,
                $error ? mb_substr($error, 0, 1000) : null,
                null,
            ];
        }

        // pending / processing
        return [Material::DOCUMIND_PENDING, null, null];
    }
}

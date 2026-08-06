<?php

namespace App\Jobs;

use App\Models\Material;
use App\Services\Documind\DocumindClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * Sincroniza (ingesta) un material en DocuMind: lo sube al motor RAG y guarda el
 * document_id + estado del lado de IUDocs. La materia viaja como `collection_id`
 * para poder scopear el chat por materia.
 *
 * Idempotente: si el material ya tenía un document_id, borra el anterior antes de
 * re-subir (sirve para reprocesos y backfill).
 */
class SyncMaterialToDocumind implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 15; // segundos entre reintentos

    public function __construct(public int $materialId)
    {
    }

    public function handle(DocumindClient $documind): void
    {
        if (! $documind->enabled()) {
            return;
        }

        $material = Material::find($this->materialId);
        if ($material === null || empty($material->path)) {
            return; // borrado entre el dispatch y el handle, o sin archivo
        }

        // Solo tipos que DocuMind sabe procesar.
        $ext = strtolower(pathinfo(
            $material->original_name ?: $material->path,
            PATHINFO_EXTENSION,
        ));
        $supported = config('services.documind.extensions', ['pdf', 'txt', 'md', 'docx']);
        if (! in_array($ext, $supported, true)) {
            $this->mark($material, Material::DOCUMIND_SKIPPED, "Tipo .{$ext} no soportado por DocuMind");
            return;
        }

        // Límite de tamaño de DocuMind.
        $maxBytes = ((int) config('services.documind.max_mb', 20)) * 1024 * 1024;
        if ((int) $material->size > $maxBytes) {
            $this->mark($material, Material::DOCUMIND_SKIPPED, 'Archivo más grande que el límite de DocuMind');
            return;
        }

        if (! Storage::exists($material->path)) {
            $this->mark($material, Material::DOCUMIND_ERROR, 'Archivo no encontrado en el storage');
            return;
        }

        try {
            // Idempotencia: si ya estaba indexado, borramos el anterior antes de re-subir.
            if (! empty($material->documind_document_id)) {
                $documind->deleteDocument($material->documind_document_id);
            }

            $documindId = $documind->uploadDocument(
                contents: Storage::get($material->path),
                filename: $material->original_name ?: basename($material->path),
                mime: $material->mime,
                collectionId: (string) $material->materia_id,
            );

            $material->forceFill([
                'documind_document_id' => $documindId,
                'documind_status' => Material::DOCUMIND_SYNCED,
                'documind_synced_at' => now(),
                'documind_error' => null,
            ])->save();
        } catch (\Throwable $e) {
            $this->mark($material, Material::DOCUMIND_ERROR, $e->getMessage());
            throw $e; // que la cola reintente (hasta $tries)
        }
    }

    private function mark(Material $material, string $status, ?string $error = null): void
    {
        $material->forceFill([
            'documind_status' => $status,
            'documind_error' => $error !== null ? mb_substr($error, 0, 1000) : null,
        ])->save();
    }
}

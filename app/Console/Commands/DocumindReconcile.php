<?php

namespace App\Console\Commands;

use App\Models\Material;
use App\Services\Documind\DocumindClient;
use Illuminate\Console\Command;

/**
 * Concilia el estado de los materiales con el estado REAL de DocuMind.
 *
 * El upload solo encola la ingesta (async), así que IUDocs no sabe si el documento
 * terminó indexado o falló. Este comando lee el estado real de DocuMind y actualiza
 * documind_status / documind_error:
 *   ready                     -> synced
 *   error (escaneado / OCR)   -> skipped  (no se reintenta: necesita OCR)
 *   error (otro, ej. 429)     -> error    (transitorio: se puede reintentar)
 *   pending / processing      -> pending
 *   no está en DocuMind       -> error
 */
class DocumindReconcile extends Command
{
    protected $signature = 'documind:reconcile {--materia= : Limita a una materia (id)}';

    protected $description = 'Actualiza el estado de los materiales según el estado real en DocuMind.';

    public function handle(DocumindClient $documind): int
    {
        if (! $documind->enabled()) {
            $this->error('La integración con DocuMind está deshabilitada.');

            return self::FAILURE;
        }

        // Estado real de DocuMind, indexado por document_id (una sola llamada).
        $remote = collect($documind->listDocuments())->keyBy('id');

        $query = Material::whereNotNull('documind_document_id');
        if ($materia = $this->option('materia')) {
            $query->where('materia_id', $materia);
        }
        $materials = $query->get();

        if ($materials->isEmpty()) {
            $this->info('No hay materiales con documento en DocuMind para conciliar.');

            return self::SUCCESS;
        }

        $summary = [];
        foreach ($materials as $material) {
            $doc = $remote->get($material->documind_document_id);
            [$status, $error, $syncedAt] = $this->mapStatus($doc);

            $material->forceFill([
                'documind_status' => $status,
                'documind_error' => $error,
                'documind_synced_at' => $syncedAt,
            ])->save();

            $summary[$status] = ($summary[$status] ?? 0) + 1;
        }

        $this->info("Conciliados {$materials->count()} material(es):");
        foreach ($summary as $status => $n) {
            $this->line("  {$status}: {$n}");
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>|null  $doc  Documento de DocuMind (o null si no existe).
     * @return array{0: string, 1: ?string, 2: ?\Illuminate\Support\Carbon}
     */
    private function mapStatus(?array $doc): array
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
            $mapped = $needsOcr ? Material::DOCUMIND_SKIPPED : Material::DOCUMIND_ERROR;

            return [$mapped, $error ? mb_substr($error, 0, 1000) : null, null];
        }

        // pending / processing
        return [Material::DOCUMIND_PENDING, null, null];
    }
}

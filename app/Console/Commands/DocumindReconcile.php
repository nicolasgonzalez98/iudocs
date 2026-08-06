<?php

namespace App\Console\Commands;

use App\Services\Documind\DocumindClient;
use App\Services\Documind\MaterialSyncService;
use Illuminate\Console\Command;

/**
 * Concilia el estado de los materiales con el estado REAL de DocuMind.
 *
 * El upload solo encola la ingesta (async), así que IUDocs no sabe si el documento
 * terminó indexado o falló. Este comando lee el estado real y actualiza documind_status
 * (ready -> synced, error de OCR/escaneado -> skipped, otro -> error, en curso -> pending).
 */
class DocumindReconcile extends Command
{
    protected $signature = 'documind:reconcile {--materia= : Limita a una materia (id)}';

    protected $description = 'Actualiza el estado de los materiales según el estado real en DocuMind.';

    public function handle(DocumindClient $documind, MaterialSyncService $sync): int
    {
        if (! $documind->enabled()) {
            $this->error('La integración con DocuMind está deshabilitada.');

            return self::FAILURE;
        }

        $materia = $this->option('materia') ? (int) $this->option('materia') : null;
        $summary = $sync->reconcile($materia);

        if ($summary === []) {
            $this->info('No hay materiales con documento en DocuMind para conciliar.');

            return self::SUCCESS;
        }

        $this->info('Conciliado:');
        foreach ($summary as $status => $n) {
            $this->line("  {$status}: {$n}");
        }

        return self::SUCCESS;
    }
}

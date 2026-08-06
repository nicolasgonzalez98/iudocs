<?php

namespace App\Console\Commands;

use App\Jobs\SyncMaterialToDocumind;
use App\Models\Material;
use App\Services\Documind\DocumindClient;
use Illuminate\Console\Command;

/**
 * Backfill: sincroniza a DocuMind los materiales ya existentes.
 *
 * Por defecto solo toca los que aún no están sincronizados (status null/pending/error)
 * y los ENCOLA (necesita un worker corriendo). Opciones:
 *   --force      también re-sincroniza los ya sincronizados (reindexa todo)
 *   --sync       procesa en el momento, sin cola (útil para un backfill puntual)
 *   --materia=ID limita a una materia
 */
class DocumindBackfill extends Command
{
    protected $signature = 'documind:backfill
                            {--force : Re-sincroniza también los ya sincronizados}
                            {--sync : Procesa en el momento, sin encolar}
                            {--materia= : Limita a una materia (id)}
                            {--spacing=5 : Segundos de separación entre jobs encolados (evita el rate-limit)}';

    protected $description = 'Sincroniza a DocuMind los materiales existentes (backfill).';

    public function handle(DocumindClient $documind): int
    {
        if (! $documind->enabled()) {
            $this->error('La integración con DocuMind está deshabilitada (revisá DOCUMIND_ENABLED / URL / KEY).');

            return self::FAILURE;
        }

        $query = Material::query();

        if (! $this->option('force')) {
            // Los que faltan o fallaron (transitorio): status null/pending/error.
            // No incluimos 'synced' (ya está) ni 'skipped' (no aplica: OCR/tipo).
            // El job es idempotente, así que reintentar un 'error' con id previo es seguro.
            $query->where(function ($q) {
                $q->whereNull('documind_status')
                    ->orWhereIn('documind_status', [
                        Material::DOCUMIND_PENDING,
                        Material::DOCUMIND_ERROR,
                    ]);
            });
        }

        if ($materia = $this->option('materia')) {
            $query->where('materia_id', $materia);
        }

        $ids = $query->orderBy('id')->pluck('id');
        $total = $ids->count();

        if ($total === 0) {
            $this->info('No hay materiales para sincronizar. (Usá --force para reindexar todo.)');

            return self::SUCCESS;
        }

        $inline = (bool) $this->option('sync');
        $spacing = max(0, (int) $this->option('spacing'));
        $this->info(($inline ? 'Sincronizando' : 'Encolando')." {$total} material(es)...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($ids as $i => $id) {
            if ($inline) {
                SyncMaterialToDocumind::dispatchSync($id);
            } else {
                Material::whereKey($id)->update(['documind_status' => Material::DOCUMIND_PENDING]);
                // Espaciamos la ingesta: DocuMind procesa async y el free-tier de
                // Gemini tiene límite por minuto → sin espaciado, una tanda grande da 429.
                SyncMaterialToDocumind::dispatch($id)->delay(now()->addSeconds($i * $spacing));
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($inline) {
            // Resumen del resultado (los estados ya quedaron persistidos).
            $counts = Material::whereIn('id', $ids)
                ->selectRaw('documind_status, count(*) as n')
                ->groupBy('documind_status')
                ->pluck('n', 'documind_status');
            $this->info('Backfill completado (inline):');
            foreach ($counts as $status => $n) {
                $this->line('  '.($status ?? 'null').": {$n}");
            }
        } else {
            $this->info("Encolados {$total}. Corré `php artisan queue:work` para procesarlos.");
        }

        return self::SUCCESS;
    }
}

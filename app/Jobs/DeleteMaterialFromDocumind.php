<?php

namespace App\Jobs;

use App\Services\Documind\DocumindClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Borra en DocuMind el documento de un material que se eliminó en IUDocs.
 *
 * Recibe el document_id de DocuMind (no el material, que ya no existe). El borrado
 * es idempotente del lado del cliente (un 404 se tolera).
 */
class DeleteMaterialFromDocumind implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 15;

    public function __construct(public string $documindDocumentId)
    {
    }

    public function handle(DocumindClient $documind): void
    {
        if (! $documind->enabled()) {
            return;
        }

        $documind->deleteDocument($this->documindDocumentId);
    }
}

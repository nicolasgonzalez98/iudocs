<?php

namespace App\Services\Documind;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Cliente HTTP del motor RAG DocuMind.
 *
 * IUDocs se autentica como servicio (header X-Service-Key) y opera siempre sobre
 * la organización de servicio configurada del lado de DocuMind. Cada material se
 * sincroniza como un "documento" y su materia viaja como `collection_id` (scoping).
 */
class DocumindClient
{
    public function __construct(
        private readonly ?string $url,
        private readonly ?string $key,
        private readonly int $timeout = 30,
        private readonly bool $enabled = false,
    ) {
    }

    /** ¿Está la integración configurada y habilitada? */
    public function enabled(): bool
    {
        return $this->enabled && ! empty($this->url) && ! empty($this->key);
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) $this->url, '/'))
            ->withHeaders(['X-Service-Key' => (string) $this->key])
            ->timeout($this->timeout)
            ->acceptJson();
    }

    /**
     * Sube (ingesta) un documento y devuelve su `document_id` en DocuMind.
     *
     * @param  string       $contents      Bytes del archivo.
     * @param  string       $filename      Nombre visible del archivo.
     * @param  string|null  $mime          Content-Type del archivo.
     * @param  string|null  $collectionId  Colección (ej.: id de la materia) para scoping.
     */
    public function uploadDocument(
        string $contents,
        string $filename,
        ?string $mime = null,
        ?string $collectionId = null,
    ): string {
        $request = $this->http()->attach(
            'file',
            $contents,
            $filename,
            $mime ? ['Content-Type' => $mime] : [],
        );

        $form = [];
        if ($collectionId !== null && $collectionId !== '') {
            $form['collection_id'] = $collectionId;
        }

        $response = $request->post('/documents', $form);
        $response->throw();

        return (string) $response->json('id');
    }

    /**
     * Lista documentos de la organización de servicio (con su estado real de ingesta:
     * pending / processing / ready / error + error_message). Filtra por colección si se pasa.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listDocuments(?string $collectionId = null): array
    {
        $query = [];
        if ($collectionId !== null && $collectionId !== '') {
            $query['collection_id'] = $collectionId;
        }

        return $this->http()->get('/documents', $query)->throw()->json();
    }

    /** Borra un documento en DocuMind. 404 se tolera (idempotente). */
    public function deleteDocument(string $documindId): void
    {
        $response = $this->http()->delete("/documents/{$documindId}");

        if ($response->status() !== 404) {
            $response->throw();
        }
    }

    /** Chequeo de salud del vector store (útil para diagnóstico). */
    public function healthVector(): array
    {
        return $this->http()->get('/health/vector')->throw()->json();
    }
}

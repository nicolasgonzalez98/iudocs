<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Materia;
use App\Models\User;
use App\Services\Documind\DocumindClient;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Chat RAG embebido, scopeado por materia.
 *
 * IUDocs actúa de proxy: calcula la allow-list de documentos que este usuario puede
 * consultar (permisos finos) y relaya el streaming de DocuMind al browser, sin exponer
 * la service key. El acceso lo gobierna el feature flag `services.documind.chat_mode`.
 */
class DocumindChatController extends Controller
{
    public function ask(Request $request, Materia $materia, DocumindClient $documind): StreamedResponse
    {
        $user = $request->user();
        abort_unless($documind->chatAllowedFor($user->isAdmin()), 403, 'El asistente no está disponible.');

        $data = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
        ]);

        $docIds = $this->allowedDocumindIds($materia, $user);

        return response()->stream(function () use ($documind, $data, $docIds) {
            // Sin documentos permitidos → respondemos sin llamar a DocuMind.
            if (empty($docIds)) {
                echo $this->sse('sources', ['confidence' => 0, 'chunks' => []]);
                echo $this->sse('token', [
                    'text' => 'Todavía no hay apuntes indexados en esta materia para responder tu pregunta.',
                ]);
                echo $this->sse('done', []);

                return;
            }

            try {
                $documind->streamChat($data['question'], $docIds, function (string $chunk) {
                    echo $chunk;
                    if (ob_get_level() > 0) {
                        @ob_flush();
                    }
                    @flush();
                });
            } catch (\Throwable $e) {
                echo $this->sse('error', ['message' => 'No se pudo consultar el asistente: '.$e->getMessage()]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * document_ids de DocuMind que este usuario puede consultar en esta materia:
     * solo material ya indexado (synced) y, si no puede ver exámenes, sin exámenes.
     *
     * @return array<int, string>
     */
    private function allowedDocumindIds(Materia $materia, User $user): array
    {
        $query = $materia->materials()
            ->where('documind_status', Material::DOCUMIND_SYNCED)
            ->whereNotNull('documind_document_id');

        if (! $user->canSeeExamenes()) {
            $query->where('tipo', '!=', 'examen');
        }

        return $query->pluck('documind_document_id')->all();
    }

    /** Formatea un evento SSE igual que DocuMind (event + data JSON). */
    private function sse(string $event, array $data): string
    {
        return "event: {$event}\ndata: ".json_encode($data, JSON_UNESCAPED_UNICODE)."\n\n";
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campos para sincronizar cada material con DocuMind (motor RAG).
     * - documind_document_id: id del documento del lado de DocuMind.
     * - documind_status: null (sin sincronizar) | pending | synced | error | skipped.
     * - documind_synced_at: cuándo se sincronizó por última vez.
     * - documind_error: último error de sincronización (si hubo).
     */
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('documind_document_id')->nullable()->index()->after('downloads');
            $table->string('documind_status', 20)->nullable()->after('documind_document_id');
            $table->timestamp('documind_synced_at')->nullable()->after('documind_status');
            $table->text('documind_error')->nullable()->after('documind_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn([
                'documind_document_id',
                'documind_status',
                'documind_synced_at',
                'documind_error',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            // null = archivo suelto; con valor = dentro de esa subcarpeta.
            // Al borrar la carpeta, los archivos vuelven a "sueltos" (nullOnDelete).
            $table->foreignId('subcarpeta_id')
                ->nullable()
                ->after('materia_id')
                ->constrained('subcarpetas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subcarpeta_id');
        });
    }
};

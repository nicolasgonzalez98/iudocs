<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carreras', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });

        // Carreras iniciales (el admin puede editarlas/borrarlas o agregar más).
        DB::table('carreras')->insert([
            ['nombre' => 'Biotecnología', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Bioingeniería', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('carreras');
    }
};

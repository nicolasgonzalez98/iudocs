<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->unsignedInteger('downloads')->default(0)->after('size');
        });

        // Votos "me sirvió" (un voto por usuario y material)
        Schema::create('material_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['material_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_votes');
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('downloads');
        });
    }
};

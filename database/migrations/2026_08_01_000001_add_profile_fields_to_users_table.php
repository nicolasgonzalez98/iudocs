<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campos de perfil, login con Google y control de acceso (rol + estado).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('id');
            $table->string('avatar')->nullable()->after('email');
            $table->string('role')->default('member')->after('avatar');   // admin | member
            $table->string('status')->default('pending')->after('role');  // pending | active | blocked
            $table->string('password')->nullable()->change();             // los usuarios de Google no tienen password
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar', 'role', 'status']);
            // password vuelve a NOT NULL
            $table->string('password')->nullable(false)->change();
        });
    }
};

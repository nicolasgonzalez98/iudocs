<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Material extends Model
{
    // Estados de sincronización con DocuMind (columna documind_status).
    public const DOCUMIND_PENDING = 'pending';   // encolado, esperando sync
    public const DOCUMIND_SYNCED = 'synced';     // indexado en DocuMind
    public const DOCUMIND_ERROR = 'error';       // falló el sync (ver documind_error)
    public const DOCUMIND_SKIPPED = 'skipped';   // no aplica (tipo/tamaño no soportado)

    protected $fillable = [
        'materia_id',
        'subcarpeta_id',
        'user_id',
        'tipo',
        'titulo',
        'descripcion',
        'path',
        'original_name',
        'mime',
        'size',
        'downloads',
    ];
    // Nota: los campos documind_* NO son fillable a propósito (los setea el sistema,
    // no vienen de input del usuario).

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'downloads' => 'integer',
            'documind_synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Al borrar el registro, borramos también el archivo físico
        static::deleting(function (Material $material) {
            if ($material->path) {
                Storage::delete($material->path);
            }
        });
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }

    public function subcarpeta(): BelongsTo
    {
        return $this->belongsTo(Subcarpeta::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function voters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'material_votes')->withTimestamps();
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'material_favorites')->withTimestamps();
    }
}

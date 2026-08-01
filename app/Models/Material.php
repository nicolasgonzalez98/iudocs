<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Material extends Model
{
    protected $fillable = [
        'materia_id',
        'user_id',
        'tipo',
        'titulo',
        'descripcion',
        'path',
        'original_name',
        'mime',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Materia extends Model
{
    protected $table = 'materias';

    protected $fillable = ['nombre', 'anio', 'cuatrimestre', 'catedra', 'color', 'icon'];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'cuatrimestre' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Al borrar una materia, borramos sus materiales uno a uno
        // (así se dispara el hook que elimina cada archivo físico).
        static::deleting(function (Materia $materia) {
            $materia->materials()->get()->each->delete();
        });
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function subcarpetas(): HasMany
    {
        return $this->hasMany(Subcarpeta::class);
    }

    public function carreras(): BelongsToMany
    {
        return $this->belongsToMany(Carrera::class, 'carrera_materia');
    }
}

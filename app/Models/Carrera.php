<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Carrera extends Model
{
    protected $table = 'carreras';

    protected $fillable = ['nombre'];

    public function materias(): BelongsToMany
    {
        return $this->belongsToMany(Materia::class, 'carrera_materia');
    }
}

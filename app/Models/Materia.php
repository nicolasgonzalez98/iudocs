<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    protected $table = 'materias';

    protected $fillable = ['nombre', 'anio', 'cuatrimestre', 'color', 'icon'];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'cuatrimestre' => 'integer',
        ];
    }
}

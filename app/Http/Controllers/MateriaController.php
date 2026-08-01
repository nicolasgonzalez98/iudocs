<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use Inertia\Inertia;
use Inertia\Response;

class MateriaController extends Controller
{
    /**
     * Grilla de materias (home para usuarios activos).
     */
    public function index(): Response
    {
        return Inertia::render('Materias/Index', [
            'materias' => Materia::orderBy('anio')
                ->orderBy('cuatrimestre')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    /**
     * Detalle de una materia (los apuntes/exámenes se agregan en F4).
     */
    public function show(Materia $materia): Response
    {
        return Inertia::render('Materias/Show', [
            'materia' => $materia,
        ]);
    }
}

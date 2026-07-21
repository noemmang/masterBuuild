<?php

namespace App\Http\Controllers\Api\Componentes;

use App\Http\Controllers\Controller;
use App\Models\Componentes\Componente;
use Illuminate\Http\Request;

class GabineteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Devuelve únicamente los campos necesarios para el visor 3D comparador.
     * Payload mínimo (~200 bytes) frente a los ~50 KB del show() genérico.
     */
    public function visor(string $uuid)
    {
        $componente = Componente::where('uuid', $uuid)
            ->activo()
            ->with(['gabinete'])
            ->firstOrFail();

        $g = $componente->gabinete;

        return response()->json([
            'uuid'                 => $componente->uuid,
            'nombre'               => $componente->nombre,
            'ancho_mm'             => $g?->ancho_mm,
            'alto_mm'              => $g?->alto_mm,
            'profundidad_mm'       => $g?->profundidad_mm,
            'longitud_gpu_max_mm'  => $g?->longitud_gpu_max_mm,
            'altura_cooler_max_mm' => $g?->altura_cooler_max_mm,
            'soporte_radiadores'   => $g?->soporte_radiadores ?? [],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
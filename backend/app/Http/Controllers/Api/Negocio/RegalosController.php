<?php

namespace App\Http\Controllers\Api\Negocio;

use App\Http\Controllers\Controller;
use App\Models\Componentes\Componente;
use App\Models\Negocio\Regalo;
use Illuminate\Http\JsonResponse;

class RegalosController extends Controller
{
    /**
     * GET /api/v1/componentes/{uuid}/regalos
     *
     * Devuelve los regalos activos asociados a un componente,
     * incluyendo la tienda de la que proviene cada regalo (dato del pivot).
     */
    public function porComponente(string $uuid): JsonResponse
    {
        $componente = Componente::where('uuid', $uuid)->activo()->firstOrFail();

        $regalos = $componente->regalosActivos()
            ->with('componentes') // no necesario aquí pero útil para extend
            ->get()
            ->map(fn($r) => [
                'uuid'           => $r->uuid,
                'nombre'         => $r->nombre,
                'tipo'           => $r->tipo,
                'imagen_url'     => $r->imagen_url,
                'descripcion'    => $r->descripcion,
                'valor_estimado' => (float) $r->valor_estimado,
                'tienda_id'      => $r->pivot->tienda_id,
                'fecha_inicio'   => $r->pivot->fecha_inicio,
                'fecha_expiracion' => $r->pivot->fecha_expiracion,
            ]);

        return response()->json([
            'componente' => ['uuid' => $componente->uuid, 'nombre' => $componente->nombre],
            'regalos'    => $regalos,
        ]);
    }

    /**
     * GET /api/v1/regalos
     *
     * Lista todos los regalos activos del catálogo (paginado).
     * Útil para una sección de "Promociones" si se añade en el futuro.
     */
    public function index(): JsonResponse
    {
        $regalos = Regalo::activos()
            ->with(['componentes' => fn($q) => $q->activo()->with('marca')])
            ->paginate(20);

        return response()->json($regalos);
    }
}
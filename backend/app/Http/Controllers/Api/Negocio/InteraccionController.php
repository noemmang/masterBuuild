<?php

namespace App\Http\Controllers\Api\Negocio;

use App\Http\Controllers\Controller;
use App\Models\Componentes\Componente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Registra las dos señales de las que se alimenta la relevancia de un
 * componente (ver RelevanciaService): que haya aparecido como resultado
 * de una búsqueda con texto, o que el usuario lo haya seleccionado/abierto.
 * Rutas públicas (no requieren sesión: la relevancia se mide sobre TODO el
 * tráfico, no solo sobre usuarios logueados) y limitadas por el
 * rate limiter 'interacciones' (ver AppServiceProvider) para que no sirvan
 * como vector fácil de inflar la puntuación de un componente a base de
 * peticiones.
 *
 * No devuelven nada relevante al front (fire-and-forget: el front no
 * espera ni bloquea nada a la respuesta) y nunca fallan de forma visible
 * por un uuid que no exista — simplemente no insertan nada.
 */
class InteraccionController extends Controller
{
    // Tope de componentes que puede llevar una única llamada de
    // "apareció en esta búsqueda": una página de resultados nunca tiene
    // más que esto, así que es suficiente margen sin permitir lotes
    // arbitrariamente grandes.
    private const MAX_UUIDS_POR_LOTE = 40;

    public function registrarBusqueda(Request $request)
    {
        $data = $request->validate([
            'componente_uuids'   => ['required', 'array', 'min:1', 'max:' . self::MAX_UUIDS_POR_LOTE],
            'componente_uuids.*' => ['string'],
        ]);

        $ids = Componente::whereIn('uuid', $data['componente_uuids'])->pluck('id');

        if ($ids->isNotEmpty()) {
            $ahora = now();
            $filas = $ids->map(fn ($id) => [
                'componente_id' => $id,
                'tipo'          => 'busqueda',
                'created_at'    => $ahora,
            ])->all();

            DB::table('interacciones_componente')->insert($filas);
        }

        return response()->json(['message' => 'ok'], 202);
    }

    public function registrarSeleccion(Request $request)
    {
        $data = $request->validate([
            'componente_uuid' => ['required', 'string'],
        ]);

        $id = Componente::where('uuid', $data['componente_uuid'])->value('id');

        if ($id) {
            DB::table('interacciones_componente')->insert([
                'componente_id' => $id,
                'tipo'          => 'seleccion',
                'created_at'    => now(),
            ]);
        }

        return response()->json(['message' => 'ok'], 202);
    }
}

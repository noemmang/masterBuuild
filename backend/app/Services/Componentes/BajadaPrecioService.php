<?php

namespace App\Services\Componentes;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Detecta qué componentes han tenido una bajada de precio REAL y
 * RECIENTE en, al menos, una tienda donde siguen en stock ahora mismo.
 * La usa HomeController::bajadasPrecio() para el carrusel "Bajadas de
 * precio" del home.
 *
 * "Real y reciente" descarta a propósito los tres casos que el carrusel
 * anterior no distinguía (el bug: el front simplemente mostraba los más
 * baratos, con bajada_precio hardcodeado a false en el mapeo):
 *   - Altas nuevas: un componente sin ningún tramo CERRADO en
 *     historial_precios no puede haber "bajado" nunca — no hay precio
 *     anterior con el que compararlo.
 *   - Reposición de stock al mismo precio: volver a tener stock no es una
 *     bajada si el precio no cambió.
 *   - Agotados en todas sus tiendas: si la tienda con la bajada ya no
 *     tiene stock, no cuenta (y por construcción de la consulta, si NINGUNA
 *     tienda del componente tiene stock, no puede aparecer aquí).
 */
class BajadaPrecioService
{
    private const VENTANA_DIAS_DEFECTO = 14;

    /**
     * @return Collection<int, object{componente_id:int,precio_anterior:string,precio_actual:string}>
     *         indexada por componente_id. Si un componente tiene bajada en
     *         varias tiendas a la vez, se queda con la de mayor caída
     *         porcentual (la más "vendible" para el carrusel).
     */
    public function idsConBajada(int $diasVentana = self::VENTANA_DIAS_DEFECTO): Collection
    {
        // Por (componente, tienda): el ÚLTIMO tramo cerrado antes del
        // precio vigente (rn = 1, ordenado por valid_to descendente) es
        // "el precio que había antes". Si el precio vigente es menor Y esa
        // tienda tiene stock ahora Y el cambio ocurrió dentro de la
        // ventana, es una bajada real.
        //
        // Solo PostgreSQL (ROW_NUMBER() + "CURRENT_DATE - entero"): mismo
        // alcance que el resto de SQL a mano de la app — ver
        // PrecioController::historial(), que ya usa date_trunc/generate_series
        // sin contemplar sqlite.
        $filas = DB::select("
            SELECT componente_id, precio_anterior, precio_actual FROM (
                SELECT
                    hp.componente_id,
                    hp.precio    AS precio_anterior,
                    pa.precio    AS precio_actual,
                    pa.en_stock,
                    hp.valid_to,
                    ROW_NUMBER() OVER (
                        PARTITION BY hp.componente_id, hp.tienda_id
                        ORDER BY hp.valid_to DESC
                    ) AS rn
                FROM historial_precios hp
                JOIN precios_actuales pa
                  ON pa.componente_id = hp.componente_id
                 AND pa.tienda_id     = hp.tienda_id
            ) t
            WHERE t.rn = 1
              AND t.en_stock = true
              AND t.precio_actual < t.precio_anterior
              AND t.valid_to >= (CURRENT_DATE - ?::int)
        ", [$diasVentana]);

        return collect($filas)
            ->groupBy('componente_id')
            ->map(fn ($grupo) => $grupo->sortBy(
                fn ($fila) => (float) $fila->precio_actual / (float) $fila->precio_anterior
            )->first());
    }
}

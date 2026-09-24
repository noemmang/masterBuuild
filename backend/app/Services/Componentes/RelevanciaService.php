<?php

namespace App\Services\Componentes;

use Illuminate\Support\Facades\DB;

/**
 * "Relevancia" como criterio de orden por defecto en el buscador y el
 * configurador: qué tan buscado o seleccionado ha sido un componente
 * recientemente, no un simple orden alfabético disfrazado (ver el
 * comentario en ComponenteController::index).
 *
 * Dos responsabilidades:
 *  - ordenarPorRelevancia(): añade el JOIN + ORDER BY a una query de
 *    Componente ya construida (la usan ComponenteController::index y
 *    HomeController::destacados).
 *  - recalcular(): reconstruye metricas_relevancia a partir del crudo en
 *    interacciones_componente. Se ejecuta una vez al día, encadenada en
 *    scrape:diario (ver RelevanciaRecalcular), no en caliente en cada
 *    petición: así el listado y el home solo hacen un JOIN de lectura
 *    contra una tabla ya calculada, en vez de un COUNT/GROUP BY sobre el
 *    histórico completo de interacciones en cada página vista.
 */
class RelevanciaService
{
    // Una selección (el usuario entró a ver la ficha) pesa más que
    // aparecer como resultado de una búsqueda con texto: demuestra un
    // interés real, no solo una coincidencia de nombre.
    private const PESO_SELECCION = 3;
    private const PESO_BUSQUEDA = 1;

    // Ventana de interacciones que cuentan para la puntuación. Pasado este
    // número de días, una búsqueda o selección deja de "empujar" al
    // componente — así lo que sale primero refleja el interés reciente,
    // no un pico puntual de hace meses que ya no es relevante.
    private const VENTANA_DIAS = 30;

    public function ordenarPorRelevancia($query)
    {
        // LEFT JOIN porque la mayoría de componentes puede no tener fila
        // todavía en metricas_relevancia (sin interacciones registradas):
        // deben seguir apareciendo, solo que al final. NULLS LAST es
        // explícito porque en Postgres el DESC por defecto pone los NULL
        // PRIMERO, justo al revés de lo que hace falta aquí (si no, los
        // componentes sin puntuación taparían a los que sí la tienen).
        return $query
            ->leftJoin('metricas_relevancia', 'metricas_relevancia.componente_id', '=', 'componentes.id')
            ->orderByRaw('metricas_relevancia.puntuacion DESC NULLS LAST')
            ->orderBy('componentes.nombre', 'asc');
    }

    /**
     * Regenera metricas_relevancia entera a partir de las interacciones de
     * los últimos $dias días. DELETE + INSERT (no UPDATE fila a fila):
     * como la ventana es móvil, un componente que ya no tiene
     * interacciones recientes simplemente deja de tener fila, sin
     * necesidad de "caducar" nada a mano.
     *
     * @return int número de componentes con puntuación tras el recálculo
     */
    public function recalcular(?int $dias = null): int
    {
        $dias = $dias ?? self::VENTANA_DIAS;
        $desde = now()->subDays($dias);

        $busquedas = DB::table('interacciones_componente')
            ->select('componente_id', DB::raw('COUNT(*) as n'))
            ->where('tipo', 'busqueda')
            ->where('created_at', '>=', $desde)
            ->groupBy('componente_id')
            ->pluck('n', 'componente_id');

        $selecciones = DB::table('interacciones_componente')
            ->select('componente_id', DB::raw('COUNT(*) as n'))
            ->where('tipo', 'seleccion')
            ->where('created_at', '>=', $desde)
            ->groupBy('componente_id')
            ->pluck('n', 'componente_id');

        $ids = $busquedas->keys()->merge($selecciones->keys())->unique()->values();

        $ahora = now();
        $filas = $ids->map(function ($id) use ($busquedas, $selecciones, $ahora) {
            $b = (int) ($busquedas[$id] ?? 0);
            $s = (int) ($selecciones[$id] ?? 0);

            return [
                'componente_id'   => (int) $id,
                'busquedas_30d'   => $b,
                'selecciones_30d' => $s,
                'puntuacion'      => $s * self::PESO_SELECCION + $b * self::PESO_BUSQUEDA,
                'actualizado_en'  => $ahora,
            ];
        })->all();

        DB::transaction(function () use ($filas) {
            DB::table('metricas_relevancia')->delete();

            foreach (array_chunk($filas, 500) as $lote) {
                DB::table('metricas_relevancia')->insert($lote);
            }
        });

        return count($filas);
    }
}

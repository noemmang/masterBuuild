<?php

namespace App\Http\Controllers\Api\Negocio;

use App\Http\Controllers\Controller;
use App\Models\Componentes\Componente;
use App\Models\Negocio\PrecioActual;
use App\Models\Negocio\Tienda;
use App\Models\Negocio\UrlProductoTienda;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrecioController extends Controller
{
    // ── GET /api/v1/componentes/{uuid}/precios ─────────────────────────────

    public function actuales(string $uuid)
    {
        $componente = Componente::where('uuid', $uuid)->firstOrFail();

        // Fallback: si una fila de precios_actuales no trae url propia
        // (dato insertado a mano, tinker...), usamos la configurada en
        // urls_producto_tienda para que el link a la tienda no se rompa.
        $urlsConfiguradasPorTienda = UrlProductoTienda::where('componente_id', $componente->id)
            ->pluck('url', 'tienda_id');

        // precios_actuales ya tiene, como mucho, una fila por tienda: nada
        // de subquery para calcular "el más reciente", es una lectura directa.
        $precios = PrecioActual::where('componente_id', $componente->id)
            ->with(['tienda'])
            ->orderByDesc('en_stock')
            ->orderBy('precio', 'asc')
            ->get()
            ->map(fn ($p) => [
                'uuid'        => $p->uuid,
                'tienda'      => $p->tienda ? ['uuid' => $p->tienda->uuid, 'nombre' => $p->tienda->nombre] : null,
                'precio'      => (float) $p->precio,
                'en_stock'    => $p->en_stock,
                'url'         => $p->url ?: ($urlsConfiguradasPorTienda[$p->tienda_id] ?? null),
                'actualizado' => $p->updated_at?->diffForHumans(),
            ]);

        return response()->json([
            'componente'   => ['uuid' => $componente->uuid, 'nombre' => $componente->nombre],
            'precios'      => $precios,
            'mejor_precio' => $precios->first(),
        ]);
    }

    // ── GET /api/v1/componentes/{uuid}/precios/historial ───────────────────
    //
    // Query params: periodo = 6m|1y|2y|3y (default 1y), tienda = uuid (opc.)
    // Respuesta idéntica a la de antes: el front no cambia.
    //
    // El histórico se guarda comprimido (historial_precios: tramos cerrados
    // [valid_from, valid_to]; el tramo abierto vive en precios_actuales), así
    // que la media mensual pondera cada tramo por los días que solapa con
    // cada mes: un precio que duró 28 días no puede pesar igual que uno que
    // duró 2.

    public function historial(string $uuid, Request $request): JsonResponse
    {
        $componente = Componente::where('uuid', $uuid)->firstOrFail();

        $periodo = $request->query('periodo', '1y');
        $desde   = match ($periodo) {
            '6m'    => now()->subMonths(6)->startOfMonth(),
            '2y'    => now()->subYears(2)->startOfMonth(),
            '3y'    => now()->subYears(3)->startOfMonth(),
            default => now()->subYear()->startOfMonth(),
        };

        $tiendaId = null;
        if ($request->filled('tienda')) {
            $tiendaId = Tienda::where('uuid', $request->tienda)->value('id');

            if (!$tiendaId) {
                return response()->json([
                    'resumen' => ['min' => null, 'max' => null, 'media' => null, 'actual' => null],
                    'puntos'  => [],
                    'tiendas' => [],
                ]);
            }
        }

        // tramos = historial_precios (cerrados) + el tramo abierto de
        // precios_actuales (tratado como si llegara hasta hoy), para que el
        // mes en curso también salga bien sin esperar a que cambie el precio.
        //
        // Los ::bigint en "$X IS NULL" son obligatorios: sin ellos Postgres
        // no puede inferir el tipo de un parámetro sin contexto y la query
        // falla con "could not determine data type of parameter" en el caso
        // más común, que es NO filtrar por tienda.
        $puntos = collect(DB::select("
            WITH meses AS (
                SELECT generate_series(
                    date_trunc('month', ?::date),
                    date_trunc('month', now()),
                    interval '1 month'
                )::date AS mes
            ),
            tramos AS (
                SELECT componente_id, tienda_id, precio, valid_from, valid_to
                FROM historial_precios
                WHERE componente_id = ? AND valid_to >= ?
                  AND (?::bigint IS NULL OR tienda_id = ?::bigint)
                UNION ALL
                SELECT componente_id, tienda_id, precio, vigente_desde::date AS valid_from, CURRENT_DATE AS valid_to
                FROM precios_actuales
                WHERE componente_id = ?
                  AND (?::bigint IS NULL OR tienda_id = ?::bigint)
            )
            SELECT
                to_char(m.mes, 'YYYY-MM') AS periodo,
                MIN(t.precio) AS min,
                MAX(t.precio) AS max,
                ROUND((
                    SUM(t.precio * (LEAST(t.valid_to, (m.mes + INTERVAL '1 month' - INTERVAL '1 day')::date)
                                   - GREATEST(t.valid_from, m.mes) + 1))
                    / NULLIF(SUM(LEAST(t.valid_to, (m.mes + INTERVAL '1 month' - INTERVAL '1 day')::date)
                               - GREATEST(t.valid_from, m.mes) + 1), 0)
                )::numeric, 2) AS media,
                COUNT(DISTINCT t.tienda_id) AS tiendas
            FROM meses m
            JOIN tramos t
                ON t.valid_from <= (m.mes + INTERVAL '1 month' - INTERVAL '1 day')::date
               AND t.valid_to   >= m.mes
            GROUP BY m.mes
            ORDER BY m.mes
        ", [
            $desde, $componente->id, $desde, $tiendaId, $tiendaId,
            $componente->id, $tiendaId, $tiendaId,
        ]))->map(fn ($row) => [
            'periodo' => $row->periodo,
            'min'     => (float) $row->min,
            'max'     => (float) $row->max,
            'media'   => (float) $row->media,
            'tiendas' => (int) $row->tiendas,
        ]);

        if ($puntos->isEmpty()) {
            return response()->json([
                'resumen' => ['min' => null, 'max' => null, 'media' => null, 'actual' => null],
                'puntos'  => [],
                'tiendas' => [],
            ]);
        }

        // ── Resumen global del período (misma ponderación por días) ────────
        $resumenRaw = DB::selectOne("
            WITH tramos AS (
                SELECT componente_id, tienda_id, precio, valid_from, valid_to
                FROM historial_precios
                WHERE componente_id = ? AND valid_to >= ?
                  AND (?::bigint IS NULL OR tienda_id = ?::bigint)
                UNION ALL
                SELECT componente_id, tienda_id, precio, vigente_desde::date AS valid_from, CURRENT_DATE AS valid_to
                FROM precios_actuales
                WHERE componente_id = ?
                  AND (?::bigint IS NULL OR tienda_id = ?::bigint)
            )
            SELECT
                MIN(precio) AS min,
                MAX(precio) AS max,
                ROUND((
                    SUM(precio * (LEAST(valid_to, CURRENT_DATE) - GREATEST(valid_from, ?::date) + 1))
                    / NULLIF(SUM(LEAST(valid_to, CURRENT_DATE) - GREATEST(valid_from, ?::date) + 1), 0)
                )::numeric, 2) AS media
            FROM tramos
            WHERE valid_to >= ?
        ", [
            $componente->id, $desde, $tiendaId, $tiendaId,
            $componente->id, $tiendaId, $tiendaId,
            $desde, $desde, $desde,
        ]);

        // Precio actual: el más bajo entre tiendas en stock (o la tienda
        // filtrada); si ninguna tiene stock, caemos al más bajo a secas.
        $actualQuery = PrecioActual::where('componente_id', $componente->id)
            ->when($tiendaId, fn ($q) => $q->where('tienda_id', $tiendaId));

        $actual = (clone $actualQuery)->where('en_stock', true)->min('precio')
            ?? $actualQuery->min('precio');

        $tiendaIds = collect(DB::select("
            SELECT DISTINCT tienda_id FROM (
                SELECT tienda_id FROM historial_precios WHERE componente_id = ? AND valid_to >= ?
                UNION
                SELECT tienda_id FROM precios_actuales WHERE componente_id = ?
            ) sub
        ", [$componente->id, $desde, $componente->id]))->pluck('tienda_id');

        $tiendas = Tienda::whereIn('id', $tiendaIds)
            ->select('uuid', 'nombre')
            ->get()
            ->map(fn ($t) => ['uuid' => $t->uuid, 'nombre' => $t->nombre])
            ->values();

        return response()->json([
            'resumen' => [
                'min'    => $resumenRaw?->min   !== null ? (float) $resumenRaw->min   : null,
                'max'    => $resumenRaw?->max   !== null ? (float) $resumenRaw->max   : null,
                'media'  => $resumenRaw?->media !== null ? (float) $resumenRaw->media : null,
                'actual' => $actual !== null ? (float) $actual : null,
            ],
            'puntos'  => $puntos->values(),
            'tiendas' => $tiendas,
        ]);
    }
}
<?php

namespace App\Http\Controllers\Api\Negocio;

use App\Http\Controllers\Controller;
use App\Http\Resources\PromocionRegaloResource;
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

        // Regalos visibles de este componente, agrupados por la tienda a la
        // que pertenece cada promoción. "Visible" ya implica: activa en el
        // último scrape, vigente por fechas y esa tienda con stock (ver
        // Componente::promocionesRegaloVisibles). Una sola query para todas
        // las tiendas, no una por fila.
        $regalosPorTienda = $componente->promocionesRegaloVisibles()
            ->orderBy('promociones_regalo.fecha_fin')
            ->orderBy('promociones_regalo.titulo')
            ->get()
            ->groupBy('tienda_id');

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
                // Solo los regalos de ESTA tienda. Vacío si no tiene o si
                // está agotada (el filtro de stock ya lo aplica la relación).
                'regalos'     => PromocionRegaloResource::collection($regalosPorTienda->get($p->tienda_id, collect()))->resolve(),
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

    // ── GET /api/v1/componentes/historial-categorias ───────────────────────
    //
    // Evolución de la MEDIA de precio por tipo de componente (para el home).
    //
    // Query params:
    //   periodo    = 6m|1y|2y|3y (default 1y), igual que historial()
    //   categorias = lista separada por comas (cpu,gpu,ram...). Vacío = todas.
    //
    // Cómo se calcula cada punto (categoría, mes):
    //   1. Para cada componente se hace la media del mes ponderada por días
    //      (mismo criterio que historial(): un precio que duró 28 días pesa
    //      más que uno que duró 2). Todas las tiendas del componente entran
    //      en esa media.
    //   2. La media de la categoría es la media simple de esas medias entre
    //      los componentes que tienen precio ese mes, así un componente con
    //      muchas tiendas no pesa más que uno con una sola.
    //
    // Ojo: si entran o salen componentes del catálogo la media se mueve
    // aunque ningún precio cambie; por eso cada punto devuelve cuántos
    // componentes lo forman.

    public function historialCategorias(Request $request): JsonResponse
    {
        $periodo = $request->query('periodo', '1y');
        $desde   = match ($periodo) {
            '6m'    => now()->subMonths(6)->startOfMonth(),
            '2y'    => now()->subYears(2)->startOfMonth(),
            '3y'    => now()->subYears(3)->startOfMonth(),
            default => now()->subYear()->startOfMonth(),
        };

        $pedidas = collect(explode(',', (string) $request->query('categorias', '')))
            ->map(fn ($c) => trim($c))
            ->filter(fn ($c) => in_array($c, Componente::CATEGORIAS, true))
            ->unique()
            ->values();

        $categorias = $pedidas->isEmpty() ? collect(Componente::CATEGORIAS) : $pedidas;
        $in         = implode(',', array_fill(0, $categorias->count(), '?'));

        $rows = DB::select("
            WITH meses AS (
                SELECT generate_series(
                    date_trunc('month', ?::date),
                    date_trunc('month', now()),
                    interval '1 month'
                )::date AS mes
            ),
            tramos AS (
                SELECT h.componente_id, h.precio, h.valid_from, h.valid_to
                FROM historial_precios h
                JOIN componentes c ON c.id = h.componente_id
                WHERE h.valid_to >= ? AND c.activo = true AND c.categoria IN ($in)
                UNION ALL
                SELECT p.componente_id, p.precio, p.vigente_desde::date AS valid_from, CURRENT_DATE AS valid_to
                FROM precios_actuales p
                JOIN componentes c ON c.id = p.componente_id
                WHERE c.activo = true AND c.categoria IN ($in)
            ),
            por_componente AS (
                SELECT
                    c.categoria,
                    m.mes,
                    t.componente_id,
                    SUM(t.precio * (LEAST(t.valid_to, (m.mes + INTERVAL '1 month' - INTERVAL '1 day')::date)
                                   - GREATEST(t.valid_from, m.mes) + 1))
                    / NULLIF(SUM(LEAST(t.valid_to, (m.mes + INTERVAL '1 month' - INTERVAL '1 day')::date)
                               - GREATEST(t.valid_from, m.mes) + 1), 0) AS media
                FROM meses m
                JOIN tramos t
                    ON t.valid_from <= (m.mes + INTERVAL '1 month' - INTERVAL '1 day')::date
                   AND t.valid_to   >= m.mes
                JOIN componentes c ON c.id = t.componente_id
                GROUP BY c.categoria, m.mes, t.componente_id
            )
            SELECT
                categoria,
                to_char(mes, 'YYYY-MM') AS periodo,
                ROUND(AVG(media)::numeric, 2) AS media,
                COUNT(*) AS componentes
            FROM por_componente
            WHERE media IS NOT NULL
            GROUP BY categoria, mes
            ORDER BY categoria, mes
        ", [
            $desde,
            $desde, ...$categorias->all(),
            ...$categorias->all(),
        ]);

        $series = collect($rows)
            ->groupBy('categoria')
            ->map(fn ($puntos, $categoria) => [
                'categoria' => $categoria,
                'puntos'    => $puntos->map(fn ($r) => [
                    'periodo'     => $r->periodo,
                    'media'       => (float) $r->media,
                    'componentes' => (int) $r->componentes,
                ])->values(),
            ])
            ->values();

        return response()->json(['series' => $series]);
    }
}

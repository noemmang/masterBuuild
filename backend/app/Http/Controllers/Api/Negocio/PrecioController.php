<?php

namespace App\Http\Controllers\Api\Negocio;

use App\Http\Controllers\Controller;
use App\Models\Componentes\Componente;
use App\Models\Negocio\EntradaPrecio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrecioController extends Controller
{
    // ── GET /api/v1/componentes/{uuid}/precios ────────────────────────────────

    public function actuales(string $uuid)
    {
        $componente = Componente::where('uuid', $uuid)->firstOrFail();

        // ── IDs del precio más reciente por tienda ────────────────────────────
        $ids = DB::select("
            SELECT id
            FROM (
                SELECT DISTINCT ON (tienda_id) id
                FROM entradas_precio
                WHERE componente_id = ?
                ORDER BY tienda_id, scraped_at DESC
            ) sub
        ", [$componente->id]);

        $idsArray = array_column($ids, 'id');

        // ── Tiendas que realmente tienen un precio activo ─────────────────────
        $tiendaIdsConPrecio = EntradaPrecio::whereIn('id', $idsArray)
            ->pluck('tienda_id')
            ->toArray();

        // ── Mejor precio (tienda más barata) ──────────────────────────────────
        $mejorTiendaId = EntradaPrecio::whereIn('id', $idsArray)
            ->orderBy('precio', 'asc')
            ->value('tienda_id');

        // ── Regalos activos por tienda ────────────────────────────────────────
        $regalosPorTienda = [];
        $regalosActivos   = $componente->regalosActivos()->get();

        foreach ($regalosActivos as $regalo) {
            $regalosPorTienda[$regalo->pivot->tienda_id] = $regalo;
        }

        // Fallback: si ninguna tienda del regalo vende este componente,
        // lo asignamos al mejor precio disponible.
        $hayMatchRegalo = !empty(array_intersect(array_keys($regalosPorTienda), $tiendaIdsConPrecio));

        if (!$hayMatchRegalo && $regalosActivos->isNotEmpty() && $mejorTiendaId) {
            $regalosPorTienda = [$mejorTiendaId => $regalosActivos->first()];
        }

        // ── Cupones activos por tienda ────────────────────────────────────────
        // Los cupones están en cupon_componente (many-to-many) y cada cupón
        // pertenece a una tienda vía cupon.tienda_id.
        $cuponesPorTienda = [];
        $cuponesActivos   = $componente->cuponesActivos()->get();

        foreach ($cuponesActivos as $cupon) {
            // Guardamos el primero por tienda (el más relevante)
            if (!isset($cuponesPorTienda[$cupon->tienda_id])) {
                $cuponesPorTienda[$cupon->tienda_id] = $cupon;
            }
        }

        // Fallback: si ninguna tienda del cupón vende este componente,
        // lo asignamos al mejor precio disponible (misma lógica que regalos).
        $hayMatchCupon = !empty(array_intersect(array_keys($cuponesPorTienda), $tiendaIdsConPrecio));

        if (!$hayMatchCupon && $cuponesActivos->isNotEmpty() && $mejorTiendaId) {
            $cuponesPorTienda = [$mejorTiendaId => $cuponesActivos->first()];
        }

        // ── Construir respuesta de precios ────────────────────────────────────
        $precios = EntradaPrecio::whereIn('id', $idsArray)
            ->with(['tienda'])
            ->orderBy('precio', 'asc')
            ->get()
            ->map(fn($p) => [
                'uuid'        => $p->uuid,
                'tienda'      => $p->tienda ? ['nombre' => $p->tienda->nombre] : null,
                'precio'      => (float) $p->precio,
                'en_stock'    => $p->en_stock,
                'url'         => $p->url,
                'actualizado' => $p->scraped_at?->diffForHumans(),
                'cupon'       => isset($cuponesPorTienda[$p->tienda_id])
                    ? $this->formatearCupon($cuponesPorTienda[$p->tienda_id])
                    : null,
                'regalo'      => isset($regalosPorTienda[$p->tienda_id])
                    ? $this->formatearRegalo($regalosPorTienda[$p->tienda_id])
                    : null,
            ]);

        return response()->json([
            'componente'   => ['uuid' => $componente->uuid, 'nombre' => $componente->nombre],
            'precios'      => $precios,
            'mejor_precio' => $precios->first(),
        ]);
    }

    // ── Helpers de formato ────────────────────────────────────────────────────

    private function formatearCupon($cupon): array
    {
        return [
            'codigo'    => $cupon->codigo,
            'tipo'      => $cupon->tipo,
            'descuento' => $cupon->tipo === 'porcentaje'
                ? (float) $cupon->porcentaje_descuento
                : (float) $cupon->descuento_fijo,
        ];
    }

    private function formatearRegalo($regalo): array
    {
        return [
            'nombre'         => $regalo->nombre,
            'imagen_url'     => $regalo->imagen_url,
            'valor_estimado' => (float) $regalo->valor_estimado,
        ];
    }

    // ── GET /api/v1/componentes/{uuid}/precios/historial ──────────────────────
    //
    // Query params:
    //   periodo  = 6m | 1y | 2y | 3y   (default: 1y)
    //   tienda   = uuid de tienda       (opcional)
    //
    // Respuesta:
    // {
    //   "resumen": { "min", "max", "media", "actual" },
    //   "puntos":  [{ "periodo": "2024-01", "min", "max", "media", "tiendas" }],
    //   "tiendas": [{ "uuid", "nombre" }]
    // }

    public function historial(string $uuid, Request $request): JsonResponse
    {
        $componente = Componente::where('uuid', $uuid)->firstOrFail();

        // ── Período ───────────────────────────────────────────────────────────
        $periodo = $request->query('periodo', '1y');
        $desde   = match ($periodo) {
            '6m'  => now()->subMonths(6)->startOfMonth(),
            '2y'  => now()->subYears(2)->startOfMonth(),
            '3y'  => now()->subYears(3)->startOfMonth(),
            default => now()->subYear()->startOfMonth(),
        };

        // ── Query base ────────────────────────────────────────────────────────
        $base = EntradaPrecio::where('componente_id', $componente->id)
            ->where('scraped_at', '>=', $desde);

        if ($request->filled('tienda')) {
            $base->whereHas('tienda', fn($q) =>
                $q->where('uuid', $request->tienda)
            );
        }

        // ── Sin datos ─────────────────────────────────────────────────────────
        if ((clone $base)->count() === 0) {
            return response()->json([
                'resumen' => ['min' => null, 'max' => null, 'media' => null, 'actual' => null],
                'puntos'  => [],
                'tiendas' => [],
            ]);
        }

        // ── Agrupación mensual — PostgreSQL usa TO_CHAR ───────────────────────
        $puntos = (clone $base)
            ->select([
                DB::raw("TO_CHAR(scraped_at, 'YYYY-MM') as periodo"),
                DB::raw('MIN(precio) as min'),
                DB::raw('MAX(precio) as max'),
                DB::raw('ROUND(AVG(precio)::numeric, 2) as media'),
                DB::raw('COUNT(DISTINCT tienda_id) as tiendas'),
            ])
            ->groupBy(DB::raw("TO_CHAR(scraped_at, 'YYYY-MM')"))
            ->orderBy('periodo')
            ->get()
            ->map(fn($row) => [
                'periodo' => $row->periodo,
                'min'     => (float) $row->min,
                'max'     => (float) $row->max,
                'media'   => (float) $row->media,
                'tiendas' => (int)   $row->tiendas,
            ]);

        // ── Resumen global del período ────────────────────────────────────────
        $resumenRaw = (clone $base)
            ->selectRaw('
                MIN(precio) as min,
                MAX(precio) as max,
                ROUND(AVG(precio)::numeric, 2) as media
            ')
            ->first();

        // Precio actual = precio más bajo del scrape más reciente
        $actual = EntradaPrecio::where('componente_id', $componente->id)
            ->orderBy('scraped_at', 'desc')
            ->orderBy('precio', 'asc')
            ->value('precio');

        // ── Tiendas únicas con datos en el período ────────────────────────────
        $tiendaIds = (clone $base)
            ->select('tienda_id')
            ->distinct()
            ->pluck('tienda_id');

        $tiendas = \App\Models\Negocio\Tienda::whereIn('id', $tiendaIds)
            ->select('uuid', 'nombre')
            ->get()
            ->map(fn($t) => ['uuid' => $t->uuid, 'nombre' => $t->nombre])
            ->values();

        return response()->json([
            'resumen' => [
                'min'    => $resumenRaw ? (float) $resumenRaw->min   : null,
                'max'    => $resumenRaw ? (float) $resumenRaw->max   : null,
                'media'  => $resumenRaw ? (float) $resumenRaw->media : null,
                'actual' => $actual     ? (float) $actual            : null,
            ],
            'puntos'  => $puntos,
            'tiendas' => $tiendas,
        ]);
    }
}
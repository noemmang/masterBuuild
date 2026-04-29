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
    // ── GET /api/v1/componentes/{uuid}/precios ─────────────────────────────────
    // Sin cambios

    public function actuales(string $uuid)
    {
        $componente = Componente::where('uuid', $uuid)->firstOrFail();

        $precios = EntradaPrecio::actual()
            ->where('componente_id', $componente->id)
            ->with(['tienda', 'cupon'])
            ->orderByRaw('COALESCE(precio_con_cupon, precio) ASC')
            ->get()
            ->map(fn($p) => [
                'tienda'           => $p->tienda->nombre,
                'tienda_logo'      => $p->tienda->logo_url,
                'precio'           => $p->precio,
                'precio_con_cupon' => $p->precio_con_cupon,
                'precio_efectivo'  => $p->precioEfectivo(),
                'cupon'            => $p->cupon ? [
                    'codigo'      => $p->cupon->codigo,
                    'descripcion' => $p->cupon->descripcion,
                    'tipo'        => $p->cupon->tipo,
                    'descuento'   => $p->cupon->tipo === 'porcentaje'
                        ? $p->cupon->porcentaje_descuento . '%'
                        : $p->cupon->descuento_fijo . '€',
                ] : null,
                'en_stock'    => $p->en_stock,
                'url'         => $p->url,
                'actualizado' => $p->scraped_at->diffForHumans(),
            ]);

        return response()->json([
            'componente'   => [
                'uuid'   => $componente->uuid,
                'nombre' => $componente->nombre,
            ],
            'precios'      => $precios,
            'mejor_precio' => $precios->first(),
        ]);
    }

    // ── GET /api/v1/componentes/{uuid}/precios/historial ──────────────────────
    //
    // Query params:
    //   periodo  = 6m | 1y | 2y | 3y   (default: 1y)
    //   tienda   = uuid de tienda       (opcional, filtra por tienda)
    //
    // Respuesta:
    // {
    //   "componente": { "uuid": "...", "nombre": "..." },
    //   "resumen":    { "min": 99.99, "max": 149.99, "media": 124.50, "actual": 109.99 },
    //   "puntos": [
    //     { "periodo": "2024-01", "min": 99.99, "max": 139.99, "media": 119.50, "tiendas": 3 },
    //     ...
    //   ],
    //   "tiendas": [ { "uuid": "...", "nombre": "PcComponentes" }, ... ]
    // }

    public function historial(string $uuid, Request $request): JsonResponse
    {
        $componente = Componente::where('uuid', $uuid)->firstOrFail();

        // ── Período ───────────────────────────────────────────────────────────
        $periodo = $request->query('periodo', '1y');
        $desde   = match ($periodo) {
            '6m'  => now()->subMonths(6),
            '2y'  => now()->subYears(2),
            '3y'  => now()->subYears(3),
            default => now()->subYear(), // '1y'
        };

        // ── Base query — reutiliza los scopes del modelo ──────────────────────
        $base = EntradaPrecio::historial($componente->id)
            ->entreFechas($desde, now())
            ->enStock();

        if ($request->filled('tienda')) {
            $base->whereHas('tienda', fn($q) =>
                $q->where('uuid', $request->tienda)
            );
        }

        // ── Agrupación mensual ────────────────────────────────────────────────
        $puntos = (clone $base)
            ->select([
                DB::raw("DATE_FORMAT(scraped_at, '%Y-%m') as periodo"),
                DB::raw('MIN(COALESCE(precio_con_cupon, precio)) as min'),
                DB::raw('MAX(COALESCE(precio_con_cupon, precio)) as max'),
                DB::raw('ROUND(AVG(COALESCE(precio_con_cupon, precio)), 2) as media'),
                DB::raw('COUNT(DISTINCT tienda_id) as tiendas'),
            ])
            ->groupBy('periodo')
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
                MIN(COALESCE(precio_con_cupon, precio)) as min,
                MAX(COALESCE(precio_con_cupon, precio)) as max,
                ROUND(AVG(COALESCE(precio_con_cupon, precio)), 2) as media
            ')
            ->first();

        // Precio actual = mejor precio efectivo del scrape más reciente
        $mejorActual = EntradaPrecio::scopeMejorPrecio(
            EntradaPrecio::query(),
            $componente->id
        );

        // ── Tiendas con datos en el período ───────────────────────────────────
        $tiendas = (clone $base)
            ->with('tienda:id,uuid,nombre')
            ->select('tienda_id')
            ->distinct()
            ->get()
            ->map(fn($ep) => [
                'uuid'   => $ep->tienda->uuid,
                'nombre' => $ep->tienda->nombre,
            ])
            ->filter(fn($t) => filled($t['uuid']))
            ->values();

        return response()->json([
            'componente' => [
                'uuid'   => $componente->uuid,
                'nombre' => $componente->nombre,
            ],
            'resumen' => [
                'min'    => $resumenRaw ? (float) $resumenRaw->min   : null,
                'max'    => $resumenRaw ? (float) $resumenRaw->max   : null,
                'media'  => $resumenRaw ? (float) $resumenRaw->media : null,
                'actual' => $mejorActual
                    ? (float) $mejorActual->precioEfectivo()
                    : null,
            ],
            'puntos'  => $puntos,
            'tiendas' => $tiendas,
        ]);
    }
}
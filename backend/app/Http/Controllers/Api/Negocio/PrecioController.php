<?php

namespace App\Http\Controllers\Api\Negocio;

use App\Http\Controllers\Controller;
use App\Models\Componentes\Componente;
use App\Models\Negocio\EntradaPrecio;
use Illuminate\Http\Request;

class PrecioController extends Controller
{
    // Historial de precios de un componente
    public function historial(string $uuid, Request $request)
    {
        $componente = Componente::where('uuid', $uuid)->firstOrFail();

        $query = EntradaPrecio::where('componente_id', $componente->id)
            ->with('tienda');

        // Filtro por tienda
        if ($request->filled('tienda')) {
            $query->whereHas('tienda', fn($q) =>
                $q->where('uuid', $request->tienda)
            );
        }

        // Filtro por rango de fechas
        if ($request->filled('desde') && $request->filled('hasta')) {
            $query->entreFechas($request->desde, $request->hasta);
        } else {
            // Por defecto últimos 30 días
            $query->entreFechas(now()->subDays(30), now());
        }

        $historial = $query->orderBy('scraped_at', 'desc')->get();

        // Agrupar por tienda para el gráfico
        $porTienda = $historial->groupBy('tienda.nombre')->map(fn($precios) =>
            $precios->map(fn($p) => [
                'precio'     => $p->precio,
                'con_cupon'  => $p->precio_con_cupon,
                'en_stock'   => $p->en_stock,
                'fecha'      => $p->scraped_at->format('Y-m-d H:i'),
            ])
        );

        return response()->json([
            'componente' => [
                'uuid'   => $componente->uuid,
                'nombre' => $componente->nombre,
            ],
            'historial'  => $porTienda,
            'mejor_precio_actual' => EntradaPrecio::scopeMejorPrecio(
                EntradaPrecio::query(), $componente->id
            ),
        ]);
    }

    // Precios actuales de un componente en todas las tiendas
    public function actuales(string $uuid)
    {
        $componente = Componente::where('uuid', $uuid)->firstOrFail();

        $precios = EntradaPrecio::actual()
            ->where('componente_id', $componente->id)
            ->with(['tienda', 'cupon'])
            ->orderByRaw('COALESCE(precio_con_cupon, precio) ASC')
            ->get()
            ->map(fn($p) => [
                'tienda'          => $p->tienda->nombre,
                'tienda_logo'     => $p->tienda->logo_url,
                'precio'          => $p->precio,
                'precio_con_cupon'=> $p->precio_con_cupon,
                'precio_efectivo' => $p->precioEfectivo(),
                'cupon'           => $p->cupon ? [
                    'codigo'      => $p->cupon->codigo,
                    'descripcion' => $p->cupon->descripcion,
                    'tipo'        => $p->cupon->tipo,
                    'descuento'   => $p->cupon->tipo === 'porcentaje'
                        ? $p->cupon->porcentaje_descuento . '%'
                        : $p->cupon->descuento_fijo . '€',
                ] : null,
                'en_stock'        => $p->en_stock,
                'url'             => $p->url,
                'actualizado'     => $p->scraped_at->diffForHumans(),
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
}
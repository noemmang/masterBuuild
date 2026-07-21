<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Forma ligera de un componente para vistas de listado
 * (GET /api/v1/componentes). No incluye specs técnicas (cpu, gpu, ram...)
 * ni el histórico de precios: eso solo se pide cuando el usuario abre el
 * detalle de un componente concreto, vía /componentes/{uuid} y
 * /componentes/{uuid}/precios.
 *
 * Los campos precio_min, precio_max, num_tiendas, tiene_cupon y
 * tiene_regalo vienen ya calculados desde el controller con
 * withMin/withMax/withCount/withExists, así que aquí solo se formatean.
 */
class ComponenteListadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,
            'nombre'       => $this->nombre,
            'categoria'    => $this->categoria,
            'imagen_url'   => $this->imagen_url,
            'descripcion'  => $this->descripcion,
            'marca'        => $this->marca ? ['nombre' => $this->marca->nombre] : null,
            'precio_min'   => $this->precio_min !== null ? (float) $this->precio_min : null,
            'precio_max'   => $this->precio_max !== null ? (float) $this->precio_max : null,
            'num_tiendas'  => (int) $this->num_tiendas,
            'tiene_cupon'  => (bool) $this->tiene_cupon,
            'tiene_regalo' => (bool) $this->tiene_regalo,
        ];
    }
}
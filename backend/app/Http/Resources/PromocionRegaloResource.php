<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Forma de un regalo (promoción) tal como lo consume el front en el panel
 * de tiendas. Va anidado dentro de cada precio de GET
 * /componentes/{uuid}/precios, así que ya está ligado a UNA tienda: no
 * hace falta repetir aquí quién es.
 *
 * Solo se serializan promociones que ya pasaron el filtro de
 * Componente::promocionesRegaloVisibles() (activas, vigentes y con esa
 * tienda en stock).
 */
class PromocionRegaloResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,
            'titulo'       => $this->titulo,
            'tipo'         => $this->tipo,
            'url'          => $this->url,
            'imagen_url'   => $this->imagen_url,
            'fecha_inicio' => $this->fecha_inicio?->toDateString(),
            'fecha_fin'    => $this->fecha_fin?->toDateString(),
        ];
    }
}

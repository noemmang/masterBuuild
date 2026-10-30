<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Forma de una promoción tal como la consume el banner del home (ver
 * PromocionController::banners). A diferencia de PromocionRegaloResource,
 * que va anidada dentro del precio de UNA tienda, aquí la promoción viaja
 * sola, así que se añade el nombre de la tienda a la que pertenece.
 */
class PromocionBannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'       => $this->uuid,
            'titulo'     => $this->titulo,
            'tienda'     => $this->tienda?->nombre,
            // Página de la tienda donde se explica la promoción/regalo.
            'url'        => $this->url,
            'imagen_url' => $this->imagen_url,
            'fecha_fin'  => $this->fecha_fin?->toDateString(),
        ];
    }
}
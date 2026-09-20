<?php

namespace App\Scrapers\DTO;

/**
 * Una promoción de regalo tal como la anuncia una tienda en la ficha de un
 * producto, ya normalizada e independiente de la tienda de origen.
 *
 * slug: identificador estable de la promoción dentro de la tienda (con la
 * tienda forma la clave natural en promociones_regalo). Los scrapers deben
 * devolver siempre el mismo slug para la misma promoción entre ejecuciones.
 *
 * fechaInicio / fechaFin: "Y-m-d" o null si la tienda no las indica.
 */
final class PromocionScrapeada
{
    public function __construct(
        public readonly string $slug,
        public readonly string $titulo,
        public readonly ?string $tipo,
        public readonly string $url,
        public readonly ?string $imagenUrl,
        public readonly ?string $fechaInicio,
        public readonly ?string $fechaFin,
    ) {
    }
}
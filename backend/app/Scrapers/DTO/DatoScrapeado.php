<?php

namespace App\Scrapers\DTO;

/**
 * Resultado normalizado de un scraper, independientemente de la tienda
 * de la que provenga. Todos los scrapers deben devolver esto.
 */
final class DatoScrapeado
{
    public function __construct(
        public readonly float $precio,
        public readonly bool $enStock,
        public readonly string $moneda,
        public readonly ?string $nombreProducto,
        public readonly string $url,
    ) {
    }
}

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
        /**
         * Promociones de regalo que la tienda anuncia para este producto.
         *
         *  - null       → este scraper NO informa de promociones (o no pudo
         *                 leerlas de forma fiable esta vez). Es "no sé": el
         *                 resto del sistema NO toca los regalos que ya
         *                 hubiera guardados. Es el valor por defecto, así que
         *                 los scrapers que no implementan promociones (p. ej.
         *                 Neobyte) siguen funcionando igual sin cambios.
         *  - []         → se comprobó la ficha y NO tiene regalo. Los que
         *                 hubiera guardados para este producto/tienda se
         *                 desactivan.
         *  - [..]       → estos son los regalos vigentes; los guardados que
         *                 no estén en la lista se desactivan.
         *
         * @var PromocionScrapeada[]|null
         */
        public readonly ?array $promociones = null,
    ) {
    }
}
<?php

namespace App\Scrapers;

use App\Scrapers\Contracts\ScraperTienda;
use App\Scrapers\DTO\DatoScrapeado;
use App\Scrapers\Exceptions\ScrapingException;
use Symfony\Component\DomCrawler\Crawler;

class NeobyteScraper extends AbstractScraper implements ScraperTienda
{
    public function extraerDatos(string $url): DatoScrapeado
    {
        $crawler = $this->descargar($url);

        $producto = $this->extraerJsonLdProducto($crawler);

        if ($producto !== null) {
            return $this->desdeJsonLd($producto, $url);
        }

        // ⚠️ SIN VERIFICAR: igual que con Coolmod, no se ha podido inspeccionar
        // el HTML real de una ficha de producto de Neobyte (el sitio bloquea
        // la descarga automatizada por detección de bots al intentar
        // revisarlo). Neobyte corre sobre PrestaShop (URLs del tipo
        // "...-tarjeta-grafica-23854.html"), y las tiendas PrestaShop suelen
        // incluir un bloque JSON-LD schema.org/Product de serie, así que lo
        // más probable es que ni siquiera se llegue a usar este fallback.
        // Aun así, antes de confiar en desdeHtml():
        //   1. Abre una ficha de producto real en Chrome.
        //   2. Ctrl+U (ver código fuente) y busca "application/ld+json"
        //      para confirmar si el bloque existe (si el sitio devuelve una
        //      página de verificación/challenge en vez del HTML real, este
        //      camino tampoco funcionará y hay que resolver el bloqueo antes).
        //   3. Si existe, compara su estructura con resolverOffer() en
        //      AbstractScraper.
        //   4. Si NO existe JSON-LD, usa DevTools > Elements sobre el precio
        //      visible en pantalla para sacar el selector CSS real y
        //      reemplaza los de desdeHtml() más abajo.
        return $this->desdeHtml($crawler, $url);
    }

    protected function desdeJsonLd(array $producto, string $url): DatoScrapeado
    {
        $offer = $this->resolverOffer($producto['offers'] ?? []);

        if ($offer === null || !isset($offer['price'])) {
            throw new ScrapingException("JSON-LD sin precio en {$url}");
        }

        $disponibilidad = strtolower((string) ($offer['availability'] ?? ''));

        return new DatoScrapeado(
            precio: (float) $offer['price'],
            enStock: str_contains($disponibilidad, 'instock'),
            moneda: strtoupper((string) ($offer['priceCurrency'] ?? 'EUR')),
            nombreProducto: $producto['name'] ?? null,
            url: $url,
        );
    }

    /**
     * ⚠️ PLANTILLA SIN VERIFICAR — ver aviso en extraerDatos().
     * Los selectores de abajo son una suposición razonable (comunes en
     * temas de PrestaShop: precio en "[itemprop=price]" o ".current-price",
     * botón de compra con id "add-to-cart"), NO confirmada contra el HTML
     * real de Neobyte. Reemplázalos en cuanto los verifiques con DevTools.
     */
    protected function desdeHtml(Crawler $crawler, string $url): DatoScrapeado
    {
        try {
            $precioTexto = $crawler->filter('[itemprop="price"], .current-price, .product-price')
                ->first()
                ->text();
        } catch (\InvalidArgumentException) {
            throw new ScrapingException(
                "No se pudo extraer el precio de {$url} (ni JSON-LD ni selector CSS). "
                ."Estos selectores son una plantilla sin verificar contra el HTML real "
                ."de Neobyte — ábrela en el navegador, inspecciona el precio con "
                ."DevTools y ajusta el selector aquí."
            );
        }

        $precio = (float) str_replace(
            ['.', ',', '€', ' ', "\xc2\xa0"],
            ['', '.', '', '', ''],
            trim($precioTexto)
        );

        // También sin verificar: ajusta el selector cuando confirmes cómo
        // marca Neobyte el stock (botón "Añadir al carrito", texto
        // "No disponible" —lo hemos visto en los snippets de búsqueda—,
        // clase CSS específica, etc.)
        $enStock = $crawler->filter('#add-to-cart, [data-add-to-cart], .add-to-cart')->count() > 0;

        return new DatoScrapeado(
            precio: $precio,
            enStock: $enStock,
            moneda: 'EUR',
            nombreProducto: null,
            url: $url,
        );
    }
}
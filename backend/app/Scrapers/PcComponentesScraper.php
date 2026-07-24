<?php

namespace App\Scrapers;

use App\Scrapers\Contracts\ScraperTienda;
use App\Scrapers\DTO\DatoScrapeado;
use App\Scrapers\Exceptions\ScrapingException;
use Symfony\Component\DomCrawler\Crawler;

class PcComponentesScraper extends AbstractScraper implements ScraperTienda
{
    public function extraerDatos(string $url): DatoScrapeado
    {
        $crawler = $this->descargar($url);

        $producto = $this->extraerJsonLdProducto($crawler);

        if ($producto !== null) {
            return $this->desdeJsonLd($producto, $url);
        }

        // Fallback si PcComponentes no sirve JSON-LD para esta página o
        // cambia su estructura. OJO: estos selectores son un punto de
        // partida razonable pero HAY QUE VERIFICARLOS contra el HTML real
        // (Chrome DevTools > Elements) antes de confiar en ellos, y
        // revisarlos si el scraper empieza a fallar.
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

    protected function desdeHtml(Crawler $crawler, string $url): DatoScrapeado
    {
        // Selectores actualizados según el HTML real de PcComponentes
        // (verificado en julio 2026). El sitio muestra el precio como
        // "158" + "," + "06" + "€" en spans separados dentro de
        // #pdp-price-current-container, en vez de un único nodo con
        // data-cy="product-price".
        try {
            $precioEntero = $crawler->filter('#pdp-price-current-integer')->first()->text();
        } catch (\InvalidArgumentException) {
            throw new ScrapingException(
                "No se pudo extraer el precio de {$url} (ni JSON-LD ni selector CSS). "
                ."Revisa el HTML actual de la página y ajusta el selector."
            );
        }

        $precio = (float) str_replace(
            ['.', ',', '€', ' ', "\xc2\xa0"],
            ['', '.', '', '', ''],
            trim($precioEntero)
        );

        $enStock = $crawler->filter('#pdp-add-to-cart')->count() > 0;

        return new DatoScrapeado(
            precio: $precio,
            enStock: $enStock,
            moneda: 'EUR',
            nombreProducto: null,
            url: $url,
        );
    }
}
<?php

namespace App\Scrapers;

use App\Scrapers\Contracts\ScraperTienda;
use App\Scrapers\DTO\DatoScrapeado;
use App\Scrapers\Exceptions\ScrapingException;
use Symfony\Component\DomCrawler\Crawler;

class CoolmodScraper extends AbstractScraper implements ScraperTienda
{
    public function extraerDatos(string $url): DatoScrapeado
    {
        $crawler = $this->descargar($url);

        $producto = $this->extraerJsonLdProducto($crawler);

        if ($producto !== null) {
            return $this->desdeJsonLd($producto, $url);
        }

        // ⚠️ SIN VERIFICAR: no se pudo inspeccionar el HTML real de Coolmod
        // (la página bloqueó la descarga automatizada por detección de
        // bots al intentar revisarla). Los selectores de desdeHtml() son
        // solo un punto de partida razonable. Antes de confiar en ellos:
        //   1. Abre una ficha de producto real en Chrome.
        //   2. Ctrl+U (ver código fuente) y busca "application/ld+json"
        //      para confirmar si este bloque SIQUIERA existe en Coolmod
        //      (si el sitio devuelve una página de "verificación" en vez
        //      del HTML real, este camino JSON-LD tampoco funcionará y
        //      hay que resolver el bloqueo antes de seguir).
        //   3. Si existe, compara su estructura con resolverOffer() en
        //      AbstractScraper — Coolmod podría anidar "offers" distinto
        //      a PcComponentes.
        //   4. Si NO existe JSON-LD, usa DevTools > Elements sobre el
        //      precio visible en pantalla para sacar el selector CSS real
        //      y reemplaza los de desdeHtml() más abajo.
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
     * Los selectores de abajo son una suposición razonable basada en
     * patrones comunes de tiendas online, NO en el HTML real de Coolmod.
     * Reemplázalos en cuanto confirmes los reales con DevTools.
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
                ."de Coolmod — ábrela en el navegador, inspecciona el precio con "
                ."DevTools y ajusta el selector aquí."
            );
        }

        $precio = (float) str_replace(
            ['.', ',', '€', ' ', "\xc2\xa0"],
            ['', '.', '', '', ''],
            trim($precioTexto)
        );

        // También sin verificar: ajusta el selector cuando confirmes cómo
        // marca Coolmod el stock (botón "Añadir al carrito", texto
        // "Agotado", clase CSS específica, etc.)
        $enStock = $crawler->filter('.add-to-cart, [data-add-to-cart]')->count() > 0;

        return new DatoScrapeado(
            precio: $precio,
            enStock: $enStock,
            moneda: 'EUR',
            nombreProducto: null,
            url: $url,
        );
    }
}
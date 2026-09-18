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
            return $this->desdeJsonLd($producto, $url, $crawler);
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

    protected function desdeJsonLd(array $producto, string $url, Crawler $crawler): DatoScrapeado
    {
        $offer = $this->resolverOffer($producto['offers'] ?? []);

        if ($offer === null || !isset($offer['price'])) {
            throw new ScrapingException("JSON-LD sin precio en {$url}");
        }

        return new DatoScrapeado(
            precio: (float) $offer['price'],
            // ⚠️ OJO: en Coolmod NO usamos $offer['availability'] directamente.
            // Ver el aviso completo en resolverStockReal(): ese campo del
            // JSON-LD viene desincronizado del stock real (siempre
            // "https://schema.org/InStock", agotado o no), así que el precio
            // se saca bien pero el stock siempre salía disponible. Es el bug
            // reportado con la Fractal Design Terra: 2 tiendas agotadas,
            // Coolmod no se marcaba como agotado.
            enStock: $this->resolverStockReal($crawler, $offer),
            moneda: strtoupper((string) ($offer['priceCurrency'] ?? 'EUR')),
            nombreProducto: $producto['name'] ?? null,
            url: $url,
        );
    }

    /**
     * Determina el stock REAL de Coolmod ignorando (o solo como último
     * recurso) el "offers.availability" del JSON-LD.
     *
     * CONFIRMADO con el HTML de una ficha agotada (Fractal Design Terra,
     * PROD-027107): el JSON-LD de <head> declara
     *   "availability": "https://schema.org/InStock"
     * de forma estática pese a que el producto está agotado. Probablemente
     * se genera una vez al crear la ficha y no se regenera cuando cambia
     * el stock, así que NO es fiable en Coolmod (a diferencia de Neobyte,
     * donde este mismo campo sí parece reflejar el stock real).
     *
     * La señal fiable que sí varía con el stock real es el input oculto
     * que Coolmod usa para su propio tracking (Connectif):
     *   <input id="connectif-prod-info" data-itemavailability="OutOfStock">
     * (o "InStock" cuando hay unidades). Como red de seguridad, si ese
     * input no apareciera o no trajera el atributo, comprobamos también
     * el marcado/texto que Coolmod muestra cuando no hay stock:
     *   - el botón "No disponible" (id="drawer-without-stock") que
     *     sustituye al selector de cantidad/añadir al carrito.
     *   - el texto "Artículo no disponible" del bloque de disponibilidad.
     *
     * ⚠️ Verificado solo contra una ficha agotada. Antes de dar esto por
     * cerrado del todo, sería ideal repetir la comprobación con una ficha
     * de Coolmod que SÍ tenga stock, para confirmar que
     * data-itemavailability="InStock" aparece igual (y que no falta el
     * atributo cuando hay stock, que dispararía el fallback de texto).
     */
    protected function resolverStockReal(Crawler $crawler, array $offer): bool
    {
        $infoConnectif = $crawler->filter('#connectif-prod-info');

        if ($infoConnectif->count() > 0) {
            $atributo = $infoConnectif->first()->attr('data-itemavailability');

            if ($atributo !== null && $atributo !== '') {
                $atributo = strtolower(trim($atributo));

                if (str_contains($atributo, 'outofstock')) {
                    return false;
                }

                if (str_contains($atributo, 'instock')) {
                    return true;
                }
            }
        }

        // Fallback 1: el botón/label que reemplaza al de compra cuando no
        // hay stock.
        if ($crawler->filter('#drawer-without-stock')->count() > 0) {
            return false;
        }

        // Fallback 2: texto visible del bloque de disponibilidad.
        $body = $crawler->filter('body');
        if ($body->count() > 0) {
            $texto = $body->first()->text();
            if (str_contains($texto, 'Artículo no disponible')
                || str_contains($texto, 'No disponible')) {
                return false;
            }
        }

        // Último recurso: lo que diga el JSON-LD. Puede estar
        // desactualizado en Coolmod, pero es mejor que nada si no se
        // encontró ninguna señal fiable en el HTML.
        $disponibilidad = strtolower((string) ($offer['availability'] ?? ''));

        return str_contains($disponibilidad, 'instock');
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

        // El stock NO se saca de un selector "add-to-cart" (ese selector
        // era una suposición sin verificar y, además, en Coolmod ni
        // siquiera haría falta: ver resolverStockReal() más abajo, que usa
        // el input #connectif-prod-info / el texto "No disponible" y es la
        // misma lógica que ya usamos cuando sí hay JSON-LD.
        $enStock = $this->resolverStockReal($crawler, []);

        return new DatoScrapeado(
            precio: $precio,
            enStock: $enStock,
            moneda: 'EUR',
            nombreProducto: null,
            url: $url,
        );
    }
}
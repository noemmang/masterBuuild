<?php

namespace App\Scrapers;

use App\Scrapers\Contracts\ScraperTienda;
use App\Scrapers\DTO\DatoScrapeado;
use App\Scrapers\DTO\PromocionScrapeada;
use App\Scrapers\Exceptions\ScrapingException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

class NeobyteScraper extends AbstractScraper implements ScraperTienda
{
    public function extraerDatos(string $url): DatoScrapeado
    {
        $crawler = $this->descargar($url);

        $producto = $this->extraerJsonLdProducto($crawler);

        if ($producto !== null) {
            return $this->desdeJsonLd($producto, $url, $crawler);
        }

        // Neobyte corre sobre PrestaShop y lo normal es que la ficha traiga
        // un bloque JSON-LD schema.org/Product en el <head>, así que este
        // fallback rara vez se usa. Aun así sus selectores ya están
        // contrastados con el <body> de una ficha real (ver desdeHtml()).
        return $this->desdeHtml($crawler, $url);
    }

    protected function desdeJsonLd(array $producto, string $url, Crawler $crawler): DatoScrapeado
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
            promociones: $this->extraerPromocionesRegalo($crawler, $url),
        );
    }

    /**
     * Selectores contrastados con el <body> de una ficha real de Neobyte
     * (PrestaShop, tema Warehouse):
     *
     *  - Precio: <div class="product-prices"> … <span class="product-price
     *    current-price-value" content="1037.9">1.037,90 €</span>.
     *    ⚠️ NO vale ".product-price" a secas (el selector que había antes):
     *    ese mismo class lo llevan los productos del menú desplegable
     *    ("Nuestros destacados") y los de "Productos similares", y el menú
     *    va ANTES que el producto en el documento, así que first() devolvía
     *    el precio de OTRO producto sin dar error alguno. Por eso se acota
     *    a ".product-prices" (el bloque de precios del producto principal).
     *
     *  - Stock: el botón "Añadir al carrito" del formulario del producto
     *    (#add-to-cart-or-refresh). Tampoco vale ".add-to-cart" a secas:
     *    todas las tarjetas de "Productos similares" traen su propio botón
     *    con esa clase, y daría "en stock" aunque el principal estuviera
     *    agotado. Si el botón está deshabilitado, o el bloque de
     *    disponibilidad (#product-availability) es "product-unavailable",
     *    se considera agotado.
     *    ⚠️ Solo se ha visto una ficha CON stock; el marcado exacto de una
     *    agotada (botón disabled / clase product-unavailable) es el
     *    comportamiento estándar de PrestaShop pero sin confirmar en esta
     *    tienda.
     */
    protected function desdeHtml(Crawler $crawler, string $url): DatoScrapeado
    {
        $nodoPrecio = $crawler->filter('.product-prices .current-price-value, .product-prices [itemprop="price"]');

        if ($nodoPrecio->count() === 0) {
            throw new ScrapingException(
                "No se pudo extraer el precio de {$url} (ni JSON-LD ni el bloque .product-prices). "
                .'Puede que Neobyte haya cambiado el diseño de la ficha: revisa el selector en desdeHtml().'
            );
        }

        $primero = $nodoPrecio->first();
        $contenido = $primero->attr('content');

        // El atributo "content" trae el número limpio ("1037.9"); el texto
        // visible ("1.037,90 €") es solo el plan B.
        $precio = ($contenido !== null && is_numeric($contenido))
            ? (float) $contenido
            : (float) str_replace(
                ['.', ',', '€', ' ', "\xc2\xa0"],
                ['', '.', '', '', ''],
                trim($primero->text())
            );

        if ($precio <= 0) {
            throw new ScrapingException("Precio no válido ({$precio}) extraído de {$url}");
        }

        $boton = $crawler->filter('#add-to-cart-or-refresh .add-to-cart');
        $enStock = $boton->count() > 0 && $boton->first()->attr('disabled') === null;

        $disponibilidad = $crawler->filter('#product-availability');
        if ($disponibilidad->count() > 0
            && str_contains(strtolower((string) $disponibilidad->first()->attr('class')), 'product-unavailable')) {
            $enStock = false;
        }

        return new DatoScrapeado(
            precio: $precio,
            enStock: $enStock,
            moneda: 'EUR',
            nombreProducto: null,
            url: $url,
            promociones: $this->extraerPromocionesRegalo($crawler, $url),
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // Regalos / promociones
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Regalos que Neobyte anuncia en la ficha de un producto.
     *
     * Estructura (confirmada con el <body> de una RTX 5070 con regalo):
     *  - Bajo el bloque de compra, un panel
     *      <div id="promociones"><h4>Promociones incluidas</h4>
     *        <div class="promociones_banners">
     *          <a href="https://www.neobyte.es/content/{slug}-{id}" title="¡Consigue el juego…!">
     *            <img src="/modules/ps_promociones/uploads/Banner_….jpg" alt="…">
     *          </a> …
     *    Cada <a> es una promoción: el href es la página con la descripción
     *    extensa, el title/alt el nombre y la imagen el banner. Puede haber
     *    varios <a>. Neobyte NO muestra tipo ni fechas de vigencia.
     *  - En la portada del producto, además, un icono
     *      <ul class="product-promos"><li class="neoREGALOSV"></li></ul>
     *    ⚠️ Ese mismo icono aparece en las tarjetas de "Productos
     *    similares" (y en las del menú), así que solo se mira el de la
     *    portada (.product-cover), nunca el de toda la página.
     *
     * Devuelve:
     *  - [...] → las promociones leídas.
     *  - []    → la ficha no anuncia regalo (ni panel ni icono de portada).
     *  - null  → no fiable: el icono de portada dice que hay regalo pero no
     *            se pudo leer ninguna promoción del panel (rediseño, carga
     *            diferida…). "No sé", no "no hay": no se debe borrar lo que
     *            ya hubiera guardado.
     *
     * Nunca lanza: un fallo aquí no debe tirar el scraping del precio.
     *
     * ⚠️ Solo se ha visto una ficha CON regalo. Que una ficha sin regalo no
     * lleve ni #promociones ni el icono es lo esperable, pero está sin
     * confirmar con una ficha real de un producto sin promoción.
     *
     * @return PromocionScrapeada[]|null
     */
    public function extraerPromocionesRegalo(Crawler $crawler, string $url): ?array
    {
        try {
            return $this->leerPromocionesRegalo($crawler, $url);
        } catch (Throwable $e) {
            Log::warning("Neobyte: no se pudieron leer los regalos de {$url}: {$e->getMessage()}");

            return null;
        }
    }

    /** @return PromocionScrapeada[]|null */
    private function leerPromocionesRegalo(Crawler $crawler, string $url): ?array
    {
        $promociones = [];
        $procesados = [];

        foreach ($crawler->filter('#promociones .promociones_banners a') as $enlace) {
            /** @var \DOMElement $enlace */
            $urlPromo = $this->urlAbsoluta((string) $enlace->getAttribute('href'), $url);
            $slug = $urlPromo !== null ? $this->slugPromocion($urlPromo) : null;

            if ($slug === null || isset($procesados[$slug])) {
                continue;
            }
            $procesados[$slug] = true;

            $promociones[] = $this->construirPromocion($slug, $urlPromo, $enlace, $url);
        }

        if ($promociones !== []) {
            return $promociones;
        }

        $anunciaRegalo = $crawler->filter('.product-cover .product-promos .neoREGALOSV')->count() > 0;

        if ($anunciaRegalo) {
            Log::warning(
                "Neobyte: {$url} muestra el icono de regalo en la portada pero no se reconoció "
                .'ninguna promoción en #promociones. ¿Ha cambiado el HTML? Se conservan los regalos ya guardados.'
            );

            return null;
        }

        return [];
    }

    private function construirPromocion(
        string $slug,
        string $urlPromo,
        \DOMElement $enlace,
        string $urlProducto,
    ): PromocionScrapeada {
        $img = $enlace->getElementsByTagName('img')->item(0);

        $imagen = null;
        $textoImg = null;

        if ($img instanceof \DOMElement) {
            foreach (['src', 'data-src', 'data-lazy-src'] as $atributo) {
                $valor = trim($img->getAttribute($atributo));
                // Los <img> con carga diferida suelen traer un placeholder
                // "data:" en src: no es la imagen real.
                if ($valor !== '' && !str_starts_with($valor, 'data:')) {
                    $imagen = $this->urlAbsoluta($valor, $urlProducto);
                    break;
                }
            }

            $textoImg = $this->normalizarTexto($img->getAttribute('alt')) ?: null;
            $textoImg ??= $this->normalizarTexto($img->getAttribute('title')) ?: null;
        }

        // El banner no trae texto visible: el nombre está en el title del
        // enlace (o, en su defecto, en el alt de la imagen).
        $titulo = $this->normalizarTexto($enlace->getAttribute('title'))
            ?: ($textoImg ?? $this->normalizarTexto($enlace->textContent))
            ?: ucfirst(str_replace('-', ' ', (string) preg_replace('/-\d+$/', '', $slug)));

        // Los límites son los de las columnas de promociones_regalo. Una URL
        // recortada dejaría de funcionar, así que si no cabe se descarta.
        return new PromocionScrapeada(
            slug: mb_substr($slug, 0, 255),
            titulo: mb_substr($titulo, 0, 255),
            tipo: null,
            url: mb_strlen($urlPromo) <= 500 ? $urlPromo : $urlProducto,
            imagenUrl: ($imagen !== null && mb_strlen($imagen) <= 500) ? $imagen : null,
            fechaInicio: null,
            fechaFin: null,
        );
    }

    /**
     * ".../content/consigue-control-resonant-con-nvidia-geforce-381" →
     * "consigue-control-resonant-con-nvidia-geforce-381". El sufijo numérico
     * es el id de la página CMS de PrestaShop, así que el slug es estable
     * aunque el banner o el título cambien.
     */
    private function slugPromocion(string $urlPromo): ?string
    {
        $ruta = parse_url($urlPromo, PHP_URL_PATH);

        if (!is_string($ruta)) {
            return null;
        }

        $ultimo = basename(rtrim($ruta, '/'));

        return ($ultimo !== '' && $ultimo !== '.' && $ultimo !== '/')
            ? strtolower(urldecode($ultimo))
            : null;
    }
}
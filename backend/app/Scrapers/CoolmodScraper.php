<?php

namespace App\Scrapers;

use App\Scrapers\Contracts\ScraperTienda;
use App\Scrapers\DTO\DatoScrapeado;
use App\Scrapers\DTO\PromocionScrapeada;
use App\Scrapers\Exceptions\ScrapingException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

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
            promociones: $this->extraerPromocionesRegalo($crawler, $url),
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
            promociones: $this->extraerPromocionesRegalo($crawler, $url),
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // Regalos / promociones
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Regalos que Coolmod anuncia en la ficha de un producto.
     *
     * Estructura de la ficha (confirmada con el HTML de una RTX 5070 Ti):
     *  - Si el producto tiene regalo, junto a la galería aparece
     *      <label for="product-promo-gift">Consigue un regalo con la compra
     *      de este artículo.</label>
     *    Si no lo tiene, ese label no existe.
     *  - El detalle vive en el drawer del mismo id (<input id="product-promo-gift">):
     *    un bloque por promoción, con un <a href="/promocion/{slug}"> que
     *    envuelve el banner, otro <a> con el título y las líneas
     *    "Tipo promoción:", "Fecha inicio:" y "Fecha fin:" (DD/MM/AAAA).
     *
     * Devuelve:
     *  - []    → la ficha no anuncia regalo: comprobado, no hay.
     *  - [...] → las promociones leídas (puede haber varias).
     *  - null  → NO se pudo leer con fiabilidad (la ficha anuncia regalo
     *            pero no reconocemos su estructura, o el parser falló). Es
     *            "no sé", no "no hay": quien la consume NO debe borrar los
     *            regalos que ya tuviera guardados por un cambio de HTML.
     *
     * Nunca lanza: un fallo aquí no debe tirar el scraping del precio de
     * ese producto (que es lo importante y ya salió bien).
     *
     * @return PromocionScrapeada[]|null
     */
    public function extraerPromocionesRegalo(Crawler $crawler, string $url): ?array
    {
        try {
            return $this->leerPromocionesRegalo($crawler, $url);
        } catch (Throwable $e) {
            Log::warning("Coolmod: no se pudieron leer los regalos de {$url}: {$e->getMessage()}");

            return null;
        }
    }

    /** @return PromocionScrapeada[]|null */
    private function leerPromocionesRegalo(Crawler $crawler, string $url): ?array
    {
        $anunciaRegalo = $crawler
            ->filter('#product-promo-gift, label[for="product-promo-gift"]')
            ->count() > 0;

        if (!$anunciaRegalo) {
            return [];
        }

        // Primero se busca dentro del contenedor del drawer (el padre del
        // <input>); si ahí no aparece nada se prueba en toda la página. El
        // filtrado por bloque de promocionesEn() evita que enlaces
        // /promocion/ de menús o banners ajenos cuelen como regalos.
        foreach ($this->ambitosDePromociones($crawler) as $ambito) {
            $promociones = $this->promocionesEn($ambito, $url);

            if ($promociones !== []) {
                return $promociones;
            }
        }

        Log::warning(
            "Coolmod: {$url} anuncia regalo (#product-promo-gift) pero no se reconoció "
            .'ninguna promoción en el drawer. ¿Ha cambiado el HTML? Se conservan los regalos ya guardados.'
        );

        return null;
    }

    /** @return \DOMNode[] */
    private function ambitosDePromociones(Crawler $crawler): array
    {
        $ambitos = [];

        $input = $crawler->filter('input#product-promo-gift');
        if ($input->count() > 0 && $input->getNode(0)?->parentNode !== null) {
            $ambitos[] = $input->getNode(0)->parentNode;
        }

        $body = $crawler->filter('body');
        if ($body->count() > 0) {
            $ambitos[] = $body->getNode(0);
        }

        return $ambitos;
    }

    /** @return PromocionScrapeada[] */
    private function promocionesEn(\DOMNode $ambito, string $urlProducto): array
    {
        $xpath = new \DOMXPath($ambito->ownerDocument);
        $enlaces = $xpath->query('.//a[contains(@href, "/promocion/")]', $ambito);

        $promociones = [];
        $procesados = [];

        foreach ($enlaces ?: [] as $enlace) {
            $href = trim($enlace->getAttribute('href'));
            $slug = $this->slugPromocion($href);

            // Cada promoción trae 2 enlaces (banner y título): una vez
            // procesado el primero, el segundo es el mismo slug.
            if ($slug === null || isset($procesados[$slug])) {
                continue;
            }
            $procesados[$slug] = true;

            $bloque = $this->bloqueDePromocion($enlace, $xpath);
            if ($bloque === null) {
                continue;
            }

            $promociones[] = $this->construirPromocion($slug, $href, $bloque, $xpath, $urlProducto);
        }

        return $promociones;
    }

    /**
     * Sube desde el enlace hasta el bloque más pequeño que contiene los
     * datos de la promoción ("Tipo promoción: ..."), sin depender de cuántos
     * <div> haya de por medio. Si ese bloque contiene MÁS de una promoción
     * distinta ya no es "el de esta": el enlace no pertenece a una tarjeta
     * con datos y se descarta (así no se cuelan enlaces sueltos a
     * /promocion/ de un menú o un banner).
     */
    private function bloqueDePromocion(\DOMElement $enlace, \DOMXPath $xpath): ?\DOMElement
    {
        $nodo = $enlace->parentNode;

        while ($nodo instanceof \DOMElement) {
            if (preg_match('/Tipo\s+promoci[oó]n\s*:/iu', $this->normalizarTexto($nodo->textContent))) {
                return $this->contarPromociones($nodo, $xpath) === 1 ? $nodo : null;
            }

            $nodo = $nodo->parentNode;
        }

        return null;
    }

    private function contarPromociones(\DOMElement $nodo, \DOMXPath $xpath): int
    {
        $slugs = [];

        foreach ($xpath->query('.//a[contains(@href, "/promocion/")]', $nodo) ?: [] as $enlace) {
            $slug = $this->slugPromocion($enlace->getAttribute('href'));
            if ($slug !== null) {
                $slugs[$slug] = true;
            }
        }

        return count($slugs);
    }

    private function construirPromocion(
        string $slug,
        string $href,
        \DOMElement $bloque,
        \DOMXPath $xpath,
        string $urlProducto,
    ): PromocionScrapeada {
        $titulo = null;
        $alt = null;
        $imagen = null;

        foreach ($xpath->query('.//a[contains(@href, "/promocion/")]', $bloque) ?: [] as $enlace) {
            if ($this->slugPromocion($enlace->getAttribute('href')) !== $slug) {
                continue;
            }

            if ($titulo === null) {
                $texto = $this->normalizarTexto($enlace->textContent);
                if ($texto !== '') {
                    $titulo = $texto;
                }
            }

            $img = $xpath->query('.//img', $enlace)?->item(0);
            if ($img instanceof \DOMElement) {
                $alt ??= $this->normalizarTexto($img->getAttribute('alt')) ?: null;

                if ($imagen === null) {
                    foreach (['src', 'data-src', 'data-lazy-src'] as $atributo) {
                        $valor = trim($img->getAttribute($atributo));
                        // Los <img> con carga diferida suelen traer un
                        // placeholder "data:" en src: no es la imagen real.
                        if ($valor !== '' && !str_starts_with($valor, 'data:')) {
                            $imagen = $this->urlAbsoluta($valor, $urlProducto);
                            break;
                        }
                    }
                }
            }
        }

        $titulo ??= $alt ?? ucfirst(str_replace('-', ' ', $slug));
        // Coolmod añade su nombre a todos los títulos ("... | COOLMOD");
        // en nuestra UI la tienda ya se ve, así que sobra.
        $titulo = trim((string) preg_replace('/\s*\|\s*COOLMOD\s*$/iu', '', $titulo));

        $texto = $this->normalizarTexto($bloque->textContent);

        $tipo = null;
        if (preg_match('/Tipo\s+promoci[oó]n\s*:\s*(.+?)\s*(?=Fecha\s+(?:inicio|fin)\s*:|$)/iu', $texto, $m)) {
            $tipo = trim($m[1]) !== '' ? trim($m[1]) : null;
        }

        $fechaInicio = preg_match('/Fecha\s+inicio\s*:\s*(\d{1,2}\/\d{1,2}\/\d{4})/iu', $texto, $m)
            ? $this->parsearFecha($m[1]) : null;
        $fechaFin = preg_match('/Fecha\s+fin\s*:\s*(\d{1,2}\/\d{1,2}\/\d{4})/iu', $texto, $m)
            ? $this->parsearFecha($m[1]) : null;

        $urlPromo = $this->urlAbsoluta($href, $urlProducto) ?? $urlProducto;

        // Los límites son los de las columnas de promociones_regalo. Una URL
        // recortada dejaría de funcionar, así que si no cabe se descarta.
        return new PromocionScrapeada(
            slug: mb_substr($slug, 0, 255),
            titulo: mb_substr($titulo, 0, 255),
            tipo: $tipo !== null ? mb_substr($tipo, 0, 255) : null,
            url: mb_strlen($urlPromo) <= 500 ? $urlPromo : $urlProducto,
            imagenUrl: ($imagen !== null && mb_strlen($imagen) <= 500) ? $imagen : null,
            fechaInicio: $fechaInicio,
            fechaFin: $fechaFin,
        );
    }

    /** "/promocion/mi-promo" (o URL absoluta) → "mi-promo". Null si no es una ficha de promoción. */
    private function slugPromocion(string $href): ?string
    {
        $ruta = parse_url($href, PHP_URL_PATH);

        if (!is_string($ruta) || !preg_match('#/promocion/([^/?\#]+)/?$#i', $ruta, $m)) {
            return null;
        }

        return strtolower(urldecode($m[1]));
    }

    /** "30/09/2026" → "2026-09-30". Null si no es una fecha real. */
    private function parsearFecha(string $texto): ?string
    {
        if (!preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', trim($texto), $m)) {
            return null;
        }

        [, $dia, $mes, $anio] = array_map('intval', $m);

        return checkdate($mes, $dia, $anio) ? sprintf('%04d-%02d-%02d', $anio, $mes, $dia) : null;
    }
}
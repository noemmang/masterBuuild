<?php

namespace App\Scrapers;

use App\Scrapers\Exceptions\ScrapingException;
use App\Scrapers\Support\Transporte;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractScraper
{
    protected Client $http;

    public function __construct()
    {
        // El cliente se construye en Transporte según SCRAPER_MODO
        // (direct / proxy / api). Aquí solo desactivamos http_errors para
        // decidir nosotros qué hacer con cada código de estado.
        $this->http = Transporte::cliente(
            (array) config('scraper'),
            null,
            [RequestOptions::HTTP_ERRORS => false]
        );
    }

    /**
     * Descarga una URL y devuelve un Crawler (Symfony DomCrawler) listo
     * para aplicar selectores CSS o buscar JSON-LD.
     *
     * Clasificación de errores (de ella depende ScrapePrecios):
     *  - BLOQUEO (ScrapingException::bloqueo): 401/402/403/406/429/451, 5xx,
     *    timeouts/errores de conexión, bucles de redirección y páginas anti-bot
     *    servidas con 200. NO es culpa del producto: no suma fallos, no lo
     *    oculta y conserva su último precio.
     *  - FALLO normal (ScrapingException): 404/410 y cualquier otra cosa. Sí
     *    cuenta contra el producto.
     */
    protected function descargar(string $url): Crawler
    {
        $cfg = (array) config('scraper');

        try {
            $urlReal = Transporte::urlEfectiva($cfg, $url);
        } catch (\InvalidArgumentException $e) {
            // Error de configuración (no del producto): que no lo castigue.
            throw ScrapingException::bloqueo($e->getMessage(), 0, $e);
        }

        $intentos = 1 + max(0, (int) ($cfg['reintentos'] ?? 1));
        $respuesta = null;

        for ($intento = 1; $intento <= $intentos; $intento++) {
            try {
                $respuesta = $this->http->get($urlReal);
            } catch (TooManyRedirectsException $e) {
                // Coolmod y otras redirigen en bucle hasta que se resuelve
                // un desafío de cookie/JS que no podemos resolver.
                throw ScrapingException::bloqueo(
                    "Bucle de redirecciones al descargar {$url} (probable desafío anti-bot)",
                    0,
                    $e
                );
            } catch (ConnectException $e) {
                if ($intento < $intentos) {
                    usleep(2_000_000 * $intento);
                    continue;
                }

                throw ScrapingException::bloqueo(
                    "Sin conexión / timeout al descargar {$url}: ".$this->limpiar($e->getMessage(), $urlReal, $url),
                    0,
                    $e
                );
            } catch (GuzzleException $e) {
                throw new ScrapingException(
                    "Error de red al descargar {$url}: ".$this->limpiar($e->getMessage(), $urlReal, $url),
                    previous: $e
                );
            }

            // 5xx: error del servidor (o de la API de scraping). Se reintenta.
            if ($respuesta->getStatusCode() >= 500 && $intento < $intentos) {
                usleep(2_000_000 * $intento);
                continue;
            }

            break;
        }

        $codigo = $respuesta->getStatusCode();
        $cuerpo = (string) $respuesta->getBody();

        if (in_array($codigo, [401, 402, 403, 406, 429, 451], true) || $codigo >= 500) {
            throw ScrapingException::bloqueo(
                "Bloqueado/rechazado por la tienda (HTTP {$codigo}) al descargar {$url}".$this->pistasDeBloqueo($respuesta),
                $codigo
            );
        }

        if ($codigo !== 200) {
            throw new ScrapingException("Respuesta HTTP {$codigo} al descargar {$url}");
        }

        // 200 OK pero con una página anti-bot en lugar de la ficha.
        $cabeceras = array_change_key_case(
            array_map(static fn (array $v) => implode(', ', $v), $respuesta->getHeaders()),
            CASE_LOWER
        );
        $desafio = Transporte::detectarDesafio($cuerpo, $cabeceras);

        if ($desafio !== null) {
            Log::warning('Scraping: desafío anti-bot servido con HTTP 200', [
                'url'     => $url,
                'sistema' => $desafio,
                'cuerpo'  => $this->normalizarTexto(substr($cuerpo, 0, 600)),
            ]);

            throw ScrapingException::bloqueo(
                "Página anti-bot ({$desafio}) en lugar de la ficha de {$url}",
                200
            );
        }

        return new Crawler($cuerpo, $url);
    }

    /**
     * Los mensajes de Guzzle incluyen la URL pedida; en modo "api" esa URL
     * lleva la API key. Se sustituye por la del producto y se tapan
     * parámetros típicos de credenciales antes de guardar/loguear nada.
     */
    protected function limpiar(string $mensaje, string $urlReal, string $url): string
    {
        $mensaje = str_replace($urlReal, $url, $mensaje);

        return (string) preg_replace('/((?:api[_-]?key|token|key)=)[^&\s"\']+/i', '$1***', $mensaje);
    }

    /**
     * Pistas cortas para saber QUIÉN bloquea (cabecera Server, Cloudflare,
     * Retry-After...) sin volcar el HTML de la página de bloqueo en el log
     * del Job. El principio del cuerpo se guarda aparte en el log de
     * Laravel por si hace falta identificar el WAF.
     */
    private function pistasDeBloqueo(\Psr\Http\Message\ResponseInterface $respuesta): string
    {
        $pistas = [];

        $servidor = $respuesta->getHeaderLine('Server');
        if ($servidor !== '') {
            $pistas[] = "server: {$servidor}";
        }
        if ($respuesta->hasHeader('CF-Ray')) {
            $pistas[] = 'cloudflare';
        }
        $reintento = $respuesta->getHeaderLine('Retry-After');
        if ($reintento !== '') {
            $pistas[] = "retry-after: {$reintento}";
        }

        try {
            $cuerpo = $this->normalizarTexto(substr((string) $respuesta->getBody(), 0, 600));
            Log::warning('Scraping bloqueado por la tienda', [
                'http'  => $respuesta->getStatusCode(),
                'pistas' => $pistas,
                'cuerpo' => $cuerpo,
            ]);
        } catch (\Throwable) {
            // el log de diagnóstico nunca debe romper el scraping
        }

        return $pistas === [] ? '' : ' ['.implode('; ', $pistas).']';
    }

    /**
     * Convierte una ruta o URL leída del HTML en una URL absoluta usando el
     * host de la ficha del producto. Devuelve null si no se puede resolver.
     * Sirve para enlaces (/promocion/x, /content/x) y para imágenes
     * (/modules/.../banner.jpg, //cdn.tienda.com/x.jpg).
     */
    protected function urlAbsoluta(string $ruta, string $urlProducto): ?string
    {
        $ruta = trim($ruta);

        if ($ruta === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $ruta)) {
            return $ruta;
        }

        if (str_starts_with($ruta, '//')) {
            return 'https:'.$ruta;
        }

        $partes = parse_url($urlProducto);
        if (empty($partes['scheme']) || empty($partes['host']) || !str_starts_with($ruta, '/')) {
            return null;
        }

        return $partes['scheme'].'://'.$partes['host'].$ruta;
    }

    /** Colapsa espacios (incluido el &nbsp;) y recorta. */
    protected function normalizarTexto(string $texto): string
    {
        // \xC2\xA0 = espacio duro (&nbsp;), muy habitual en HTML de tiendas.
        $texto = str_replace("\xC2\xA0", ' ', $texto);

        return trim((string) preg_replace('/\s+/u', ' ', $texto));
    }

    /**
     * La mayoría de tiendas online incrustan un bloque
     * <script type="application/ld+json"> con datos schema.org/Product
     * (nombre, precio, disponibilidad...) pensado para Google Shopping.
     * Es la fuente MÁS ESTABLE para scraping: no depende de clases CSS
     * que cambian con cada rediseño. Solo si no existe recurrimos a
     * selectores CSS como fallback.
     *
     * OJO: algunas tiendas (p. ej. PcComponentes) sirven "@type":"product"
     * en minúscula en vez de "Product". La spec de schema.org exige
     * PascalCase, pero en la práctica hay que comparar sin distinguir
     * mayúsculas/minúsculas para no perder estos casos.
     */
    protected function extraerJsonLdProducto(Crawler $crawler): ?array
    {
        $nodos = $crawler->filter('script[type="application/ld+json"]');

        foreach ($nodos as $nodo) {
            $data = json_decode($nodo->textContent, true);
            if (!is_array($data)) {
                continue;
            }

            $candidatos = $data['@graph'] ?? (array_is_list($data) ? $data : [$data]);

            foreach ($candidatos as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $tipo = $item['@type'] ?? null;
                $esProducto = $this->tipoEsProducto($tipo);

                if ($esProducto && isset($item['offers'])) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * El nodo "offers" de schema.org/Product puede venir en varias formas:
     *  - Un único Offer con "price" directamente.
     *  - Una lista de Offers (p. ej. varios vendedores).
     *  - Un AggregateOffer (varios vendedores/precios resumidos) que a su
     *    vez puede traer un Offer "ganador" anidado en su propia clave
     *    "offers" (como hace PcComponentes). En ese caso hay que bajar un
     *    nivel más para llegar al precio real.
     *
     * Vive aquí (y no en un scraper concreto) porque no tiene nada
     * específico de una tienda: cualquier scraper que use JSON-LD
     * schema.org/Product se va a encontrar los mismos 4 casos.
     */
    protected function resolverOffer(mixed $offers): ?array
    {
        if (!is_array($offers)) {
            return null;
        }

        // Lista de offers: nos quedamos con el primero.
        if (array_is_list($offers)) {
            return $offers[0] ?? null;
        }

        // Ya es un Offer con precio directo.
        if (isset($offers['price'])) {
            return $offers;
        }

        // AggregateOffer con un Offer anidado en su propia clave "offers".
        if (isset($offers['offers'])) {
            return $this->resolverOffer($offers['offers']);
        }

        // AggregateOffer sin Offer anidado: usamos lowPrice como mejor precio disponible.
        if (isset($offers['lowPrice'])) {
            return [
                'price' => $offers['lowPrice'],
                'priceCurrency' => $offers['priceCurrency'] ?? null,
                'availability' => $offers['availability'] ?? null,
            ];
        }

        return null;
    }

    /**
     * Comprueba si un valor @type de JSON-LD representa un Product,
     * sin distinguir mayúsculas/minúsculas (algunas tiendas no siguen
     * estrictamente el PascalCase de schema.org).
     */
    private function tipoEsProducto(mixed $tipo): bool
    {
        if (is_string($tipo)) {
            return strcasecmp($tipo, 'Product') === 0;
        }

        if (is_array($tipo)) {
            foreach ($tipo as $valor) {
                if (is_string($valor) && strcasecmp($valor, 'Product') === 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
<?php

namespace App\Scrapers;

use App\Scrapers\Exceptions\ScrapingException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractScraper
{
    protected Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'timeout' => 15,
            // Algunas tiendas (p. ej. Coolmod) exigen una cookie de
            // "verificación" antes de servir la página real. Sin cookies
            // persistentes, cada redirect vuelve a pedir la verificación
            // y se entra en bucle infinito -> "Will not follow more than
            // 5 redirects". Con esto, Guzzle guarda y reenvía las cookies
            // que el servidor va fijando entre redirects.
            'cookies' => true,
            'headers' => [
                // Cabeceras de un navegador real. Muchas tiendas devuelven
                // una versión distinta (o bloquean) si detectan un
                // User-Agent de bot/librería.
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                    .'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'es-ES,es;q=0.9',
            ],
        ]);
    }

    /**
     * Descarga una URL y devuelve un Crawler (Symfony DomCrawler) listo
     * para aplicar selectores CSS o buscar JSON-LD.
     */
    protected function descargar(string $url): Crawler
    {
        try {
            $response = $this->http->get($url);
        } catch (GuzzleException $e) {
            throw new ScrapingException(
                "Error de red al descargar {$url}: {$e->getMessage()}",
                previous: $e
            );
        }

        $codigo = $response->getStatusCode();
        if ($codigo !== 200) {
            throw new ScrapingException("Respuesta HTTP {$codigo} al descargar {$url}");
        }

        return new Crawler((string) $response->getBody(), $url);
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
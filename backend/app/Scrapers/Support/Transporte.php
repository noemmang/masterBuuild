<?php

namespace App\Scrapers\Support;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

/**
 * Capa de transporte HTTP del scraping: construye el cliente Guzzle según
 * SCRAPER_MODO (direct / proxy / api) y detecta páginas de "desafío" anti-bot.
 *
 * Es PHP puro (sin facades de Laravel) para poder usarse igual desde los
 * scrapers, desde el comando de diagnóstico y desde tests.
 */
final class Transporte
{
    public const MODO_DIRECTO = 'direct';
    public const MODO_PROXY   = 'proxy';
    public const MODO_API     = 'api';

    /**
     * @param  array<string,mixed>  $cfg  contenido de config('scraper')
     * @param  array<string,mixed>  $extra  opciones Guzzle que pisan las de por defecto
     */
    public static function cliente(array $cfg, ?string $modo = null, array $extra = []): Client
    {
        $modo ??= (string) ($cfg['modo'] ?? self::MODO_DIRECTO);
        $major = (int) ($cfg['chrome_major'] ?? 156);

        $opciones = [
            RequestOptions::TIMEOUT         => (int) ($cfg['timeout'] ?? 25),
            RequestOptions::CONNECT_TIMEOUT => (int) ($cfg['connect_timeout'] ?? 10),
            // Coolmod y otras exigen una cookie de verificación entre
            // redirects: sin cookie jar se entra en bucle de redirecciones.
            RequestOptions::COOKIES         => true,
            RequestOptions::HEADERS         => self::cabecerasNavegador((string) ($cfg['user_agent'] ?? ''), $major),
        ];

        // Los navegadores hablan HTTP/2. libcurl negocia h2 y, si el servidor
        // o la build no lo soportan, cae solo a HTTP/1.1.
        if (defined('CURL_HTTP_VERSION_2_0')) {
            $opciones[RequestOptions::VERSION] = '2.0';
        }

        if ($modo === self::MODO_PROXY && !empty($cfg['proxy'])) {
            $opciones[RequestOptions::PROXY] = $cfg['proxy'];
        }

        return new Client(array_replace($opciones, $extra));
    }

    /**
     * Cabeceras coherentes con un Chrome real de escritorio. Un User-Agent de
     * Chrome SIN las cabeceras sec-ch-ua / sec-fetch-* es una de las
     * incoherencias que más puntúan como bot.
     *
     * @return array<string,string>
     */
    public static function cabecerasNavegador(string $userAgent, int $chromeMajor): array
    {
        if ($userAgent === '') {
            $userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/{$chromeMajor}.0.0.0 Safari/537.36";
        }

        return [
            'User-Agent'                => $userAgent,
            'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language'           => 'es-ES,es;q=0.9,en;q=0.8',
            'Cache-Control'             => 'max-age=0',
            'Upgrade-Insecure-Requests' => '1',
            'sec-ch-ua'                 => "\"Chromium\";v=\"{$chromeMajor}\", \"Google Chrome\";v=\"{$chromeMajor}\", \"Not.A/Brand\";v=\"99\"",
            'sec-ch-ua-mobile'          => '?0',
            'sec-ch-ua-platform'        => '"Windows"',
            'Sec-Fetch-Dest'            => 'document',
            'Sec-Fetch-Mode'            => 'navigate',
            'Sec-Fetch-Site'            => 'none',
            'Sec-Fetch-User'            => '?1',
        ];
    }

    /**
     * URL que realmente se pide: la del producto, o la de la API de scraping
     * con la URL del producto incrustada (modo "api").
     */
    public static function urlEfectiva(array $cfg, string $url, ?string $modo = null): string
    {
        $modo ??= (string) ($cfg['modo'] ?? self::MODO_DIRECTO);

        if ($modo !== self::MODO_API) {
            return $url;
        }

        $plantilla = (string) ($cfg['api_url_template'] ?? '');

        if ($plantilla === '' || !str_contains($plantilla, '{url}')) {
            throw new \InvalidArgumentException(
                'SCRAPER_MODO=api requiere SCRAPER_API_URL_TEMPLATE con el marcador {url}.'
            );
        }

        return str_replace('{url}', rawurlencode($url), $plantilla);
    }

    /**
     * Si el cuerpo es una página anti-bot en vez de la ficha real, devuelve
     * el nombre del sistema que la ha servido; si no, null.
     *
     * Importante: muchos WAF contestan 200 OK con la página de desafío. Sin
     * esta comprobación el scraper lo trataba como "ficha sin precio", sumaba
     * fallos al producto y, a los 5 días, lo OCULTABA del front como
     * no_disponible por algo que no era culpa del producto.
     *
     * @param  array<string,string>  $cabeceras  nombres en minúsculas
     */
    public static function detectarDesafio(string $cuerpo, array $cabeceras = []): ?string
    {
        if (isset($cabeceras['cf-mitigated'])) {
            return 'Cloudflare (cf-mitigated: '.$cabeceras['cf-mitigated'].')';
        }

        // Una ficha real de producto pesa decenas de KB; las páginas de
        // desafío son diminutas. Así evitamos falsos positivos por una
        // palabra suelta dentro de una ficha legítima.
        if (strlen($cuerpo) > 40_000) {
            return null;
        }

        $firmas = [
            'Cloudflare'            => '/just a moment\.\.\.|cf-browser-verification|challenge-platform|cf_chl_opt|attention required! \| cloudflare|checking your browser before accessing/i',
            'DataDome'              => '/datadome|dd_captcha|geo\.captcha-delivery\.com/i',
            'PerimeterX / HUMAN'    => '/px-captcha|_pxhd|perimeterx|press & hold/i',
            'Imperva / Incapsula'   => '/_incapsula_resource|incapsula incident|pardon our interruption/i',
            'Akamai'                => '/akamai.{0,40}reference #|access denied.{0,120}edgesuite/is',
            'Sucuri'                => '/sucuri website firewall|cloudproxy@sucuri/i',
            'Verificación genérica' => '/verifica(?:ndo)? (?:que )?(?:eres|tu navegador)|comprobando (?:tu )?navegador|verify you are (?:a )?human|enable javascript and cookies to continue|g-recaptcha|h-captcha/i',
        ];

        foreach ($firmas as $sistema => $patron) {
            if (preg_match($patron, $cuerpo) === 1) {
                return $sistema;
            }
        }

        return null;
    }
}

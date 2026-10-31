<?php

namespace App\Console\Commands;

use App\Models\Negocio\Tienda;
use App\Models\Negocio\UrlProductoTienda;
use App\Scrapers\Contracts\ScraperTienda;
use App\Scrapers\Support\Transporte;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Illuminate\Console\Command;
use Throwable;

/**
 * Diagnóstico del scraping DESDE el entorno donde se ejecuta (p. ej. dentro
 * del contenedor de Azure). Sirve para responder con datos a "¿me han
 * bloqueado la IP de Azure?":
 *
 *   az containerapp exec -n masterbuild-backend -g infrastructure-rg \
 *       --command "php artisan scrape:diagnostico --comparar"
 *
 * Muestra la IP de salida, el código HTTP de una ficha de cada tienda, las
 * cabeceras que delatan al WAF (Server, CF-Ray, cf-mitigated), si la página
 * es un desafío anti-bot y si el scraper consigue extraer el precio.
 */
class ScrapeDiagnostico extends Command
{
    protected $signature = 'scrape:diagnostico
        {tienda? : Nombre exacto de la tienda (por defecto, todas las activas)}
        {--url= : Probar esta URL concreta en lugar de una ficha de la BD}
        {--modo= : Forzar el modo de transporte: direct | proxy | api (por defecto, el de SCRAPER_MODO)}
        {--comparar : Probar también en modo directo para comparar con el configurado}';

    protected $description = 'Prueba una ficha de cada tienda y explica por qué el scraping falla (IP bloqueada, desafío anti-bot, selectores...)';

    public function handle(): int
    {
        $configurado = (string) ($this->option('modo') ?: config('scraper.modo', 'direct'));
        $modos = $this->option('comparar')
            ? array_values(array_unique([Transporte::MODO_DIRECTO, $configurado]))
            : [$configurado];

        $this->info('== Configuración ==');
        $this->line('  Modo configurado : '.config('scraper.modo', 'direct'));
        $this->line('  Proxy            : '.($this->enmascarar((string) config('scraper.proxy')) ?: '(ninguno)'));
        $this->line('  API de scraping  : '.(config('scraper.api_url_template') ? '(plantilla definida)' : '(ninguna)'));
        $this->line('  User-Agent       : '.config('scraper.user_agent'));

        $objetivos = $this->objetivos();

        if ($objetivos === []) {
            $this->warn('No hay ninguna URL que probar (¿tiendas sin URLs activas?). Usa --url=...');

            return self::FAILURE;
        }

        $resumen = [];

        foreach ($modos as $modo) {
            $this->newLine();
            $this->info("== Modo: {$modo} ==");

            // Los scrapers leen config('scraper.modo') al construirse.
            config(['scraper.modo' => $modo]);

            $this->mostrarIpDeSalida($modo);

            foreach ($objetivos as $objetivo) {
                $resumen[$modo][$objetivo['tienda']] = $this->probar($modo, $objetivo);
            }
        }

        $this->newLine();
        $this->info('== Diagnóstico ==');
        $this->line($this->veredicto($resumen));

        return self::SUCCESS;
    }

    /** @return array<int, array{tienda:string, url:string, clase:?string}> */
    private function objetivos(): array
    {
        if ($url = $this->option('url')) {
            $tienda = Tienda::activa()->get()->first(
                fn (Tienda $t) => $t->url && str_contains((string) $url, (string) parse_url($t->url, PHP_URL_HOST))
            );

            return [['tienda' => $tienda?->nombre ?? 'URL manual', 'url' => (string) $url, 'clase' => $tienda?->clase_scraper]];
        }

        $query = Tienda::activa()->whereNotNull('clase_scraper');
        if ($filtro = $this->argument('tienda')) {
            $query->where('nombre', $filtro);
        }

        $objetivos = [];
        foreach ($query->get() as $tienda) {
            $registro = UrlProductoTienda::activo()->where('tienda_id', $tienda->id)->orderBy('id')->first();
            if ($registro && trim((string) $registro->url) !== '') {
                $objetivos[] = ['tienda' => $tienda->nombre, 'url' => $registro->url, 'clase' => $tienda->clase_scraper];
            }
        }

        return $objetivos;
    }

    private function mostrarIpDeSalida(string $modo): void
    {
        if ($modo === Transporte::MODO_API) {
            $this->line('  IP de salida     : (la decide la API de scraping)');

            return;
        }

        try {
            $cliente = Transporte::cliente((array) config('scraper'), $modo, [
                RequestOptions::HTTP_ERRORS => false,
                RequestOptions::TIMEOUT => 15,
            ]);
            $ip = trim((string) $cliente->get('https://api.ipify.org')->getBody());
            $this->line("  IP de salida     : {$ip}");
        } catch (Throwable $e) {
            $this->warn('  IP de salida     : no se pudo averiguar ('.mb_substr($e->getMessage(), 0, 120).')');
        }
    }

    /** @return array{estado:string, http:?int, desafio:?string, precio:bool} */
    private function probar(string $modo, array $objetivo): array
    {
        $this->newLine();
        $this->line("▶ [{$objetivo['tienda']}] {$objetivo['url']}");

        $res = ['estado' => 'desconocido', 'http' => null, 'desafio' => null, 'precio' => false];

        try {
            $cfg = (array) config('scraper');
            $cliente = Transporte::cliente($cfg, $modo, [RequestOptions::HTTP_ERRORS => false]);
            $t0 = microtime(true);
            $respuesta = $cliente->get(Transporte::urlEfectiva($cfg, $objetivo['url'], $modo));
            $ms = (int) ((microtime(true) - $t0) * 1000);

            $cuerpo = (string) $respuesta->getBody();
            $cabeceras = array_change_key_case(
                array_map(static fn (array $v) => implode(', ', $v), $respuesta->getHeaders()),
                CASE_LOWER
            );

            $res['http'] = $respuesta->getStatusCode();
            $res['desafio'] = Transporte::detectarDesafio($cuerpo, $cabeceras);
            $tieneJsonLd = preg_match('/"@type"\s*:\s*"product"/i', $cuerpo) === 1;

            $this->line("  HTTP {$res['http']} · {$ms} ms · ".number_format(strlen($cuerpo)).' bytes');
            $this->line('  Server: '.($cabeceras['server'] ?? '-')
                .' · CF-Ray: '.($cabeceras['cf-ray'] ?? '-')
                .' · cf-mitigated: '.($cabeceras['cf-mitigated'] ?? '-'));
            $this->line('  Página anti-bot: '.($res['desafio'] ?? 'no detectada').' · JSON-LD Product: '.($tieneJsonLd ? 'sí' : 'no'));

            if ($res['http'] !== 200 || $res['desafio'] !== null || !$tieneJsonLd) {
                $this->line('  Inicio del cuerpo: '.mb_substr((string) preg_replace('/\s+/u', ' ', strip_tags($cuerpo)), 0, 200));
            }
        } catch (GuzzleException|\InvalidArgumentException $e) {
            $res['estado'] = 'red';
            $this->error('  ✗ Error de red: '.mb_substr($e->getMessage(), 0, 200));
        }

        // Prueba completa: lo que haría ScrapePrecios con esta URL.
        if ($objetivo['clase'] && class_exists($objetivo['clase'])) {
            try {
                /** @var ScraperTienda $scraper */
                $scraper = app($objetivo['clase']);
                $dato = $scraper->extraerDatos($objetivo['url']);
                $res['precio'] = true;
                $res['estado'] = 'ok';
                $this->info("  ✓ Scraper OK: {$dato->precio} {$dato->moneda}".($dato->enStock ? '' : ' (agotado)'));
            } catch (Throwable $e) {
                $res['estado'] = $res['estado'] === 'red' ? 'red' : 'fallo';
                $this->error('  ✗ Scraper falla: '.mb_substr($e->getMessage(), 0, 250));
            }
        }

        return $res;
    }

    /** @param array<string, array<string, array{estado:string, http:?int, desafio:?string, precio:bool}>> $resumen */
    private function veredicto(array $resumen): string
    {
        $lineas = [];
        $hayBloqueo = false;
        $hayRed = false;
        $todoOk = true;

        foreach ($resumen as $modo => $porTienda) {
            foreach ($porTienda as $tienda => $r) {
                if ($r['precio']) {
                    $lineas[] = "  ✓ {$tienda} ({$modo}): funciona.";
                    continue;
                }

                $todoOk = false;

                if ($r['estado'] === 'red') {
                    $hayRed = true;
                    $lineas[] = "  ✗ {$tienda} ({$modo}): no hay conexión / timeout → la IP de salida parece cortada.";
                } elseif (in_array($r['http'], [401, 403, 406, 429, 451], true) || $r['desafio'] !== null) {
                    $hayBloqueo = true;
                    $lineas[] = "  ✗ {$tienda} ({$modo}): BLOQUEO (HTTP {$r['http']}".($r['desafio'] ? ", {$r['desafio']}" : '').').';
                } else {
                    $lineas[] = "  ✗ {$tienda} ({$modo}): llega la página pero no se extrae el precio → probablemente cambió el HTML (revisa selectores).";
                }
            }
        }

        if ($todoOk) {
            $lineas[] = '  Todo correcto desde este entorno: si los precios no se actualizan, el problema no es de red/IP (revisa timeout del Job, logs y última ejecución).';
        } elseif ($hayBloqueo || $hayRed) {
            $lineas[] = '';
            $lineas[] = '  → Si en --comparar el modo directo falla pero proxy/api funciona, la causa es la IP de datacenter.';
            $lineas[] = '    Define SCRAPER_MODO=proxy (SCRAPER_PROXY) o SCRAPER_MODO=api (SCRAPER_API_URL_TEMPLATE). Ver SCRAPING_AZURE.md.';
        }

        return implode("\n", $lineas);
    }

    private function enmascarar(string $proxy): string
    {
        return (string) preg_replace('#(//[^:@/]+:)[^@]+@#', '$1***@', $proxy);
    }
}

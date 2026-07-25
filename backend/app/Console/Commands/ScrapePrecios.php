<?php

namespace App\Console\Commands;

use App\Models\Negocio\EntradaPrecio;
use App\Models\Negocio\UrlProductoTienda;
use App\Scrapers\Contracts\ScraperTienda;
use App\Scrapers\Exceptions\ScrapingException;
use Illuminate\Console\Command;
use Throwable;

class ScrapePrecios extends Command
{
    protected $signature = 'scrape:precios
        {tienda? : Nombre exacto de la tienda a scrapear (por defecto, todas las activas)}
        {--pausa-min=2 : Segundos mínimos de espera entre peticiones}
        {--pausa-max=5 : Segundos máximos de espera entre peticiones}
        {--umbral-fallos=5 : Fallos consecutivos a partir de los cuales se marca la URL como no_disponible}';

    protected $description = 'Descarga precios reales desde las tiendas configuradas y crea nuevas entradas_precio';

    public function handle(): int
    {
        $tiendaFiltro = $this->argument('tienda');
        $umbralFallos = (int) $this->option('umbral-fallos');

        // Sin ->disponible() a propósito: seguimos reintentando las URLs
        // marcadas no_disponible para que, si la tienda arregla el enlace,
        // el producto vuelva a aparecer solo, sin tocar nada a mano.
        $query = UrlProductoTienda::query()->activo()->with(['tienda', 'componente']);

        if ($tiendaFiltro) {
            $query->whereHas('tienda', fn ($q) => $q->where('nombre', $tiendaFiltro));
        }

        $urls = $query->get();

        if ($urls->isEmpty()) {
            $this->warn('No hay URLs de producto configuradas en urls_producto_tienda.');
            $this->line('Añade alguna con: php artisan tinker, o un seeder/comando de alta.');
            return self::SUCCESS;
        }

        $this->info("Scrapeando {$urls->count()} productos...");

        $ok = 0;
        $fallos = 0;
        $marcadosNoDisponible = 0;
        $pausaMin = (int) $this->option('pausa-min');
        $pausaMax = (int) $this->option('pausa-max');

        foreach ($urls as $registro) {
            $tienda = $registro->tienda;
            $nombre = $registro->componente->nombre ?? $registro->componente_id;

            if (!$tienda || !$tienda->activo || !$tienda->clase_scraper) {
                continue;
            }

            // Defensa extra: aunque el seeder ya evita crear filas con url
            // vacía, si alguna vez se cuela una (alta manual, importación,
            // futuro panel de admin...) no tiene sentido ni gastar una
            // petición HTTP con ella ni contarla como "fallo" normal.
            if (trim((string) $registro->url) === '') {
                $this->warn("  · [{$tienda->nombre}] {$nombre}: url vacía, se ignora");
                continue;
            }

            if (!class_exists($tienda->clase_scraper)) {
                $this->error("  ✗ La clase {$tienda->clase_scraper} no existe todavía para {$tienda->nombre}");
                $fallos++;
                continue;
            }

            try {
                /** @var ScraperTienda $scraper */
                $scraper = app($tienda->clase_scraper);
                $dato = $scraper->extraerDatos($registro->url);

                EntradaPrecio::create([
                    'componente_id' => $registro->componente_id,
                    'tienda_id' => $tienda->id,
                    'precio' => $dato->precio,
                    'moneda' => $dato->moneda,
                    'url' => $dato->url,
                    'en_stock' => $dato->enStock,
                    'scraped_at' => now(),
                ]);

                // Scrape correcto: la URL vuelve a considerarse "viva",
                // aunque llevara arrastrando fallos anteriores.
                $registro->update([
                    'ultimo_scrape_at' => now(),
                    'fallos_consecutivos' => 0,
                    'no_disponible' => false,
                    'ultimo_error' => null,
                ]);

                $estado = $dato->enStock ? '' : ' (agotado)';
                $this->line("  ✓ [{$tienda->nombre}] {$nombre}: {$dato->precio} {$dato->moneda}{$estado}");
                $ok++;
            } catch (ScrapingException|Throwable $e) {
                // Capturamos también Throwable (no solo ScrapingException):
                // un fallo inesperado de un scraper (bug, cambio de HTML
                // no controlado, etc.) no debe tirar abajo el resto de la
                // ejecución ni dejar de contarse como fallo de esa URL.
                $fallosPrevios = $registro->fallos_consecutivos ?? 0;
                $fallosNuevos = $fallosPrevios + 1;
                $pasaAoNoDisponible = $fallosNuevos >= $umbralFallos && !$registro->no_disponible;

                $registro->update([
                    'ultimo_scrape_at' => now(),
                    'fallos_consecutivos' => $fallosNuevos,
                    'no_disponible' => $fallosNuevos >= $umbralFallos,
                    'ultimo_error' => substr($e->getMessage(), 0, 255),
                ]);

                $marcador = $pasaAoNoDisponible ? ' → marcada no_disponible, se oculta en el front' : '';
                $this->error("  ✗ [{$tienda->nombre}] {$nombre}: {$e->getMessage()} (fallo {$fallosNuevos}/{$umbralFallos}){$marcador}");
                $fallos++;
                if ($pasaAoNoDisponible) {
                    $marcadosNoDisponible++;
                }
            }

            // Pausa "cortés" entre peticiones para no saturar la tienda
            // ni parecer un ataque. Ajusta con --pausa-min/--pausa-max.
            if ($registro !== $urls->last()) {
                usleep(random_int($pausaMin * 1_000_000, $pausaMax * 1_000_000));
            }
        }

        $this->newLine();
        $this->info("Hecho. OK: {$ok}, fallos: {$fallos}, nuevas no_disponible: {$marcadosNoDisponible}");

        return self::SUCCESS;
    }
}
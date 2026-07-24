<?php

namespace App\Console\Commands;

use App\Models\Negocio\EntradaPrecio;
use App\Models\Negocio\UrlProductoTienda;
use App\Scrapers\Contracts\ScraperTienda;
use App\Scrapers\Exceptions\ScrapingException;
use Illuminate\Console\Command;

class ScrapePrecios extends Command
{
    protected $signature = 'scrape:precios
        {tienda? : Nombre exacto de la tienda a scrapear (por defecto, todas las activas)}
        {--pausa-min=2 : Segundos mínimos de espera entre peticiones}
        {--pausa-max=5 : Segundos máximos de espera entre peticiones}';

    protected $description = 'Descarga precios reales desde las tiendas configuradas y crea nuevas entradas_precio';

    public function handle(): int
    {
        $tiendaFiltro = $this->argument('tienda');

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
        $pausaMin = (int) $this->option('pausa-min');
        $pausaMax = (int) $this->option('pausa-max');

        foreach ($urls as $registro) {
            $tienda = $registro->tienda;

            if (!$tienda || !$tienda->activo || !$tienda->clase_scraper) {
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

                $registro->update(['ultimo_scrape_at' => now()]);

                $nombre = $registro->componente->nombre ?? $registro->componente_id;
                $this->line("  ✓ [{$tienda->nombre}] {$nombre}: {$dato->precio} {$dato->moneda}");
                $ok++;
            } catch (ScrapingException $e) {
                $nombre = $registro->componente->nombre ?? $registro->componente_id;
                $this->error("  ✗ [{$tienda->nombre}] {$nombre}: {$e->getMessage()}");
                $fallos++;
            }

            // Pausa "cortés" entre peticiones para no saturar la tienda
            // ni parecer un ataque. Ajusta con --pausa-min/--pausa-max.
            if ($registro !== $urls->last()) {
                usleep(random_int($pausaMin * 1_000_000, $pausaMax * 1_000_000));
            }
        }

        $this->newLine();
        $this->info("Hecho. OK: {$ok}, fallos: {$fallos}");

        return self::SUCCESS;
    }
}

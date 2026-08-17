<?php

namespace App\Console\Commands;

use App\Models\Negocio\HistorialPrecio;
use App\Models\Negocio\PrecioActual;
use App\Models\Negocio\UrlProductoTienda;
use App\Scrapers\Contracts\ScraperTienda;
use App\Scrapers\DTO\DatoScrapeado;
use App\Scrapers\Exceptions\ScrapingException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ScrapePrecios extends Command
{
    protected $signature = 'scrape:precios
        {tienda? : Nombre exacto de la tienda a scrapear (por defecto, todas las activas)}
        {--pausa-min=2 : Segundos mínimos de espera entre peticiones}
        {--pausa-max=5 : Segundos máximos de espera entre peticiones}
        {--umbral-fallos=5 : Fallos consecutivos a partir de los cuales se marca la URL como no_disponible}';

    protected $description = 'Descarga precios reales desde las tiendas configuradas y actualiza precios_actuales / historial_precios';

    public function handle(): int
    {
        $tiendaFiltro = $this->argument('tienda');
        $umbralFallos = (int) $this->option('umbral-fallos');

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
        $sinCambios = 0;
        $conCambios = 0;
        $pausaMin = (int) $this->option('pausa-min');
        $pausaMax = (int) $this->option('pausa-max');

        foreach ($urls as $registro) {
            $tienda = $registro->tienda;
            $nombre = $registro->componente->nombre ?? $registro->componente_id;

            if (!$tienda || !$tienda->activo || !$tienda->clase_scraper) {
                continue;
            }

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

                $huboCambio = $this->registrarPrecio($registro->componente_id, $tienda->id, $dato);
                $huboCambio ? $conCambios++ : $sinCambios++;

                $registro->update([
                    'ultimo_scrape_at' => now(),
                    'fallos_consecutivos' => 0,
                    'no_disponible' => false,
                    'ultimo_error' => null,
                ]);

                $estado = $dato->enStock ? '' : ' (agotado)';
                $marcadorCambio = $huboCambio ? '' : ' (sin cambios)';
                $this->line("  ✓ [{$tienda->nombre}] {$nombre}: {$dato->precio} {$dato->moneda}{$estado}{$marcadorCambio}");
                $ok++;
            } catch (ScrapingException|Throwable $e) {
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

            if ($registro !== $urls->last()) {
                usleep(random_int($pausaMin * 1_000_000, $pausaMax * 1_000_000));
            }
        }

        $this->newLine();
        $this->info("Hecho. OK: {$ok} ({$sinCambios} sin cambios, {$conCambios} con cambio de precio/stock), fallos: {$fallos}, nuevas no_disponible: {$marcadosNoDisponible}");

        return self::SUCCESS;
    }

    /**
     * Actualiza precios_actuales para (componenteId, tiendaId) con el dato
     * recién scrapeado.
     *
     * Si el precio y el stock son iguales a los de la última vez, solo
     * "toca" la fila existente (updated_at = ahora): no crece ninguna
     * tabla. Si cambian, cierra el tramo anterior en historial_precios
     * (con valid_to = la última fecha en que se CONFIRMÓ ese precio, no
     * "ayer" a ciegas — así, si el scraping llevaba días fallando, el
     * histórico no finge continuidad que no existe) y abre uno nuevo en
     * precios_actuales.
     *
     * Devuelve true si hubo un cambio real de precio/stock, false si solo
     * se reconfirmó el precio que ya había.
     */
    private function registrarPrecio(int $componenteId, int $tiendaId, DatoScrapeado $dato): bool
    {
        return DB::transaction(function () use ($componenteId, $tiendaId, $dato) {
            $actual = PrecioActual::where('componente_id', $componenteId)
                ->where('tienda_id', $tiendaId)
                ->lockForUpdate()
                ->first();

            if (!$actual) {
                PrecioActual::create([
                    'componente_id' => $componenteId,
                    'tienda_id'     => $tiendaId,
                    'precio'        => $dato->precio,
                    'moneda'        => $dato->moneda,
                    'url'           => $dato->url,
                    'en_stock'      => $dato->enStock,
                    'vigente_desde' => now(),
                ]);

                return true;
            }

            $precioActualFmt = number_format((float) $actual->precio, 2, '.', '');
            $precioNuevoFmt  = number_format($dato->precio, 2, '.', '');
            $mismoPrecio = $precioActualFmt === $precioNuevoFmt && $actual->en_stock === $dato->enStock;

            if ($mismoPrecio) {
                if ($dato->url !== $actual->url) {
                    $actual->update(['url' => $dato->url]); // ya actualiza updated_at
                } else {
                    $actual->touch(); // nada cambia; solo constancia de que se confirmó hoy
                }

                return false;
            }

            HistorialPrecio::create([
                'componente_id' => $actual->componente_id,
                'tienda_id'     => $actual->tienda_id,
                'precio'        => $actual->precio,
                'moneda'        => $actual->moneda,
                'en_stock'      => $actual->en_stock,
                'valid_from'    => $actual->vigente_desde->toDateString(),
                'valid_to'      => $actual->updated_at->toDateString(),
            ]);

            $actual->update([
                'precio'        => $dato->precio,
                'moneda'        => $dato->moneda,
                'url'           => $dato->url,
                'en_stock'      => $dato->enStock,
                'vigente_desde' => now(),
            ]);

            return true;
        });
    }
}
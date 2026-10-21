<?php

namespace App\Console\Commands;

use App\Models\Negocio\HistorialPrecio;
use App\Models\Negocio\PrecioActual;
use App\Models\Negocio\UrlProductoTienda;
use App\Scrapers\Contracts\ScraperTienda;
use App\Scrapers\DTO\DatoScrapeado;
use App\Scrapers\Exceptions\ScrapingException;
use App\Services\Promociones\PromocionRegaloService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ScrapePrecios extends Command
{
    protected $signature = 'scrape:precios
        {tienda? : Nombre exacto de la tienda a scrapear (por defecto, todas las activas)}
        {--pausa-min=2 : Segundos mínimos de espera entre peticiones}
        {--pausa-max=5 : Segundos máximos de espera entre peticiones}
        {--umbral-fallos=5 : Fallos consecutivos a partir de los cuales se marca la URL como no_disponible}
        {--bloqueos-max=5 : Bloqueos (403/429) seguidos de una tienda tras los cuales se abandona esa tienda en esta ejecución}';

    protected $description = 'Descarga precios reales desde las tiendas configuradas y actualiza precios_actuales / historial_precios (y los regalos activos de cada ficha)';

    /**
     * Si una tienda falla en más de esta proporción de sus productos
     * (con al menos MIN_INTENTOS_RATIO intentos), el comando termina con
     * FAILURE para que el Job de Azure aparezca como fallido en vez de
     * "correcto". Un fallo de 1-2 productos sueltos no dispara esto.
     */
    private const RATIO_FALLOS_CRITICO = 0.5;
    private const MIN_INTENTOS_RATIO = 5;

    public function handle(): int
    {
        $tiendaFiltro = $this->argument('tienda');
        $umbralFallos = (int) $this->option('umbral-fallos');
        $bloqueosMax = max(1, (int) $this->option('bloqueos-max'));

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

        $regalos = app(PromocionRegaloService::class);
        $regalosNuevos = 0;
        $regalosRetirados = 0;
        $ok = 0;
        $fallos = 0;
        $marcadosNoDisponible = 0;
        $sinCambios = 0;
        $conCambios = 0;
        $omitidos = 0;
        $pausaMin = (int) $this->option('pausa-min');
        $pausaMax = (int) $this->option('pausa-max');

        // Estadísticas por tienda, para detectar fallos sistémicos (bloqueos).
        /** @var array<string, array{intentos:int, ok:int, fallos:int, bloqueos:int, omitidos:int}> $stats */
        $stats = [];
        /** @var array<int, int> $bloqueosSeguidos bloqueos consecutivos por id de tienda */
        $bloqueosSeguidos = [];
        /** @var array<int, string> $tiendasAbandonadas tiendas cortadas en esta ejecución, por id */
        $tiendasAbandonadas = [];

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

            $stats[$tienda->nombre] ??= ['intentos' => 0, 'ok' => 0, 'fallos' => 0, 'bloqueos' => 0, 'omitidos' => 0];

            // Tienda cortada por bloqueos seguidos: no insistimos (seguir
            // golpeando solo empeora la reputación de la IP y alarga el Job).
            // El producto conserva su último precio y NO se marca no_disponible.
            if (isset($tiendasAbandonadas[$tienda->id])) {
                $stats[$tienda->nombre]['omitidos']++;
                $omitidos++;
                continue;
            }

            if (!class_exists($tienda->clase_scraper)) {
                $this->error("  ✗ La clase {$tienda->clase_scraper} no existe todavía para {$tienda->nombre}");
                $fallos++;
                continue;
            }

            $stats[$tienda->nombre]['intentos']++;

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

                // Regalos: se revisan en CADA scrape (el diario), no solo cuando
                // cambia el precio. Un fallo aquí no cuenta como fallo de
                // scraping: el precio ya se guardó bien.
                $marcadorRegalos = '';
                if ($dato->promociones !== null) {
                    try {
                        $r = $regalos->sincronizar($registro->componente_id, $tienda->id, $dato->promociones);
                        $regalosNuevos += $r['nuevas'] + $r['reactivadas'];
                        $regalosRetirados += $r['desactivadas'];

                        if ($r['vistas'] > 0) {
                            $marcadorRegalos .= " · {$r['vistas']} regalo(s)";
                        }
                        if ($r['desactivadas'] > 0) {
                            $marcadorRegalos .= " · {$r['desactivadas']} regalo(s) retirado(s)";
                        }
                    } catch (Throwable $e) {
                        $this->warn("  ! [{$tienda->nombre}] {$nombre}: no se pudieron sincronizar los regalos: {$e->getMessage()}");
                    }
                }

                $estado = $dato->enStock ? '' : ' (agotado)';
                $marcadorCambio = $huboCambio ? '' : ' (sin cambios)';
                $this->line("  ✓ [{$tienda->nombre}] {$nombre}: {$dato->precio} {$dato->moneda}{$estado}{$marcadorCambio}{$marcadorRegalos}");
                $ok++;
                $stats[$tienda->nombre]['ok']++;
                $bloqueosSeguidos[$tienda->id] = 0;
            } catch (ScrapingException|Throwable $e) {
                $stats[$tienda->nombre]['fallos']++;
                $fallos++;

                if ($e instanceof ScrapingException && $e->esBloqueo()) {
                    // BLOQUEO (403/429): la tienda nos rechaza, no es culpa del
                    // producto. No se suma a fallos_consecutivos, no se marca
                    // no_disponible y no se retiran sus regalos: el producto
                    // conserva el último precio conocido. Solo se anota el
                    // motivo para poder verlo en la tabla.
                    $registro->update([
                        'ultimo_error' => substr('[BLOQUEO] '.$e->getMessage(), 0, 255),
                    ]);

                    $stats[$tienda->nombre]['bloqueos']++;
                    $bloqueosSeguidos[$tienda->id] = ($bloqueosSeguidos[$tienda->id] ?? 0) + 1;

                    $this->error("  ✗ [{$tienda->nombre}] {$nombre}: {$e->getMessage()} (bloqueo {$bloqueosSeguidos[$tienda->id]}/{$bloqueosMax}, no cuenta como fallo del producto)");

                    if ($bloqueosSeguidos[$tienda->id] >= $bloqueosMax) {
                        $tiendasAbandonadas[$tienda->id] = $tienda->nombre;
                        $this->error("  ‼ [{$tienda->nombre}] {$bloqueosMax} bloqueos seguidos: se abandona esta tienda en esta ejecución. Sus productos conservan el último precio.");
                    }
                } else {
                    $fallosPrevios = $registro->fallos_consecutivos ?? 0;
                    $fallosNuevos = $fallosPrevios + 1;
                    $pasaAoNoDisponible = $fallosNuevos >= $umbralFallos && !$registro->no_disponible;

                    $registro->update([
                        'ultimo_scrape_at' => now(),
                        'fallos_consecutivos' => $fallosNuevos,
                        'no_disponible' => $fallosNuevos >= $umbralFallos,
                        'ultimo_error' => substr($e->getMessage(), 0, 255),
                    ]);

                    // Si la ficha ya se considera no disponible no podemos
                    // confirmar que su regalo siga ahí: se retira del front.
                    // (Si el scraping se recupera, el siguiente sincronizar() lo
                    // reactiva.) Va en su propio try para no romper este catch.
                    if ($fallosNuevos >= $umbralFallos) {
                        try {
                            $regalosRetirados += $regalos->desactivarTodas($registro->componente_id, $tienda->id);
                        } catch (Throwable) {
                            // no crítico: se reintentará en el próximo scrape fallido
                        }
                    }

                    $marcador = $pasaAoNoDisponible ? ' → marcada no_disponible, se oculta en el front' : '';
                    $this->error("  ✗ [{$tienda->nombre}] {$nombre}: {$e->getMessage()} (fallo {$fallosNuevos}/{$umbralFallos}){$marcador}");
                    if ($pasaAoNoDisponible) {
                        $marcadosNoDisponible++;
                    }
                }
            }

            if ($registro !== $urls->last()) {
                usleep(random_int($pausaMin * 1_000_000, $pausaMax * 1_000_000));
            }
        }

        $this->newLine();
        $this->info("Hecho. OK: {$ok} ({$sinCambios} sin cambios, {$conCambios} con cambio de precio/stock), fallos: {$fallos}, omitidos por bloqueo: {$omitidos}, nuevas no_disponible: {$marcadosNoDisponible}");
        $this->info("Regalos: {$regalosNuevos} nuevos/reactivados, {$regalosRetirados} retirados.");

        // ── Resumen por tienda y código de salida ────────────────────────
        // Antes este comando devolvía SIEMPRE SUCCESS: aunque una tienda
        // fallase en el 100% de sus productos, Azure marcaba el Job como
        // correcto y nadie se enteraba. Ahora un problema sistémico en
        // cualquier tienda hace que el comando termine con FAILURE.
        $this->newLine();
        $this->info('Resumen por tienda:');

        $problemas = [];

        foreach ($stats as $nombreTienda => $s) {
            $this->line(sprintf(
                '  · %s: %d intentos, %d OK, %d fallos (%d bloqueos), %d omitidos',
                $nombreTienda, $s['intentos'], $s['ok'], $s['fallos'], $s['bloqueos'], $s['omitidos']
            ));

            if ($s['bloqueos'] > 0 && ($s['omitidos'] > 0 || $s['ok'] === 0)) {
                $problemas[] = "{$nombreTienda}: bloqueada ({$s['bloqueos']} bloqueos, {$s['ok']} OK, {$s['omitidos']} omitidos)";
            } elseif (
                $s['intentos'] >= self::MIN_INTENTOS_RATIO
                && ($s['fallos'] / $s['intentos']) > self::RATIO_FALLOS_CRITICO
            ) {
                $problemas[] = sprintf('%s: %d de %d productos fallaron', $nombreTienda, $s['fallos'], $s['intentos']);
            }
        }

        if ($problemas !== []) {
            $this->newLine();
            foreach ($problemas as $problema) {
                $this->error("‼ PROBLEMA SISTÉMICO → {$problema}");
            }

            return self::FAILURE;
        }

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
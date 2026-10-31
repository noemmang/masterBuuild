<?php

namespace App\Console\Commands;

use App\Models\Negocio\PrecioActual;
use App\Models\Negocio\Tienda;
use App\Models\Negocio\UrlProductoTienda;
use App\Services\Scraping\AlertaScraping;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Vigilante de datos obsoletos. Responde a una sola pregunta: "¿los precios
 * de cada tienda se han confirmado recientemente?". Es independiente de que
 * el scraping diga haber ido bien o mal, así que atrapa casos en los que no
 * hay ningún error visible: tienda bloqueada que conserva su último precio,
 * Job que se corta a medias, etc.
 */
class ScrapeVigilar extends Command
{
    protected $signature = 'scrape:vigilar
        {--horas= : Horas máximas sin datos frescos (por defecto SCRAPER_HORAS_OBSOLETO, 30)}
        {--ratio=0.2 : Proporción máxima de URLs obsoletas por tienda antes de avisar}';

    protected $description = 'Avisa si los precios de alguna tienda llevan demasiado tiempo sin actualizarse';

    public function handle(): int
    {
        $horas = max(1, (int) ($this->option('horas') ?: config('scraper.horas_obsoleto', 30)));
        $ratioMax = (float) $this->option('ratio');
        $limite = Carbon::now()->subHours($horas);

        $problemas = [];

        $tiendas = Tienda::activa()->whereNotNull('clase_scraper')->orderBy('nombre')->get();

        foreach ($tiendas as $tienda) {
            $urls = UrlProductoTienda::activo()->where('tienda_id', $tienda->id);
            $total = (clone $urls)->count();

            if ($total === 0) {
                continue;
            }

            $ultimo = PrecioActual::where('tienda_id', $tienda->id)->max('updated_at');
            $ultimo = $ultimo ? Carbon::parse($ultimo) : null;

            $obsoletas = (clone $urls)
                ->where(fn ($q) => $q->whereNull('ultimo_scrape_at')->orWhere('ultimo_scrape_at', '<', $limite))
                ->count();

            $this->line(sprintf(
                '  · %s: último precio confirmado %s · %d/%d URLs sin scrape en %d h',
                $tienda->nombre,
                $ultimo ? $ultimo->diffForHumans() : 'NUNCA',
                $obsoletas,
                $total,
                $horas
            ));

            if ($ultimo === null || $ultimo->lt($limite)) {
                $problemas[] = "{$tienda->nombre}: ningún precio confirmado desde ".($ultimo ? $ultimo->format('Y-m-d H:i').' ('.$ultimo->diffForHumans().')' : 'siempre')
                    .". Los precios mostrados están desactualizados.";
            } elseif (($obsoletas / $total) > $ratioMax) {
                $problemas[] = sprintf(
                    '%s: %d de %d productos (%d%%) sin actualizar en las últimas %d h.',
                    $tienda->nombre, $obsoletas, $total, (int) round($obsoletas / $total * 100), $horas
                );
            }
        }

        if ($problemas === []) {
            $this->info('Datos al día en todas las tiendas.');

            return self::SUCCESS;
        }

        foreach ($problemas as $p) {
            $this->error("‼ {$p}");
        }

        app(AlertaScraping::class)->alertar('Precios desactualizados', $problemas);

        return self::FAILURE;
    }
}

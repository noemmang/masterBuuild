<?php

namespace App\Console\Commands;

use App\Services\Componentes\RelevanciaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Recalcula metricas_relevancia a partir de interacciones_componente (ver
 * RelevanciaService::recalcular) e invalida la caché de las tres secciones
 * dinámicas del home (destacados/bajadas de precio/promociones, ver
 * ComponenteController), para que la siguiente visita las regenere con
 * datos frescos. Encadenado al final de scrape:diario — así "relevancia" y
 * "el home se actualiza a diario" comparten el mismo disparador que ya
 * existe (el Job programado nocturno) en vez de necesitar un cron propio.
 */
class RelevanciaRecalcular extends Command
{
    protected $signature = 'relevancia:recalcular
        {--dias= : Ventana de días de interacciones a tener en cuenta (por defecto, la del servicio)}';

    protected $description = 'Recalcula la puntuación de relevancia de cada componente (búsquedas + selecciones) y refresca la caché del home';

    public function handle(RelevanciaService $relevancia): int
    {
        $dias = $this->option('dias') !== null ? (int) $this->option('dias') : null;

        $total = $relevancia->recalcular($dias);

        // Estas tres claves deben coincidir EXACTAMENTE con las que usan
        // ComponenteController::destacados()/bajadasPrecio()/promociones()
        // en sus respectivos Cache::remember().
        Cache::forget('componentes:destacados');
        Cache::forget('componentes:bajadas_precio');
        Cache::forget('componentes:promociones');

        $this->info("Relevancia recalculada para {$total} componentes. Caché del home invalidada.");

        return self::SUCCESS;
    }
}

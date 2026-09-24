<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Único comando que llama el Job masterbuild-scraping-diario (cron
 * 0 2 * * *). El campo "Comando"/"Argumentos" del Job solo acepta un
 * valor, no una cadena de comandos — así que en vez de intentar meter
 * "scrape:precios && notificaciones:verificar" ahí, este comando hace
 * ese encadenado por dentro con Artisan::call(). El Job se queda
 * configurado con un único comando simple, como ya estaba.
 *
 * notificaciones:verificar SIEMPRE se ejecuta después de scrape:precios,
 * nunca antes: la pregunta "¿algo cambió?" solo tiene sentido una vez
 * que los precios de esta noche ya están guardados en precios_actuales.
 *
 * relevancia:recalcular va el último: además de recalcular
 * metricas_relevancia, invalida la caché de "Componentes destacados",
 * "Bajadas de precio" y "Promociones" del home (ver HomeCache), que
 * también dependen de lo que scrape:precios acaba de guardar (precios,
 * stock, promociones). Así todo el home queda al día una vez por noche
 * con el mismo disparador, sin necesitar un segundo Job programado.
 */
class ScrapeDiario extends Command
{
    protected $signature = 'scrape:diario
        {tienda? : Nombre exacto de la tienda a scrapear (por defecto, todas las activas)}
        {--pausa-min=2 : Segundos mínimos de espera entre peticiones}
        {--pausa-max=5 : Segundos máximos de espera entre peticiones}
        {--umbral-fallos=5 : Fallos consecutivos a partir de los cuales se marca la URL como no_disponible}';

    protected $description = 'Ejecuta scrape:precios, notificaciones:verificar y relevancia:recalcular en cadena. Es el único comando que debe configurarse en el Job programado.';

    public function handle(): int
    {
        $this->info('== 1/3 · scrape:precios ==');

        $codigoScrape = Artisan::call('scrape:precios', [
            'tienda'           => $this->argument('tienda'),
            '--pausa-min'      => $this->option('pausa-min'),
            '--pausa-max'      => $this->option('pausa-max'),
            '--umbral-fallos'  => $this->option('umbral-fallos'),
        ], $this->output);

        $this->newLine();
        $this->info('== 2/3 · notificaciones:verificar ==');

        // Se ejecuta siempre, incluso si scrape:precios ha tenido fallos:
        // los precios que SÍ se guardaron esta noche merecen comprobarse
        // igualmente, y no queremos que un fallo puntual de una tienda
        // silencie las notificaciones del resto.
        $codigoNotificaciones = Artisan::call('notificaciones:verificar', [], $this->output);

        $this->newLine();
        $this->info('== 3/3 · relevancia:recalcular ==');

        // Mismo motivo: se ejecuta pase lo que pase con los dos pasos
        // anteriores, para que el home refleje los datos que sí se
        // guardaron esta noche en vez de quedarse con la caché de ayer.
        $codigoRelevancia = Artisan::call('relevancia:recalcular', [], $this->output);

        $codigos = [$codigoScrape, $codigoNotificaciones, $codigoRelevancia];

        return in_array(self::FAILURE, $codigos, true) ? self::FAILURE : self::SUCCESS;
    }
}
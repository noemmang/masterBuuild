<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Comando de UN SOLO USO para comprimir el histórico acumulado en
 * entradas_precio (una fila por scrape, se repita o no el precio) en el
 * nuevo esquema: precios_actuales (estado actual) + historial_precios
 * (tramos cerrados, uno por cada cambio real de precio/stock).
 *
 * No borra ni modifica entradas_precio en ningún momento. Es seguro
 * relanzarlo: si un (componente, tienda) ya tiene tramos en
 * historial_precios, no lo vuelve a tocar; usa --fresco si quieres
 * vaciar precios_actuales/historial_precios y migrar desde cero.
 *
 * Uso:
 *   php artisan precios:migrar-historial --dry-run   (para ver qué haría)
 *   php artisan precios:migrar-historial
 */
class MigrarHistorialPrecios extends Command
{
    protected $signature = 'precios:migrar-historial
        {--dry-run : No escribe nada, solo muestra cuántos tramos generaría}
        {--fresco : Vacía precios_actuales y historial_precios antes de migrar}';

    protected $description = 'Comprime entradas_precio en precios_actuales + historial_precios (migración de un solo uso)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (!$dryRun && $this->option('fresco')) {
            DB::table('historial_precios')->truncate();
            DB::table('precios_actuales')->truncate();
            $this->warn('precios_actuales y historial_precios vaciadas (--fresco).');
        }

        $pares = DB::table('entradas_precio')
            ->select('componente_id', 'tienda_id')
            ->distinct()
            ->orderBy('componente_id')
            ->orderBy('tienda_id')
            ->get();

        if ($pares->isEmpty()) {
            $this->warn('No hay filas en entradas_precio, nada que migrar.');
            return self::SUCCESS;
        }

        $this->info("Comprimiendo {$pares->count()} pares (componente, tienda)...");
        $bar = $this->output->createProgressBar($pares->count());
        $bar->start();

        $tramosCreados = 0;
        $paresActualizados = 0;
        $paresOmitidos = 0;

        foreach ($pares as $par) {
            $yaMigrado = DB::table('historial_precios')
                ->where('componente_id', $par->componente_id)
                ->where('tienda_id', $par->tienda_id)
                ->exists();

            if ($yaMigrado) {
                $paresOmitidos++;
                $bar->advance();
                continue;
            }

            $filas = DB::table('entradas_precio')
                ->where('componente_id', $par->componente_id)
                ->where('tienda_id', $par->tienda_id)
                ->orderBy('scraped_at')
                ->get(['precio', 'moneda', 'en_stock', 'url', 'scraped_at']);

            if ($filas->isEmpty()) {
                $bar->advance();
                continue;
            }

            // ── Comprimir filas consecutivas con el mismo precio/stock ──────
            $tramos = [];
            $tramoActual = null;

            foreach ($filas as $fila) {
                $precioFmt = number_format((float) $fila->precio, 2, '.', '');
                $fecha = Carbon::parse($fila->scraped_at);

                if ($tramoActual
                    && $tramoActual['precio'] === $precioFmt
                    && $tramoActual['en_stock'] === (bool) $fila->en_stock) {
                    $tramoActual['valid_to'] = $fecha->toDateString();
                    $tramoActual['url'] = $fila->url ?: $tramoActual['url'];
                    continue;
                }

                if ($tramoActual) {
                    $tramos[] = $tramoActual;
                }

                $tramoActual = [
                    'precio'     => $precioFmt,
                    'moneda'     => $fila->moneda,
                    'en_stock'   => (bool) $fila->en_stock,
                    'url'        => $fila->url,
                    'valid_from' => $fecha->toDateString(),
                    'valid_to'   => $fecha->toDateString(),
                ];
            }

            // El último tramo es el precio ACTUAL (vigente), no un tramo
            // cerrado del histórico.
            $actual = $tramoActual;

            if ($dryRun) {
                $tramosCreados += count($tramos);
                $paresActualizados++;
                $bar->advance();
                continue;
            }

            DB::transaction(function () use ($par, $tramos, $actual, &$tramosCreados) {
                if (!empty($tramos)) {
                    $ahora = now();
                    DB::table('historial_precios')->insert(array_map(fn ($t) => [
                        'componente_id' => $par->componente_id,
                        'tienda_id'     => $par->tienda_id,
                        'precio'        => $t['precio'],
                        'moneda'        => $t['moneda'],
                        'en_stock'      => $t['en_stock'],
                        'valid_from'    => $t['valid_from'],
                        'valid_to'      => $t['valid_to'],
                        'created_at'    => $ahora,
                        'updated_at'    => $ahora,
                    ], $tramos));
                    $tramosCreados += count($tramos);
                }

                if ($actual) {
                    $existente = DB::table('precios_actuales')
                        ->where('componente_id', $par->componente_id)
                        ->where('tienda_id', $par->tienda_id)
                        ->first();

                    // updated_at = último día CONFIRMADO a ese precio (el
                    // valid_to del tramo actual), no la fecha de hoy: es lo
                    // que ScrapePrecios usará como valid_to si el precio
                    // vuelve a cambiar, así que tiene que reflejar el
                    // último dato real, no el momento en que corres esta
                    // migración.
                    DB::table('precios_actuales')->updateOrInsert(
                        ['componente_id' => $par->componente_id, 'tienda_id' => $par->tienda_id],
                        [
                            'uuid'          => $existente->uuid ?? (string) Str::orderedUuid(),
                            'precio'        => $actual['precio'],
                            'moneda'        => $actual['moneda'],
                            'en_stock'      => $actual['en_stock'],
                            'url'           => $actual['url'],
                            'vigente_desde' => $actual['valid_from'],
                            'created_at'    => $existente->created_at ?? $actual['valid_from'],
                            'updated_at'    => $actual['valid_to'],
                        ]
                    );
                }
            });

            $paresActualizados++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info("[dry-run] Se crearían ~{$tramosCreados} tramos en historial_precios para {$paresActualizados} pares ({$paresOmitidos} ya migrados, se omitirían).");
        } else {
            $this->info("Hecho. {$tramosCreados} tramos creados en historial_precios, {$paresActualizados} pares procesados ({$paresOmitidos} ya estaban migrados).");
            $this->line('entradas_precio no se ha tocado. Verifica los datos y, cuando estés tranquilo, puedes archivarla o eliminarla tú mismo.');
        }

        return self::SUCCESS;
    }
}
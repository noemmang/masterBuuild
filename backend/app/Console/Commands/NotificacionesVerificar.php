<?php

namespace App\Console\Commands;

use App\Models\Componentes\Componente;
use App\Models\Negocio\AlertaPrecio;
use App\Models\Negocio\ComponenteGuardado;
use App\Models\Negocio\PrecioActual;
use App\Notifications\AlertaPrecioAlcanzadaNotification;
use App\Notifications\ComponenteAgotadoNotification;
use App\Notifications\ComponenteDisponibleNotification;
use Illuminate\Console\Command;

/**
 * Se ejecuta justo después de scrape:precios (ver scrape:diario, que
 * encadena los dos) y compara el estado que acaba de guardar el scraping
 * contra lo que ya sabíamos, para decidir si hay que avisar a algún
 * usuario por correo. No vuelve a tocar la red ni a scrapear nada: solo
 * lee lo que ya está en precios_actuales.
 *
 * Dos comprobaciones independientes, cada una con su propia forma de no
 * repetir el mismo correo cada noche:
 *
 *  - Guardados: usa notificado_agotado_en como bandera de "ya avisé de
 *    que esto se agotó". Se pone al avisar de que se agotó, se quita al
 *    avisar de que volvió — así ambos eventos pueden repetirse en el
 *    futuro si el componente entra y sale de stock varias veces.
 *
 *  - Alertas: usa disparada_en igual que ya hacía el resto de la app. A
 *    diferencia de los guardados, aquí NO hay un correo por "el precio
 *    volvió a subir"; simplemente se resetea disparada_en en silencio
 *    para que la alerta vuelva a poder saltar si el precio baja otra vez.
 *    Esto fue una decisión de producto explícita: el usuario solo deja
 *    de vigilar un precio si borra la alerta a mano.
 *
 * Guardados y alertas eliminados por el usuario no se vuelven a
 * comprobar: GuardadoController::destroy() y AlertaController::destroy()
 * ya hacen forceDelete() (no delete() blando), así que la fila
 * desaparece de verdad de la tabla y este comando, que solo recorre lo
 * que sigue existiendo en componentes_guardados/alertas_precio, ni
 * siquiera llega a verla. No hace falta ningún chequeo extra aquí para
 * respetar "si lo borra, ya no le interesa".
 */
class NotificacionesVerificar extends Command
{
    protected $signature = 'notificaciones:verificar';

    protected $description = 'Revisa guardados y alertas de precio contra el último scrape y envía los correos que correspondan';

    public function handle(): int
    {
        $guardadosNotificados = $this->verificarGuardados();
        $alertasNotificadas   = $this->verificarAlertas();

        $this->info("Hecho. Guardados notificados: {$guardadosNotificados}. Alertas notificadas: {$alertasNotificadas}.");

        return self::SUCCESS;
    }

    private function verificarGuardados(): int
    {
        $notificados = 0;

        ComponenteGuardado::with(['usuario', 'componente'])
            ->chunkById(100, function ($guardados) use (&$notificados) {
                foreach ($guardados as $guardado) {
                    if (!$guardado->usuario || !$guardado->componente) {
                        continue; // por si el usuario o el componente se borraron a mano
                    }

                    // Mismo criterio que ya usa el resto de la app para decidir
                    // si un componente se sigue mostrando en el front.
                    $disponibleAhora = Componente::disponible()
                        ->whereKey($guardado->componente_id)
                        ->exists();

                    $yaMarcadoAgotado = $guardado->notificadoComoAgotado();

                    if (!$disponibleAhora && !$yaMarcadoAgotado) {
                        $guardado->usuario->notify(new ComponenteAgotadoNotification($guardado));
                        $guardado->update(['notificado_agotado_en' => now()]);
                        $this->line("  · [agotado] {$guardado->usuario->email} — {$guardado->componente->nombre}");
                        $notificados++;
                        continue;
                    }

                    if ($disponibleAhora && $yaMarcadoAgotado) {
                        $mejorOferta = PrecioActual::mejorPrecio($guardado->componente_id)
                            ->with('tienda')
                            ->first();

                        if (!$mejorOferta) {
                            continue; // no debería pasar si $disponibleAhora es true, pero por si acaso
                        }

                        $guardado->usuario->notify(new ComponenteDisponibleNotification($guardado, $mejorOferta));
                        $guardado->update(['notificado_agotado_en' => null]);
                        $this->line("  · [disponible de nuevo] {$guardado->usuario->email} — {$guardado->componente->nombre}");
                        $notificados++;
                    }
                }
            });

        return $notificados;
    }

    private function verificarAlertas(): int
    {
        $notificadas = 0;

        AlertaPrecio::with(['usuario', 'componente'])
            ->where('activa', true)
            ->chunkById(100, function ($alertas) use (&$notificadas) {
                foreach ($alertas as $alerta) {
                    if (!$alerta->usuario || !$alerta->componente) {
                        continue;
                    }

                    $mejorPrecio = PrecioActual::mejorPrecio($alerta->componente_id)
                        ->with('tienda')
                        ->first();

                    if (!$mejorPrecio) {
                        continue; // nadie lo tiene en stock ahora mismo, nada que comparar
                    }

                    $objetivoAlcanzado = (float) $mejorPrecio->precio <= (float) $alerta->precio_objetivo;

                    if ($objetivoAlcanzado && !$alerta->estaDisparada()) {
                        $alerta->usuario->notify(new AlertaPrecioAlcanzadaNotification($alerta, $mejorPrecio));
                        $alerta->update(['disparada_en' => now()]);
                        $this->line("  · [alerta alcanzada] {$alerta->usuario->email} — {$alerta->componente->nombre} a {$mejorPrecio->precio} {$mejorPrecio->moneda}");
                        $notificadas++;
                        continue;
                    }

                    if (!$objetivoAlcanzado && $alerta->estaDisparada()) {
                        // El precio volvió a subir por encima del objetivo: se
                        // reactiva en silencio (sin correo) para poder volver a
                        // saltar si baja de nuevo en el futuro.
                        $alerta->update(['disparada_en' => null]);
                    }
                }
            });

        return $notificadas;
    }
}
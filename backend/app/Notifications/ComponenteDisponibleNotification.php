<?php

namespace App\Notifications;

use App\Models\Negocio\ComponenteGuardado;
use App\Models\Negocio\PrecioActual;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Se dispara desde NotificacionesVerificar cuando un ComponenteGuardado
 * que estaba marcado como agotado (notificado_agotado_en no nula) vuelve
 * a cumplir Componente::scopeDisponible(). Es la contraparte de
 * ComponenteAgotadoNotification: el comando resetea notificado_agotado_en
 * a null justo después de enviar esta, así que si vuelve a agotarse más
 * adelante se puede avisar de nuevo.
 *
 * Sin ShouldQueue, mismo motivo que en ComponenteAgotadoNotification: el
 * Job de Azure no tiene ningún queue:work corriendo detrás.
 */
class ComponenteDisponibleNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ComponenteGuardado $guardado,
        public PrecioActual $mejorOferta,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $componente = $this->guardado->componente;

        return (new MailMessage)
            ->subject("Ya está disponible de nuevo: {$componente->nombre}")
            ->view('emails.componente-disponible', [
                'componente' => $componente,
                'oferta'     => $this->mejorOferta,
                'url'        => $this->urlProducto($componente),
            ]);
    }

    private function urlProducto($componente): string
    {
        return rtrim(config('app.frontend_url'), '/')
            . '/buscar?uuid=' . $componente->uuid
            . '&categoria=' . $componente->categoria;
    }
}
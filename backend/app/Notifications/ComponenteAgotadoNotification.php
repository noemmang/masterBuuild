<?php

namespace App\Notifications;

use App\Models\Negocio\ComponenteGuardado;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Se dispara desde NotificacionesVerificar cuando un ComponenteGuardado
 * deja de cumplir Componente::scopeDisponible() (ninguna tienda lo tiene
 * ya en stock, o directamente ha desaparecido del catálogo). Se envía
 * como mucho una vez por "racha" de no disponibilidad: el comando marca
 * notificado_agotado_en al enviarla y no la repite hasta que
 * ComponenteDisponibleNotification confirme que volvió y resetee esa
 * columna a null.
 */
class ComponenteAgotadoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ComponenteGuardado $guardado)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $componente = $this->guardado->componente;

        return (new MailMessage)
            ->subject("Se ha agotado: {$componente->nombre}")
            ->view('emails.componente-agotado', [
                'componente' => $componente,
                'url'        => rtrim(config('app.frontend_url'), '/') . '/guardados',
            ]);
    }
}
<?php

namespace App\Notifications;

use App\Models\Negocio\ComponenteGuardado;
use Illuminate\Bus\Queueable;
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
 *
 * A propósito NO implementa ShouldQueue: el Job de Azure que ejecuta
 * scrape:diario corre el comando y se apaga, sin ningún queue:work
 * detrás para procesar la tabla jobs. Se envía en el momento, dentro del
 * mismo proceso — a este volumen (unos pocos correos por noche) no pasa
 * nada por que scrape:diario tarde un poco más en terminar.
 *
 * También va por 'database': queda guardada para mostrarse en la
 * campanita de notificaciones del header (ver NotificacionController).
 */
class ComponenteAgotadoNotification extends Notification
{
    use Queueable;

    public function __construct(public ComponenteGuardado $guardado)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
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

    public function toDatabase(object $notifiable): array
    {
        $componente = $this->guardado->componente;

        return [
            'tipo'    => 'componente_agotado',
            'titulo'  => 'Ya no está disponible',
            'mensaje' => "\"{$componente->nombre}\" se ha agotado o ha desaparecido de las tiendas que seguimos.",
            'url'     => '/guardados',
            'imagen'  => $componente->imagen_url,
        ];
    }
}
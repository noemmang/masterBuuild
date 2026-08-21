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
 *
 * También va por 'database': queda guardada para mostrarse en la
 * campanita de notificaciones del header (ver NotificacionController).
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
        return ['mail', 'database'];
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

    public function toDatabase(object $notifiable): array
    {
        $componente = $this->guardado->componente;
        $tienda     = $this->mejorOferta->tienda->nombre ?? null;
        $precio     = number_format((float) $this->mejorOferta->precio, 2, ',', '.');

        $mensaje = "\"{$componente->nombre}\" ha vuelto a tener stock";
        $mensaje .= $tienda ? " en {$tienda}" : '';
        $mensaje .= " por {$precio} €.";

        return [
            'tipo'    => 'componente_disponible',
            'titulo'  => 'Ya está disponible de nuevo',
            'mensaje' => $mensaje,
            'url'     => $this->rutaProducto($componente),
            'imagen'  => $componente->imagen_url,
        ];
    }

    private function urlProducto($componente): string
    {
        return rtrim(config('app.frontend_url'), '/') . $this->rutaProducto($componente);
    }

    // Ruta relativa dentro del frontend Angular (a diferencia de
    // urlProducto(), que arma la URL absoluta para el correo).
    private function rutaProducto($componente): string
    {
        return '/buscar?uuid=' . $componente->uuid . '&categoria=' . $componente->categoria;
    }
}
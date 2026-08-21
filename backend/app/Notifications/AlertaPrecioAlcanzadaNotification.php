<?php

namespace App\Notifications;

use App\Models\Negocio\AlertaPrecio;
use App\Models\Negocio\PrecioActual;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Se dispara desde NotificacionesVerificar cuando el mejor precio en
 * stock de AlertaPrecio::componente_id baja hasta el precio_objetivo (o
 * menos) y la alerta todavía no estaba disparada. El comando marca
 * disparada_en al enviarla; si el precio vuelve a subir por encima del
 * objetivo, el mismo comando resetea disparada_en a null para que la
 * alerta pueda volver a saltar sola en el futuro sin que el usuario
 * tenga que hacer nada — solo deja de vigilarse si borra la alerta.
 *
 * Sin ShouldQueue, mismo motivo que en las otras dos notificaciones: el
 * Job de Azure no tiene ningún queue:work corriendo detrás.
 *
 * También va por 'database': queda guardada para mostrarse en la
 * campanita de notificaciones del header (ver NotificacionController).
 */
class AlertaPrecioAlcanzadaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public AlertaPrecio $alerta,
        public PrecioActual $mejorPrecio,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $componente = $this->alerta->componente;

        return (new MailMessage)
            ->subject("¡Bajó de precio! {$componente->nombre}")
            ->view('emails.alerta-precio', [
                'alerta'      => $this->alerta,
                'componente'  => $componente,
                'mejorPrecio' => $this->mejorPrecio,
                'url'         => $this->urlProducto($componente),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        $componente = $this->alerta->componente;
        $precio     = number_format((float) $this->mejorPrecio->precio, 2, ',', '.');
        $objetivo   = number_format((float) $this->alerta->precio_objetivo, 2, ',', '.');

        return [
            'tipo'    => 'alerta_precio',
            'titulo'  => '¡Tu alerta de precio saltó!',
            'mensaje' => "\"{$componente->nombre}\" ha bajado a {$precio} € (tu objetivo era {$objetivo} €).",
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
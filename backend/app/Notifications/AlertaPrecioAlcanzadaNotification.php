<?php

namespace App\Notifications;

use App\Models\Negocio\AlertaPrecio;
use App\Models\Negocio\PrecioActual;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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
 */
class AlertaPrecioAlcanzadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AlertaPrecio $alerta,
        public PrecioActual $mejorPrecio,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
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

    private function urlProducto($componente): string
    {
        return rtrim(config('app.frontend_url'), '/')
            . '/buscar?uuid=' . $componente->uuid
            . '&categoria=' . $componente->categoria;
    }
}
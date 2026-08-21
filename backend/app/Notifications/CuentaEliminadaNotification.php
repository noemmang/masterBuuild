<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Se dispara desde AuthController::destroyMe(), ANTES de borrar al
 * usuario (por eso recibe el nombre como string suelto y no el modelo:
 * para dejar claro que no depende de que el registro siga existiendo).
 * Igual que CuentaCreadaNotification, va envuelta en try/catch en el
 * controlador — si el correo falla, la cuenta se borra igualmente.
 *
 * A propósito se queda solo en 'mail' (sin canal 'database'): el usuario
 * ya no existe justo después de enviarla, así que nunca llegaría a verse
 * en la campanita de notificaciones de la app.
 */
class CuentaEliminadaNotification extends Notification
{
    use Queueable;

    public function __construct(public string $nombre)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu cuenta de MasterBuild se ha eliminado')
            ->view('emails.cuenta-eliminada', [
                'nombre' => $this->nombre,
                'url'    => rtrim(config('app.frontend_url'), '/'),
            ]);
    }
}
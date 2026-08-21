<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Se dispara desde AuthController::register() justo después de crear la
 * cuenta. Sin ShouldQueue, igual que el resto de notificaciones de la
 * app: se envía en el mismo request, y AuthController la envuelve en un
 * try/catch para que un fallo de Resend nunca tumbe el registro (la
 * cuenta ya se creó; el correo es un plus, no un requisito).
 *
 * También va por 'database': queda guardada para mostrarse en la
 * campanita de notificaciones del header (ver NotificacionController).
 */
class CuentaCreadaNotification extends Notification
{
    use Queueable;

    public function __construct(public User $user)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('¡Bienvenido a MasterBuild!')
            ->view('emails.cuenta-creada', [
                'nombre' => $this->user->name,
                'url'    => rtrim(config('app.frontend_url'), '/'),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo'    => 'cuenta_creada',
            'titulo'  => '¡Bienvenido a MasterBuild!',
            'mensaje' => 'Tu cuenta se ha creado correctamente. Ya puedes empezar a comparar y guardar componentes.',
            'url'     => '/home',
            'imagen'  => null,
        ];
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla estándar del canal 'database' de las notificaciones de Laravel
 * (la que genera `php artisan notifications:table`). Es la que alimenta
 * la campanita del header: cada vez que una notificación existente
 * (CuentaCreadaNotification, ComponenteAgotadoNotification,
 * ComponenteDisponibleNotification, AlertaPrecioAlcanzadaNotification)
 * se envía por 'mail', ahora también se guarda aquí una copia para poder
 * listarla en la app sin depender del correo.
 *
 * notifiable_type/notifiable_id son polimórficos a propósito (así lo
 * define Laravel de serie): hoy solo los usa App\Models\User, pero no
 * hace falta tocar esta tabla si en el futuro algún otro modelo también
 * necesita recibir notificaciones en la app.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
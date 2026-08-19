<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * notificado_agotado_en: mismo patrón que alertas_precio.disparada_en,
 * pero para guardados. Guarda cuándo se avisó por última vez de que este
 * guardado se agotó/desapareció, para no reenviar el mismo correo cada
 * noche. Se resetea a null en cuanto vuelve a estar disponible (momento
 * en el que se envía el correo de "disponible de nuevo"), así que un
 * mismo guardado puede volver a notificar en el futuro si se vuelve a
 * agotar — igual que ya se decidió para las alertas de precio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('componentes_guardados', function (Blueprint $table) {
            $table->timestamp('notificado_agotado_en')->nullable()->after('notas');
        });
    }

    public function down(): void
    {
        Schema::table('componentes_guardados', function (Blueprint $table) {
            $table->dropColumn('notificado_agotado_en');
        });
    }
};
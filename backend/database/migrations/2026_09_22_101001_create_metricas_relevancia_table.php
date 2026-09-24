<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Puntuación de relevancia "actual" de cada componente: una fila por
 * componente, igual que precios_actuales es una fila por (componente,
 * tienda). Se regenera ENTERA cada noche (DELETE + INSERT) por
 * RelevanciaService::recalcular(), encadenado en scrape:diario, a partir de
 * interacciones_componente de los últimos N días — así que nunca hay que
 * hacer UPDATE fila a fila ni preocuparse de componentes que "se quedaron
 * viejos" en la tabla: si ya no tienen interacciones recientes, en el
 * siguiente recálculo simplemente no se vuelven a insertar.
 *
 * componente_id es la clave primaria (no hay id propio): es 1:1 con
 * componentes, como precios_actuales es 1:N (componente, tienda).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metricas_relevancia', function (Blueprint $table) {
            $table->foreignId('componente_id')->primary()->constrained('componentes')->cascadeOnDelete();
            $table->unsignedInteger('busquedas_30d')->default(0);
            $table->unsignedInteger('selecciones_30d')->default(0);
            // selecciones_30d * 3 + busquedas_30d (ver RelevanciaService).
            // Se guarda ya calculada para poder ordenar/JOINear por ella
            // directamente en el listado y en el home sin recalcularla en
            // cada petición.
            $table->unsignedInteger('puntuacion')->default(0);
            $table->timestamp('actualizado_en')->nullable();

            $table->index('puntuacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metricas_relevancia');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro crudo de "señales de interés" sobre un componente: apareció
 * entre los resultados de una búsqueda CON TEXTO ('busqueda') o el usuario
 * lo seleccionó/abrió ('seleccion' — desde el buscador, el configurador o
 * los carruseles del home). Es la materia prima de la que
 * RelevanciaService::recalcular() construye metricas_relevancia cada noche
 * (ver ese servicio para los pesos).
 *
 * Solo crece, nunca se actualiza una fila existente, así que no pasa por
 * BaseModel: sin soft deletes, sin updated_at (igual que precios_actuales
 * frente a historial_precios, pero al revés: aquí lo que se guarda es el
 * evento en sí, no un estado "actual").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interacciones_componente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('componente_id')->constrained('componentes')->cascadeOnDelete();
            // 'busqueda' | 'seleccion' — no es una FK a catálogo aparte
            // porque solo son dos valores fijos, ambos controlados por el
            // propio backend (ver InteraccionController), nunca por el
            // cliente directamente.
            $table->string('tipo', 20);
            $table->timestamp('created_at')->useCurrent();

            // Recalcular agrupa por (componente_id, tipo) dentro de una
            // ventana de fechas: este índice es el que usa esa consulta.
            $table->index(['componente_id', 'tipo', 'created_at'], 'interacciones_componente_tipo_fecha_idx');
            // Purga/consulta por tipo y fecha sin filtrar por componente.
            $table->index(['tipo', 'created_at'], 'interacciones_componente_tipo_fecha2_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interacciones_componente');
    }
};

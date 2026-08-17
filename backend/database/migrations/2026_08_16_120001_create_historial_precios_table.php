<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Histórico COMPRIMIDO de precios: una fila por TRAMO en el que un
 * (componente, tienda) mantuvo el mismo precio/stock, no una fila por
 * día. Solo crece cuando el precio cambia de verdad; mientras se
 * mantiene igual, ScrapePrecios solo toca precios_actuales.updated_at y
 * aquí no se escribe nada. El tramo todavía vigente vive en
 * precios_actuales, no aquí (ver comentario de esa migración).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_precios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('componente_id')->constrained('componentes')->cascadeOnDelete();
            $table->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $table->decimal('precio', 10, 2);
            $table->string('moneda', 3)->default('EUR');
            $table->boolean('en_stock')->default(true);
            $table->date('valid_from');
            $table->date('valid_to');
            $table->timestamps();

            $table->index(['componente_id', 'tienda_id', 'valid_from']);
            $table->index(['componente_id', 'valid_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_precios');
    }
};
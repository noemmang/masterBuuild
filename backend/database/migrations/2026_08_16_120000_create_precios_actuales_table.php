<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estado "actual" del precio de cada (componente, tienda): una única fila
 * por par, que se ACTUALIZA en cada scrape en vez de acumular una fila
 * nueva por día. Sustituye a la subquery MAX(id) GROUP BY que antes se
 * repetía en medio proyecto (listados, filtros, recomendador, alertas,
 * guardados) para saber "cuál es el precio de hoy" sobre entradas_precio,
 * una tabla que solo crecía. Ver 2026_08_16_120001_create_historial_precios_table
 * para el histórico comprimido de precios pasados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('precios_actuales', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('componente_id')->constrained('componentes')->cascadeOnDelete();
            $table->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $table->decimal('precio', 10, 2);
            $table->string('moneda', 3)->default('EUR');
            $table->string('url')->nullable();
            $table->boolean('en_stock')->default(true);
            $table->timestamp('vigente_desde')->useCurrent();
            $table->timestamps();

            // Un (componente, tienda) tiene como mucho un precio "actual".
            $table->unique(['componente_id', 'tienda_id']);
            $table->index(['componente_id', 'precio']);
            $table->index('tienda_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('precios_actuales');
    }
};
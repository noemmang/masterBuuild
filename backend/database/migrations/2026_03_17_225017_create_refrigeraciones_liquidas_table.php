<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('refrigeraciones_liquidas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('componente_id')->constrained('componentes')->cascadeOnDelete();
            $table->foreignId('tipo_refrigeracion_id')->constrained('tipos_refrigeracion')->restrictOnDelete();
            $table->integer('tdp_max_watts');
            $table->integer('tam_radiador_mm');
            $table->integer('ancho_radiador_mm')->nullable();
            $table->integer('alto_radiador_mm')->nullable();
            $table->integer('grosor_radiador_mm')->nullable();
            $table->integer('altura_bomba_mm')->nullable();
            $table->integer('ancho_bomba_mm')->nullable();
            $table->integer('profundidad_bomba_mm')->nullable();
            $table->boolean('pantalla_cabezal')->default(false);
            $table->integer('num_ventiladores');
            $table->integer('tam_ventilador_mm');
            $table->integer('rpm_min')->nullable();
            $table->integer('rpm_max')->nullable();
            $table->decimal('ruido_db_min', 4, 1)->nullable();
            $table->decimal('ruido_db_max', 4, 1)->nullable();
            $table->boolean('flujo_personalizable')->default(false);
            $table->boolean('incluye_pasta_termica')->default(true);
            $table->boolean('tiene_rgb')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tdp_max_watts');
            $table->index('tam_radiador_mm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refrigeraciones_liquidas');
    }
};

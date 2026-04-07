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
        Schema::create('refrigeraciones_aire', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('componente_id')->constrained('componentes')->cascadeOnDelete();
            $table->foreignId('tipo_refrigeracion_id')->constrained('tipos_refrigeracion')->restrictOnDelete();
            $table->integer('tdp_max_watts');
            $table->integer('altura_mm');
            $table->integer('ancho_mm')->nullable();
            $table->integer('profundidad_mm')->nullable();
            $table->integer('num_ventiladores')->default(1);
            $table->integer('tam_ventilador_mm');
            $table->integer('rpm_min')->nullable();
            $table->integer('rpm_max')->nullable();
            $table->decimal('ruido_db_min', 4, 1)->nullable();
            $table->decimal('ruido_db_max', 4, 1)->nullable();
            $table->integer('num_heatpipes')->nullable();
            $table->boolean('incluye_pasta_termica')->default(true);
            $table->boolean('tiene_rgb')->default(false);
            $table->boolean('disipador_dual_torre')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tdp_max_watts');
            $table->index('altura_mm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refrigeraciones_aire');
    }
};

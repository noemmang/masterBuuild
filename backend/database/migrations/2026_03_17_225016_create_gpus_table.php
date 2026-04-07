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
        Schema::create('gpus', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('componente_id')->constrained('componentes')->cascadeOnDelete();
            $table->foreignId('arquitectura_id')->constrained('arquitecturas')->restrictOnDelete();
            $table->foreignId('tipo_vram_id')->constrained('tipos_vram')->restrictOnDelete();
            $table->foreignId('version_pcie_id')->constrained('versiones_pcie')->restrictOnDelete();
            $table->integer('vram_gb');
            $table->integer('bus_bits');
            $table->integer('frecuencia_base_mhz');
            $table->integer('frecuencia_boost_mhz')->nullable();
            $table->integer('tdp_watts');
            $table->decimal('slots_pcie', 3, 1);
            $table->integer('longitud_mm');
            $table->json('conectores_alimentacion');
            $table->integer('psu_minima_watts');
            $table->json('salidas_video');
            $table->boolean('ray_tracing')->default(false);
            $table->boolean('dlss')->default(false);
            $table->boolean('fsr')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('longitud_mm');
            $table->index('tdp_watts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gpus');
    }
};

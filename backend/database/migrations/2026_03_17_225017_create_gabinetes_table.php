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
        Schema::create('gabinetes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('componente_id')->constrained('componentes')->cascadeOnDelete();
            $table->foreignId('tipo_gabinete_id')->constrained('tipos_gabinete')->restrictOnDelete();
            $table->foreignId('estructura_gabinete_id')->constrained('estructuras_gabinete')->restrictOnDelete();
            $table->integer('longitud_gpu_max_mm')->nullable();
            $table->integer('altura_cooler_max_mm')->nullable();
            $table->integer('largo_psu_max_mm')->nullable();
            $table->integer('bahias_35')->default(0);
            $table->integer('bahias_25')->default(0);
            $table->integer('ventiladores_frontales')->default(0);
            $table->integer('ventiladores_traseros')->default(0);
            $table->integer('ventiladores_superiores')->default(0);
            $table->integer('ventiladores_incluidos')->default(0);
            $table->integer('tam_ventilador_frontal_mm')->nullable();
            $table->integer('tam_ventilador_superior_mm')->nullable();
            $table->integer('tam_ventilador_trasero_mm')->nullable();
            $table->json('soporte_radiadores')->nullable();
            $table->json('puertos_usb_frontales')->nullable();
            $table->boolean('montaje_vertical_pcie')->default(false);
            $table->string('panel_frontal')->nullable();
            $table->integer('ancho_mm')->nullable();
            $table->integer('alto_mm')->nullable();
            $table->integer('profundidad_mm')->nullable();
            $table->integer('profundidad_camara_principal_mm')->nullable();
            $table->integer('profundidad_camara_secundaria_mm')->nullable();
            $table->integer('particion_min_mm')->nullable();
            $table->integer('particion_max_mm')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gabinetes');
    }
};

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
        Schema::create('cpus', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('componente_id')->constrained('componentes')->cascadeOnDelete();
            $table->foreignId('socket_id')->constrained('sockets')->restrictOnDelete();
            $table->foreignId('arquitectura_id')->constrained('arquitecturas')->restrictOnDelete();
            $table->foreignId('tipo_memoria_id')->constrained('tipos_memoria')->restrictOnDelete();
            $table->integer('nucleos');
            $table->integer('hilos');
            $table->decimal('frecuencia_base_ghz', 4, 2);
            $table->decimal('frecuencia_boost_ghz', 4, 2)->nullable();
            $table->integer('tdp_watts');
            $table->integer('tdp_max_watts')->nullable();
            $table->integer('frecuencia_memoria_max_mhz');
            $table->integer('memoria_max_gb');
            $table->boolean('grafica_integrada')->default(false);
            $table->string('nombre_grafica_integrada')->nullable();
            $table->integer('proceso_nm')->nullable();
            $table->boolean('incluye_cooler')->default(false);
            $table->boolean('overclock')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('socket_id');
            $table->index('tdp_watts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpus');
    }
};

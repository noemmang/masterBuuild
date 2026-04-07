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
        Schema::create('placas_base', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('componente_id')->constrained('componentes')->cascadeOnDelete();
            $table->foreignId('socket_id')->constrained('sockets')->restrictOnDelete();
            $table->foreignId('chipset_id')->constrained('chipsets')->restrictOnDelete();
            $table->foreignId('factor_forma_id')->constrained('factores_forma')->restrictOnDelete();
            $table->foreignId('tipo_memoria_id')->constrained('tipos_memoria')->restrictOnDelete();
            $table->foreignId('version_pcie_id')->constrained('versiones_pcie')->restrictOnDelete();
            $table->integer('slots_memoria');
            $table->integer('memoria_max_gb');
            $table->integer('frecuencia_memoria_max_mhz');
            $table->integer('slots_pcie_x16')->default(1);
            $table->integer('slots_pcie_x4')->default(0);
            $table->integer('slots_pcie_x1')->default(0);
            $table->integer('slots_m2')->default(0);
            $table->integer('puertos_sata')->default(0);
            $table->json('puertos_usb_traseros')->nullable();
            $table->string('conector_atx')->default('24pin');
            $table->string('conector_cpu')->default('8pin');
            $table->boolean('wifi')->default(false);
            $table->boolean('bluetooth')->default(false);
            $table->boolean('thunderbolt')->default(false);
            $table->string('audio_chipset')->nullable();
            $table->string('lan_chipset')->nullable();
            $table->decimal('lan_velocidad_gbps', 4, 1)->default(1.0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('socket_id');
            $table->index('factor_forma_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('placas_base');
    }
};

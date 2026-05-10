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
        Schema::create('ventiladores', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('componente_id')->constrained('componentes')->cascadeOnDelete();
            $table->foreignId('tipo_ventilador_id')->constrained('tipos_ventilador')->restrictOnDelete();
            $table->integer('rpm_min')->nullable();
            $table->integer('rpm_max')->nullable();
            $table->decimal('ruido_db_min', 4, 1)->nullable();
            $table->decimal('ruido_db_max', 4, 1)->nullable();
            $table->decimal('flujo_aire_cfm', 6, 2)->nullable();
            $table->decimal('static_pressure_mmh2o', 6, 2)->nullable();
            $table->integer('num_ventiladores')->default(1);
            $table->boolean('tiene_rgb')->default(false);
            $table->boolean('pwm')->default(true);
            $table->integer('tam_mm')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventiladores');
    }
};

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
        Schema::create('psus', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('componente_id')->constrained('componentes')->cascadeOnDelete();
            $table->foreignId('certificacion_id')->constrained('certificaciones_psu')->restrictOnDelete();
            $table->foreignId('tipo_psu_id')->constrained('tipos_psu')->restrictOnDelete();
            $table->integer('vatios');
            $table->string('modular')->default('no_modular');
            $table->string('version_atx')->nullable();
            $table->integer('conectores_pcie_16pin')->default(0);
            $table->integer('conectores_pcie_8pin')->default(0);
            $table->integer('conectores_sata')->default(0);
            $table->integer('conectores_molex')->default(0);
            $table->integer('largo_mm')->nullable();
            $table->integer('ventilador_mm')->nullable();
            $table->boolean('ventilador_zero_rpm')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('vatios');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('psus');
    }
};

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
        Schema::create('almacenamientos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('componente_id')->constrained('componentes')->cascadeOnDelete();
            $table->foreignId('interfaz_id')->constrained('interfaces_almacenamiento')->restrictOnDelete();
            $table->foreignId('factor_forma_id')->constrained('factores_forma_almacenamiento')->restrictOnDelete();
            $table->foreignId('tipo_nand_id')->nullable()->constrained('tipos_nand')->restrictOnDelete();
            $table->string('tipo');
            $table->integer('capacidad_gb');
            $table->integer('velocidad_lectura_mbs')->nullable();
            $table->integer('velocidad_escritura_mbs')->nullable();
            $table->integer('rpm')->nullable();
            $table->integer('cache_mb')->nullable();
            $table->integer('tbw')->nullable();
            $table->boolean('cifrado')->default(false);
            $table->boolean('dram')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tipo');
            $table->index('capacidad_gb');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('almacenamientos');
    }
};

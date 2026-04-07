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
        Schema::create('rams', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('componente_id')->constrained('componentes')->cascadeOnDelete();
            $table->foreignId('tipo_memoria_id')->constrained('tipos_memoria')->restrictOnDelete();
            $table->integer('capacidad_gb');
            $table->integer('modulos');
            $table->integer('capacidad_total_gb');
            $table->integer('velocidad_mhz');
            $table->integer('latencia_cas');
            $table->decimal('voltaje', 4, 2);
            $table->string('factor_forma');
            $table->integer('altura_mm')->nullable();
            $table->boolean('tiene_rgb')->default(false);
            $table->boolean('ecc')->default(false);
            $table->boolean('xmp')->default(false);
            $table->boolean('expo')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tipo_memoria_id');
            $table->index('velocidad_mhz');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rams');
    }
};

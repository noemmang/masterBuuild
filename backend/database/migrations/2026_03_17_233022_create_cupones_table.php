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
        Schema::create('cupones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $table->string('codigo');
            $table->text('descripcion')->nullable();
            $table->string('tipo')->default('porcentaje');
            $table->decimal('porcentaje_descuento', 5, 2)->nullable();
            $table->decimal('descuento_fijo', 10, 2)->nullable();
            $table->decimal('minimo_compra', 10, 2)->nullable();
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_expiracion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tienda_id', 'codigo']);
            $table->index('activo');
            $table->index('fecha_expiracion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cupones');
    }
};

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
        Schema::create('regalo_componente', function (Blueprint $table) {
            $table->foreignId('regalo_id')->constrained('regalos')->cascadeOnDelete();
            $table->foreignId('componente_id')->constrained('componentes')->cascadeOnDelete();
            $table->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_expiracion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->primary(['regalo_id', 'componente_id', 'tienda_id']);

            $table->index('activo');
            $table->index('fecha_expiracion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regalo_componente');
    }
};

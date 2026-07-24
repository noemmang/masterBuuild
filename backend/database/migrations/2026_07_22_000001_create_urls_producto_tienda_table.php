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
        Schema::create('urls_producto_tienda', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('componente_id')->constrained('componentes')->cascadeOnDelete();
            $table->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $table->string('url');
            $table->boolean('activo')->default(true);
            $table->timestamp('ultimo_scrape_at')->nullable();
            $table->timestamps();

            // Un mismo componente no puede tener 2 URLs distintas en la misma tienda
            $table->unique(['componente_id', 'tienda_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('urls_producto_tienda');
    }
};

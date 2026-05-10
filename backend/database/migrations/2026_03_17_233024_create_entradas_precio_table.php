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
        Schema::create('entradas_precio', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('componente_id')->constrained('componentes')->cascadeOnDelete();
            $table->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $table->decimal('precio', 10, 2);
            $table->string('moneda', 3)->default('EUR');
            $table->string('url')->nullable();
            $table->boolean('en_stock')->default(true);
            $table->foreignId('cupon_id')->nullable()->constrained('cupones')->nullOnDelete();
            $table->decimal('precio_con_cupon', 10, 2)->nullable();
            $table->timestamp('scraped_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['componente_id', 'tienda_id', 'scraped_at']);
            $table->index(['componente_id', 'precio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entradas_precio');
    }
};

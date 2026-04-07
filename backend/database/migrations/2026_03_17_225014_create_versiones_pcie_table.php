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
        Schema::create('versiones_pcie', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('ancho_banda_gbs', 8, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('versiones_pcie');
    }
};

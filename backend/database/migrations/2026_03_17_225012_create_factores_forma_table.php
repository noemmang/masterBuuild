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
        Schema::create('factores_forma', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->integer('ancho_mm')->nullable();
            $table->integer('largo_mm')->nullable();
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
        Schema::dropIfExists('factores_forma');
    }
};

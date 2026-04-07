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
        Schema::create('componentes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nombre');
            $table->foreignId('marca_id')->constrained('marcas')->restrictOnDelete();
            $table->foreignId('fabricante_id')->nullable()->constrained('marcas')->restrictOnDelete();
            $table->string('categoria');
            $table->string('modelo')->nullable();
            $table->string('imagen_url')->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('categoria');
            $table->index('marca_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('componentes');
    }
};

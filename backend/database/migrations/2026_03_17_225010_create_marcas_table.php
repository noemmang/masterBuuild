<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marcas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nombre');
            $table->json('tipo');
            $table->string('website')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('pais_origen', 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marcas');
    }
};

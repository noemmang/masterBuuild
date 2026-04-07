<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('alertas_precio', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('componente_id')->constrained('componentes')->cascadeOnDelete();
            $table->decimal('precio_objetivo', 10, 2);
            $table->boolean('activa')->default(true);
            $table->timestamp('disparada_en')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'componente_id']); // una alerta por componente por usuario
        });
    }

    public function down(): void {
        Schema::dropIfExists('alertas_precio');
    }
};
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
        Schema::create('refrigeracion_liquida_socket', function (Blueprint $table) {
            $table->foreignId('refrigeracion_liquida_id')->constrained('refrigeraciones_liquidas')->cascadeOnDelete();
            $table->foreignId('socket_id')->constrained('sockets')->cascadeOnDelete();
            $table->primary(['refrigeracion_liquida_id', 'socket_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refrigeracion_liquida_socket');
    }
};

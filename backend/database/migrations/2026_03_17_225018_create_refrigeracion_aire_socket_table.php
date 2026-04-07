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
        Schema::create('refrigeracion_aire_socket', function (Blueprint $table) {
            $table->foreignId('refrigeracion_aire_id')->constrained('refrigeraciones_aire')->cascadeOnDelete();
            $table->foreignId('socket_id')->constrained('sockets')->cascadeOnDelete();
            $table->primary(['refrigeracion_aire_id', 'socket_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refrigeracion_aire_socket');
    }
};

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
        Schema::create('gabinete_tipo_psu', function (Blueprint $table) {
            $table->foreignId('gabinete_id')->constrained('gabinetes')->cascadeOnDelete();
            $table->foreignId('tipo_psu_id')->constrained('tipos_psu')->cascadeOnDelete();
            $table->primary(['gabinete_id', 'tipo_psu_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gabinete_tipo_psu');
    }
};

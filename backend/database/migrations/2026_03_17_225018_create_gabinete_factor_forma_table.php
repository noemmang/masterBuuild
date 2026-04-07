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
        Schema::create('gabinete_factor_forma', function (Blueprint $table) {
            $table->foreignId('gabinete_id')->constrained('gabinetes')->cascadeOnDelete();
            $table->foreignId('factor_forma_id')->constrained('factores_forma')->cascadeOnDelete();
            $table->primary(['gabinete_id', 'factor_forma_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gabinete_factor_forma');
    }
};

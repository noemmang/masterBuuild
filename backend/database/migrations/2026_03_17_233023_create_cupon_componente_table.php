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
        Schema::create('cupon_componente', function (Blueprint $table) {
            $table->foreignId('cupon_id')->constrained('cupones')->cascadeOnDelete();
            $table->foreignId('componente_id')->constrained('componentes')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['cupon_id', 'componente_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cupon_componente');
    }
};

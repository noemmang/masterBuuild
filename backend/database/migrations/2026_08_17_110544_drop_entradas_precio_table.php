<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina entradas_precio, ya sustituida por precios_actuales +
 * historial_precios. Ejecutar solo cuando lleves unos días viendo
 * funcionar el scraping nuevo sin problemas: mientras la tabla exista,
 * es tu red de seguridad para volver a la versión anterior sin perder
 * datos si algo sale mal con el despliegue nuevo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('entradas_precio');
    }

    public function down(): void
    {
        // No se recrea vacía: si hay que volver atrás, se restaura
        // desde el backup de Neon, no desde una tabla en blanco.
    }
};
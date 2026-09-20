<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Qué promociones de regalo tiene un componente. La TIENDA no se repite
 * aquí: ya viene en promociones_regalo.tienda_id, y es lo que garantiza que
 * el regalo solo se enseñe en la tienda a la que corresponde.
 *
 * activa: el último scrape de ese producto seguía viendo la promoción.
 * ScrapePrecios la pone a false cuando deja de aparecer (o cuando la URL
 * pasa a no_disponible) y la vuelve a poner a true si reaparece. No se
 * borra la fila: así queda constancia de que existió.
 *
 * updated_at: última vez que el scraping CONFIRMÓ la promoción (mismo
 * criterio que precios_actuales.updated_at).
 *
 * Los nombres de los índices se dan a mano porque los autogenerados por
 * Laravel a partir de estos nombres de tabla/columna superan los 63
 * caracteres de Postgres y este los recorta en silencio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('componente_promocion_regalo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('componente_id')->constrained('componentes')->cascadeOnDelete();
            $table->foreignId('promocion_regalo_id')->constrained('promociones_regalo')->cascadeOnDelete();
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['componente_id', 'promocion_regalo_id'], 'cpr_componente_promocion_unique');
            $table->index(['componente_id', 'activa'], 'cpr_componente_activa_idx');
            $table->index('promocion_regalo_id', 'cpr_promocion_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('componente_promocion_regalo');
    }
};

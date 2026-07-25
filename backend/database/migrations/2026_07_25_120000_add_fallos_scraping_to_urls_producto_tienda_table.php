<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade el tracking de fallos de scraping a urls_producto_tienda para poder
 * distinguir un fallo puntual (red caída, timeout de la tienda...) de una
 * URL que lleva rota N veces seguidas (producto descatalogado, URL movida,
 * etc.). Cuando "no_disponible" es true, Componente::scopeDisponible()
 * oculta el producto del listado/detalle del front.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('urls_producto_tienda', function (Blueprint $table) {
            $table->unsignedInteger('fallos_consecutivos')->default(0)->after('ultimo_scrape_at');
            $table->boolean('no_disponible')->default(false)->after('fallos_consecutivos');
            $table->string('ultimo_error')->nullable()->after('no_disponible');
        });
    }

    public function down(): void
    {
        Schema::table('urls_producto_tienda', function (Blueprint $table) {
            $table->dropColumn(['fallos_consecutivos', 'no_disponible', 'ultimo_error']);
        });
    }
};
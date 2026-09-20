<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de promociones de regalo de cada TIENDA (p. ej. en Coolmod:
 * "Consigue CONTROL Resonant GeForce RTX 50 Series Bundle"). Una misma
 * promoción suele aplicar a muchos productos, así que vive aquí una sola
 * vez y se enlaza a cada componente desde componente_promocion_regalo.
 *
 * Es estado derivado del scraping (igual que precios_actuales): no lleva
 * soft deletes; lo crea y lo actualiza ScrapePrecios a través de
 * PromocionRegaloService.
 *
 * slug: identificador estable de la promoción dentro de la tienda. Para
 * Coolmod es el último tramo de /promocion/{slug}. Junto con tienda_id es
 * la clave natural con la que se reconoce la misma promoción de un scrape
 * a otro (el título o la imagen pueden cambiar sin que sea otra promo).
 *
 * fecha_inicio / fecha_fin: vigencia que anuncia la propia tienda. Nulas
 * = sin límite por ese lado. Aunque el scraping diario ya retira lo que la
 * tienda deja de mostrar, la fecha de fin se comprueba también al LEER
 * (ver PromocionRegalo::scopeVigentes) para que un regalo caducado deje de
 * verse ese mismo día aunque el scraping de esa noche falle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promociones_regalo', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $table->string('slug');
            $table->string('titulo');
            $table->string('tipo')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('imagen_url', 500)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->timestamps();

            // Una promoción (por slug) existe una sola vez por tienda.
            $table->unique(['tienda_id', 'slug']);
            $table->index('fecha_fin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promociones_regalo');
    }
};

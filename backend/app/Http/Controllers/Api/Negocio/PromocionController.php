<?php

namespace App\Http\Controllers\Api\Negocio;

use App\Http\Controllers\Controller;
use App\Http\Resources\PromocionBannerResource;
use App\Models\Negocio\PromocionRegalo;
use Illuminate\Support\Facades\Cache;

class PromocionController extends Controller
{
    // Tope de banners que se devuelven. El front los hace rotar uno tras
    // otro; con inRandomOrder() antes del límite, si hubiera más
    // promociones activas que esto, cada refresco de la caché reparte la
    // oportunidad entre todas.
    private const LIMITE_BANNERS = 20;

    // Caché corta (y no de 24 h como destacados()/promociones()): esta
    // consulta es barata y, a diferencia de aquellas, queremos que una
    // promoción que caduca (fecha_fin) o se agota deje de salir como
    // banner en cuestión de minutos, no de un día.
    private const CACHE_BANNERS = 'promociones:banners';
    private const CACHE_TTL_MINUTOS = 30;

    /**
     * Banners (imagen + enlace) de las promociones de regalo ACTIVAS, para
     * la cabecera (slider rotatorio) de la sección "Promociones" del home. Lista vacía si no
     * hay ninguna: el front oculta entonces el banner.
     *
     * "Activa" es exactamente lo mismo que en el resto de la web
     * (Componente::promocionesRegaloVisibles): el último scrape sigue
     * viéndola (pivot.activa), está dentro de su vigencia y su tienda tiene
     * ese componente EN STOCK. Una promoción cuenta si cumple eso para al
     * menos UN componente que se pueda mostrar (activo y con precio).
     *
     * Además, para que el banner se pueda pintar y pulsar, la promoción
     * necesita imagen y enlace con http(s): son datos scrapeados, así que
     * se descarta cualquier otra cosa (p. ej. un "javascript:" o un
     * "data:") en vez de fiarse de ellos.
     *
     * La rotación entre banners la hace el front, no este endpoint: así la
     * respuesta es la misma para todos y se puede cachear. No se intenta
     * deduplicar promociones iguales de distintas tiendas (no hay forma
     * fiable de saberlo): simplemente rotan todas las activas.
     */
    public function banners()
    {
        $data = Cache::remember(self::CACHE_BANNERS, now()->addMinutes(self::CACHE_TTL_MINUTOS), function () {
            $promociones = PromocionRegalo::query()
                ->with('tienda')
                ->vigentes()
                ->where('imagen_url', 'like', 'http%')
                ->where('url', 'like', 'http%')
                ->whereHas('componentes', function ($componentes) {
                    $componentes
                        ->where('componente_promocion_regalo.activa', true)
                        ->activo()
                        ->visible()
                        // El stock se mira en la tienda de la PROPIA promoción
                        // (no en cualquiera): el regalo es de esa tienda.
                        ->whereExists(function ($sub) {
                            $sub->selectRaw('1')
                                ->from('precios_actuales')
                                ->whereColumn('precios_actuales.componente_id', 'componentes.id')
                                ->whereColumn('precios_actuales.tienda_id', 'promociones_regalo.tienda_id')
                                ->where('precios_actuales.en_stock', true);
                        });
                })
                ->inRandomOrder()
                ->limit(self::LIMITE_BANNERS)
                ->get();

            return PromocionBannerResource::collection($promociones)->resolve();
        });

        return response()->json(['data' => $data]);
    }
}
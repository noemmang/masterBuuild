<?php

namespace App\Services\Promociones;

use App\Models\Negocio\PromocionRegalo;
use App\Scrapers\DTO\PromocionScrapeada;
use Illuminate\Support\Facades\DB;

/**
 * Deja la tabla de regalos de un (componente, tienda) igual a lo que la
 * tienda muestra AHORA en la ficha del producto. ScrapePrecios lo llama en
 * cada ejecución diaria, y es ahí donde se "revisa si sigue activa":
 *
 *  - lo que aparece se crea/actualiza y se marca activa (y se le toca
 *    updated_at: última vez confirmada);
 *  - lo que estaba activo y ya no aparece se marca inactiva.
 *
 * Solo toca las promociones de ESA tienda para ESE componente: las de otras
 * tiendas del mismo componente ni se miran.
 */
class PromocionRegaloService
{
    private const PIVOT = 'componente_promocion_regalo';

    /**
     * @param  PromocionScrapeada[]  $promociones  Lo que la tienda muestra ahora (puede ser []).
     * @return array{vistas:int, nuevas:int, reactivadas:int, desactivadas:int}
     */
    public function sincronizar(int $componenteId, int $tiendaId, array $promociones): array
    {
        return DB::transaction(function () use ($componenteId, $tiendaId, $promociones) {
            $ahora = now();
            $idsVistos = [];

            foreach ($promociones as $p) {
                // La promoción (catálogo de la tienda) se crea o se refresca:
                // título, imagen o fechas pueden cambiar sin que sea otra promo.
                $promo = PromocionRegalo::updateOrCreate(
                    ['tienda_id' => $tiendaId, 'slug' => $p->slug],
                    [
                        'titulo'       => $p->titulo,
                        'tipo'         => $p->tipo,
                        'url'          => $p->url,
                        'imagen_url'   => $p->imagenUrl,
                        'fecha_inicio' => $p->fechaInicio,
                        'fecha_fin'    => $p->fechaFin,
                    ]
                );

                $idsVistos[$promo->id] = true;
            }

            $ids = array_keys($idsVistos);

            // Estado previo del enlace componente↔promoción, para distinguir
            // una reactivación de una promo que ya estaba activa.
            $previas = $ids === []
                ? collect()
                : DB::table(self::PIVOT)
                    ->where('componente_id', $componenteId)
                    ->whereIn('promocion_regalo_id', $ids)
                    ->pluck('activa', 'promocion_regalo_id');

            $nuevas = 0;
            $reactivadas = 0;

            foreach ($ids as $promoId) {
                // insertOrIgnore es atómico (INSERT ... ON CONFLICT DO NOTHING en
                // Postgres): si dos ejecuciones procesan a la vez el mismo producto,
                // ninguna falla ni duplica, porque el índice único
                // (componente_id, promocion_regalo_id) decide quién inserta. Un
                // "mirar si existe y luego insertar" fallaba con
                // UniqueConstraintViolation al coincidir dos procesos.
                $insertadas = DB::table(self::PIVOT)->insertOrIgnore([
                    'componente_id'       => $componenteId,
                    'promocion_regalo_id' => $promoId,
                    'activa'              => true,
                    'created_at'          => $ahora,
                    'updated_at'          => $ahora,
                ]);

                if ($insertadas > 0) {
                    $nuevas++;

                    continue;
                }

                // Ya existía (o lo acaba de crear otro proceso): se reconfirma.
                // Si constaba como inactiva es una reactivación.
                if ($previas->has($promoId) && !$previas[$promoId]) {
                    $reactivadas++;
                }

                DB::table(self::PIVOT)
                    ->where('componente_id', $componenteId)
                    ->where('promocion_regalo_id', $promoId)
                    ->update(['activa' => true, 'updated_at' => $ahora]);
            }

            // Lo que estaba activo para este componente en ESTA tienda y ya
            // no aparece: la tienda lo ha retirado.
            $desactivadas = DB::table(self::PIVOT)
                ->where('componente_id', $componenteId)
                ->where('activa', true)
                ->whereIn('promocion_regalo_id', PromocionRegalo::where('tienda_id', $tiendaId)->select('id'))
                ->when($ids !== [], fn ($q) => $q->whereNotIn('promocion_regalo_id', $ids))
                ->update(['activa' => false, 'updated_at' => $ahora]);

            return [
                'vistas'       => count($ids),
                'nuevas'       => $nuevas,
                'reactivadas'  => $reactivadas,
                'desactivadas' => $desactivadas,
            ];
        });
    }

    /**
     * Desactiva todos los regalos de un (componente, tienda). Se usa cuando
     * la ficha lleva demasiados scrapes fallidos seguidos (URL marcada
     * no_disponible): ya no podemos confirmar que el regalo siga ahí.
     * Si el scraping se recupera, sincronizar() los reactiva.
     */
    public function desactivarTodas(int $componenteId, int $tiendaId): int
    {
        return $this->sincronizar($componenteId, $tiendaId, [])['desactivadas'];
    }
}

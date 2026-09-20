<?php

namespace App\Models\Negocio;

use App\Models\Componentes\Componente;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Promoción de regalo de UNA tienda (ver la migración create_promociones_regalo
 * para el porqué de cada columna). Mismo criterio que PrecioActual: es estado
 * derivado del scraping, así que no pasa por BaseModel ni lleva soft deletes.
 *
 * Qué componentes la tienen ahora mismo vive en la tabla intermedia
 * componente_promocion_regalo (columna "activa"). Para saber qué regalos
 * debe ENSEÑAR el front de un componente NO uses esta relación a mano: usa
 * Componente::promocionesRegaloVisibles(), que aplica en un solo sitio las
 * tres condiciones (activa + vigente por fechas + tienda con stock).
 */
class PromocionRegalo extends Model
{
    use HasUuids;

    protected $table = 'promociones_regalo';

    /**
     * Las fechas de las promociones son fechas de calendario de la tienda
     * (España), no instantes UTC. La app corre en UTC (config/app.php), así
     * que sin fijar la zona, entre las 00:00 y las 02:00 hora peninsular
     * "hoy" seguiría siendo ayer y un regalo caducado se vería unas horas más.
     */
    public const ZONA_HORARIA = 'Europe/Madrid';

    protected $fillable = [
        'tienda_id',
        'slug',
        'titulo',
        'tipo',
        'url',
        'imagen_url',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    protected $hidden = [
        'id',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'tienda_id');
    }

    public function componentes()
    {
        return $this->belongsToMany(
            Componente::class,
            'componente_promocion_regalo',
            'promocion_regalo_id',
            'componente_id'
        )->withPivot('activa')->withTimestamps();
    }

    /**
     * Promociones dentro de su vigencia hoy (hora de España): ya han
     * empezado y todavía no han terminado. Una fecha nula no limita por
     * ese lado. fecha_fin es INCLUSIVA: el último día sigue valiendo.
     */
    public function scopeVigentes($query)
    {
        $hoy = now(self::ZONA_HORARIA)->toDateString();

        return $query
            ->where(fn ($q) => $q->whereNull('promociones_regalo.fecha_inicio')
                                 ->orWhere('promociones_regalo.fecha_inicio', '<=', $hoy))
            ->where(fn ($q) => $q->whereNull('promociones_regalo.fecha_fin')
                                 ->orWhere('promociones_regalo.fecha_fin', '>=', $hoy));
    }
}

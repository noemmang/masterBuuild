<?php

namespace App\Models\Negocio;

use App\Models\Componentes\Componente;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Precio "actual" de un (componente, tienda): una única fila por par que
 * se ACTUALIZA en cada scrape en vez de acumular una fila nueva por día.
 * La consulta prácticamente todo el resto de la app (listados, filtros,
 * ordenar por precio, recomendador, alertas, guardados) para saber el
 * precio de hoy, en vez de calcular un MAX(id) GROUP BY sobre un
 * histórico que no paraba de crecer.
 *
 * vigente_desde: desde cuándo es válido el precio que hay ahora mismo.
 * updated_at: última vez que el scraping confirmó este precio (cambie o
 * no); ScrapePrecios lo usa para saber hasta qué día fue válido el precio
 * anterior cuando detecta un cambio (ver HistorialPrecio).
 *
 * No lleva soft deletes ni pasa por BaseModel a propósito: es una tabla
 * de estado derivado, no algo que el usuario "elimine"; el histórico de
 * verdad, si hace falta conservarlo, vive en HistorialPrecio.
 */
class PrecioActual extends Model
{
    use HasUuids;

    protected $table = 'precios_actuales';

    protected $fillable = [
        'componente_id',
        'tienda_id',
        'precio',
        'moneda',
        'url',
        'en_stock',
        'vigente_desde',
    ];

    protected $casts = [
        'precio'        => 'decimal:2',
        'en_stock'      => 'boolean',
        'vigente_desde' => 'datetime',
    ];

    protected $hidden = [
        'id',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'tienda_id');
    }

    // Mismo helper que tenía EntradaPrecio: así GuardadoController y
    // cualquier otro sitio que ya lo usara no necesitan cambiar nada.
    public function precioEfectivo(): float
    {
        return (float) $this->precio;
    }

    public function scopeEnStock($query)
    {
        return $query->where('en_stock', true);
    }
}
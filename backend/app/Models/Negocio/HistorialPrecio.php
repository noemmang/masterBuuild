<?php

namespace App\Models\Negocio;

use App\Models\Componentes\Componente;
use Illuminate\Database\Eloquent\Model;

/**
 * Tramo CERRADO del histórico de precios: un (componente, tienda) valió
 * `precio` desde valid_from hasta valid_to (ambos incluidos). Solo se
 * crea una fila cuando el precio o el stock cambian de verdad — mientras
 * se mantienen iguales, ScrapePrecios no escribe nada aquí, solo
 * actualiza PrecioActual::updated_at.
 *
 * El tramo todavía vigente (el precio de ahora mismo) NO vive en esta
 * tabla, vive en PrecioActual. Las consultas que necesitan el histórico
 * completo (el gráfico de PrecioController::historial()) unen esta tabla
 * con precios_actuales tratando su vigente_desde → hoy como el último
 * tramo, todavía abierto.
 */
class HistorialPrecio extends Model
{
    protected $table = 'historial_precios';

    protected $fillable = [
        'componente_id',
        'tienda_id',
        'precio',
        'moneda',
        'en_stock',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'precio'     => 'decimal:2',
        'en_stock'   => 'boolean',
        'valid_from' => 'date',
        'valid_to'   => 'date',
    ];

    protected $hidden = [
        'id',
    ];

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'tienda_id');
    }

    // Tramos que solapan con un rango de fechas [desde, hasta].
    public function scopeEntreFechas($query, $desde, $hasta)
    {
        return $query->where('valid_from', '<=', $hasta)
                     ->where('valid_to', '>=', $desde);
    }
}
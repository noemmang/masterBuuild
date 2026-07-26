<?php

namespace App\Models\Negocio;

use App\Models\BaseModel;
use App\Models\Componentes\Componente;

class EntradaPrecio extends BaseModel
{
    protected $table = 'entradas_precio';

    protected $fillable = [
        'componente_id',
        'tienda_id',
        'precio',
        'moneda',
        'url',
        'en_stock',
        'scraped_at',
    ];

    protected $casts = [
        'precio'     => 'decimal:2',
        'en_stock'   => 'boolean',
        'scraped_at' => 'datetime',
    ];

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'tienda_id');
    }

    // Devuelve el precio de esta entrada
    public function precioEfectivo(): float
    {
        return (float) $this->precio;
    }

    // Solo el precio más reciente por componente y tienda
    public function scopeActual($query)
    {
        return $query->whereIn('id', function ($subquery) {
            $subquery->selectRaw('MAX(id)')
                     ->from('entradas_precio')
                     ->groupBy('componente_id', 'tienda_id');
        });
    }

    // Historial completo de un componente ordenado por fecha
    public function scopeHistorial($query, $componenteId)
    {
        return $query->where('componente_id', $componenteId)
                     ->orderBy('scraped_at', 'desc');
    }

    // Mejor precio actual entre todas las tiendas
    public function scopeMejorPrecio($query, $componenteId)
    {
        return $query->actual()
                     ->where('componente_id', $componenteId)
                     ->where('en_stock', true)
                     ->orderBy('precio', 'asc')
                     ->first();
    }

    // Precios en un rango de fechas para el gráfico de historial
    public function scopeEntreFechas($query, $desde, $hasta)
    {
        return $query->whereBetween('scraped_at', [$desde, $hasta]);
    }

    // Solo entradas en stock
    public function scopeEnStock($query)
    {
        return $query->where('en_stock', true);
    }
}
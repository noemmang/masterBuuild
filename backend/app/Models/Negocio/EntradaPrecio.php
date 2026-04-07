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
        'cupon_id',
        'precio',
        'moneda',
        'url',
        'en_stock',
        'precio_con_cupon',
        'scraped_at',
    ];

    protected $casts = [
        'precio'           => 'decimal:2',
        'precio_con_cupon' => 'decimal:2',
        'en_stock'         => 'boolean',
        'scraped_at'       => 'datetime',
    ];

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'tienda_id');
    }

    public function cupon()
    {
        return $this->belongsTo(Cupon::class, 'cupon_id');
    }

    // Devuelve el precio más bajo disponible (con cupón si existe)
    public function precioEfectivo(): float
    {
        if ($this->precio_con_cupon) {
            return min($this->precio, $this->precio_con_cupon);
        }
        return $this->precio;
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

    // Mejor precio actual entre todas las tiendas incluyendo cupones
    public function scopeMejorPrecio($query, $componenteId)
    {
        return $query->actual()
                     ->where('componente_id', $componenteId)
                     ->where('en_stock', true)
                     ->orderByRaw('COALESCE(precio_con_cupon, precio) ASC')
                     ->first();
    }

    // Precios en un rango de fechas para el gráfico de historial
    public function scopeEntreFechas($query, $desde, $hasta)
    {
        return $query->whereBetween('scraped_at', [$desde, $hasta]);
    }

    // Solo entradas con cupón activo
    public function scopeConCupon($query)
    {
        return $query->whereNotNull('cupon_id')
                     ->whereNotNull('precio_con_cupon');
    }

    // Solo entradas en stock
    public function scopeEnStock($query)
    {
        return $query->where('en_stock', true);
    }
}
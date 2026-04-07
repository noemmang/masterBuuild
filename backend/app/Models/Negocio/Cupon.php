<?php

namespace App\Models\Negocio;

use App\Models\BaseModel;

class Cupon extends BaseModel
{
    protected $table = 'cupones';

    protected $fillable = [
        'tienda_id',
        'codigo',
        'descripcion',
        'tipo',
        'porcentaje_descuento',
        'descuento_fijo',
        'minimo_compra',
        'fecha_inicio',
        'fecha_expiracion',
        'activo',
    ];

    protected $casts = [
        'porcentaje_descuento' => 'decimal:2',
        'descuento_fijo'       => 'decimal:2',
        'minimo_compra'        => 'decimal:2',
        'fecha_inicio'         => 'datetime',
        'fecha_expiracion'     => 'datetime',
        'activo'               => 'boolean',
    ];

    const TIPOS = ['porcentaje', 'fijo'];

    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'tienda_id');
    }

    public function componentes()
    {
        return $this->belongsToMany(
            \App\Models\Componentes\Componente::class,
            'cupon_componente',
            'cupon_id',
            'componente_id'
        )->withTimestamps();
    }

    // Calcula el precio final aplicando el cupón
    public function calcularDescuento(float $precio): float
    {
        if ($this->minimo_compra && $precio < $this->minimo_compra) {
            return $precio;
        }

        if ($this->tipo === 'porcentaje') {
            return round($precio - ($precio * $this->porcentaje_descuento / 100), 2);
        }

        return round(max(0, $precio - $this->descuento_fijo), 2);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true)
                     ->where('fecha_inicio', '<=', now())
                     ->where(function ($q) {
                         $q->whereNull('fecha_expiracion')
                           ->orWhere('fecha_expiracion', '>=', now());
                     });
    }

    public function scopeParaComponente($query, $componenteId)
    {
        return $query->whereHas('componentes', fn($q) =>
            $q->where('componente_id', $componenteId)
        );
    }
}
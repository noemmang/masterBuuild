<?php

namespace App\Models\Negocio;

use App\Models\BaseModel;

class Tienda extends BaseModel
{
    protected $table = 'tiendas';

    protected $fillable = [
        'nombre',
        'url',
        'logo_url',
        'clase_scraper',
        'url_afiliado',
        'pais',
        'moneda',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    const PAISES = ['ES', 'DE', 'FR', 'IT', 'UK', 'US'];
    const MONEDAS = ['EUR', 'GBP', 'USD'];

    // Histórico cerrado de precios de esta tienda (ver comentario en
    // Componente::precios()).
    public function precios()
    {
        return $this->hasMany(HistorialPrecio::class, 'tienda_id');
    }

    // Precio actual por componente en esta tienda: lectura directa de
    // precios_actuales, sin subquery.
    public function preciosActuales()
    {
        return $this->hasMany(PrecioActual::class, 'tienda_id');
    }

    public function scopeActiva($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorPais($query, $pais)
    {
        return $query->where('pais', $pais);
    }
}
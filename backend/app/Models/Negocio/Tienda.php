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

    public function precios()
    {
        return $this->hasMany(EntradaPrecio::class, 'tienda_id');
    }

    public function preciosActuales()
    {
        return $this->hasMany(EntradaPrecio::class, 'tienda_id')
                    ->whereIn('id', function ($query) {
                        $query->selectRaw('MAX(id)')
                              ->from('entradas_precio')
                              ->whereColumn('tienda_id', 'tiendas.id')
                              ->groupBy('componente_id');
                    });
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
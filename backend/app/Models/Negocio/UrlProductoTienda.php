<?php

namespace App\Models\Negocio;

use App\Models\BaseModel;
use App\Models\Componentes\Componente;

/**
 * Guarda, para cada par (componente, tienda), la URL fija del producto
 * que hay que volver a descargar en cada ejecución de scrape:precios.
 * Es la "configuración" del scraping; entradas_precio sigue siendo el
 * histórico de resultados de cada scrape.
 */
class UrlProductoTienda extends BaseModel
{
    protected $table = 'urls_producto_tienda';

    protected $fillable = [
        'componente_id',
        'tienda_id',
        'url',
        'activo',
        'ultimo_scrape_at',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'ultimo_scrape_at' => 'datetime',
    ];

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'tienda_id');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }
}

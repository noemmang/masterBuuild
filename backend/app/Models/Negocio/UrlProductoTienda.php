<?php

namespace App\Models\Negocio;

use App\Models\BaseModel;
use App\Models\Componentes\Componente;

/**
 * Guarda, para cada par (componente, tienda), la URL fija del producto
 * que hay que volver a descargar en cada ejecución de scrape:precios.
 * Es la "configuración" del scraping; entradas_precio sigue siendo el
 * histórico de resultados de cada scrape.
 *
 * fallos_consecutivos / no_disponible / ultimo_error los mantiene
 * ScrapePrecios en cada ejecución: si una URL falla N veces seguidas
 * (ver ScrapePrecios::UMBRAL_NO_DISPONIBLE), se marca no_disponible = true
 * y Componente::scopeDisponible() deja de mostrar ese producto en el
 * front, aunque siga habiendo un precio antiguo en entradas_precio.
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
        'fallos_consecutivos',
        'no_disponible',
        'ultimo_error',
    ];

    protected $casts = [
        'activo'              => 'boolean',
        'ultimo_scrape_at'    => 'datetime',
        'fallos_consecutivos' => 'integer',
        'no_disponible'       => 'boolean',
    ];

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'tienda_id');
    }

    // Registros marcados como activos manualmente (flag de configuración)
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    // Registros que el scraping todavía considera "vivos": no llevan
    // fallando N veces seguidas. Es el que consulta ScrapePrecios para
    // decidir qué reintentar (ver comentario en ScrapePrecios).
    public function scopeDisponible($query)
    {
        return $query->where('no_disponible', false);
    }
}
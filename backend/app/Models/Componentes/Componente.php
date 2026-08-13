<?php

namespace App\Models\Componentes;

use App\Models\BaseModel;

class Componente extends BaseModel
{
    protected $table = 'componentes';

    protected $fillable = [
        'nombre',
        'marca_id',
        'fabricante_id',
        'categoria',
        'modelo',
        'imagen_url',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Categorías válidas
    const CATEGORIAS = [
        'cpu',
        'gpu',
        'ram',
        'placa_base',
        'almacenamiento',
        'psu',
        'gabinete',
        'refrigeracion_aire',
        'refrigeracion_liquida',
        'ventilador',
    ];

    // Relaciones auxiliares
    public function marca()
    {
        return $this->belongsTo(\App\Models\Auxiliares\Marca::class, 'marca_id');
    }

    public function fabricante()
    {
        return $this->belongsTo(\App\Models\Auxiliares\Marca::class, 'fabricante_id');
    }

    // Relaciones con specs específicas
    public function cpu()
    {
        return $this->hasOne(CPU::class, 'componente_id');
    }

    public function gpu()
    {
        return $this->hasOne(GPU::class, 'componente_id');
    }

    public function ram()
    {
        return $this->hasOne(RAM::class, 'componente_id');
    }

    public function placaBase()
    {
        return $this->hasOne(PlacaBase::class, 'componente_id');
    }

    public function almacenamiento()
    {
        return $this->hasOne(Almacenamiento::class, 'componente_id');
    }

    public function psu()
    {
        return $this->hasOne(PSU::class, 'componente_id');
    }

    public function gabinete()
    {
        return $this->hasOne(Gabinete::class, 'componente_id');
    }

    public function refrigeracionAire()
    {
        return $this->hasOne(RefrigeracionAire::class, 'componente_id');
    }

    public function refrigeracionLiquida()
    {
        return $this->hasOne(RefrigeracionLiquida::class, 'componente_id');
    }

    // Relaciones de negocio
    public function precios()
    {
        return $this->hasMany(\App\Models\Negocio\EntradaPrecio::class, 'componente_id')
                    ->orderBy('scraped_at', 'desc');
    }

    public function ventilador()
    {
        return $this->hasOne(Ventilador::class, 'componente_id');
    }

    // Solo la entrada de precio más reciente por tienda (no el histórico completo).
    // Antes esta relación devolvía TODAS las entradas de entradas_precio del
    // componente (todo el histórico acumulado por el scraping), lo que inflaba
    // muchísimo el JSON de listados, guardados y alertas. Usamos el mismo
    // criterio que EntradaPrecio::scopeActual(): el id más alto por
    // (componente_id, tienda_id) equivale al scrape más reciente de esa tienda.
    public function preciosActuales()
    {
        return $this->hasMany(\App\Models\Negocio\EntradaPrecio::class, 'componente_id')
                    ->whereIn('id', function ($query) {
                        $query->selectRaw('MAX(id)')
                              ->from('entradas_precio')
                              ->groupBy('componente_id', 'tienda_id');
                    })
                    ->orderBy('precio', 'asc');
    }

    // Configuración del scraping para este componente (una fila por tienda).
    // Es la fuente que usa scopeDisponible() para decidir si el producto
    // se sigue mostrando en el front.
    public function urlsProductoTienda()
    {
        return $this->hasMany(\App\Models\Negocio\UrlProductoTienda::class, 'componente_id');
    }

    public function guardadoPor()
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'componentes_guardados',
            'componente_id',
            'user_id'
        )->withPivot('notas')->withTimestamps();
    }

    // Scope para filtrar por categoría
    public function scopeCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    // Scope para solo activos
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    // Solo se muestran los componentes con al menos un precio actual
    // en stock (es decir, que el scraping de esta mañana confirmó que
    // existe y se puede comprar). Si ninguna tienda lo tiene en stock
    // ahora mismo, o nunca se ha podido scrapear, se oculta.
    public function scopeDisponible($query)
    {
        return $query->whereHas('preciosActuales', fn ($q) => $q->where('en_stock', true));
    }
}
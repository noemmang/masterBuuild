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

    // Histórico CERRADO de precios (tramos ya terminados). El tramo
    // vigente ahora mismo NO está aquí, está en preciosActuales(). Esta
    // relación no la usa hoy ningún controlador (el gráfico se calcula
    // con SQL directo en PrecioController::historial()); se deja
    // disponible para depuración o futuras features.
    public function precios()
    {
        return $this->hasMany(\App\Models\Negocio\HistorialPrecio::class, 'componente_id')
                    ->orderBy('valid_from', 'desc');
    }

    public function ventilador()
    {
        return $this->hasOne(Ventilador::class, 'componente_id');
    }

    // Precio actual por tienda: una fila por (componente, tienda), ya
    // mantenida al día por ScrapePrecios (ver PrecioActual). Antes esto
    // era un whereIn(MAX(id)...) sobre entradas_precio recalculado en
    // cada petición; ahora es una lectura directa e indexada.
    public function preciosActuales()
    {
        return $this->hasMany(\App\Models\Negocio\PrecioActual::class, 'componente_id')
                    ->orderBy('precio', 'asc');
    }

    // Configuración del scraping para este componente (una fila por tienda).
    // Es la fuente que usa scopeDisponible() para decidir si el producto
    // se sigue mostrando en el front.
    public function urlsProductoTienda()
    {
        return $this->hasMany(\App\Models\Negocio\UrlProductoTienda::class, 'componente_id');
    }

    // Todas las promociones de regalo que este componente ha tenido en
    // alguna tienda, activas o no (columna "activa" en el pivot). Es la
    // relación "cruda", pensada para depurar. Para lo que debe ENSEÑAR el
    // front usa promocionesRegaloVisibles().
    public function promocionesRegalo()
    {
        return $this->belongsToMany(
            \App\Models\Negocio\PromocionRegalo::class,
            'componente_promocion_regalo',
            'componente_id',
            'promocion_regalo_id'
        )->withPivot('activa')->withTimestamps();
    }

    // Regalos que se pueden ENSEÑAR ahora mismo. Es la ÚNICA definición de
    // "regalo visible": la usan el listado (withExists → tiene_regalo, el
    // icono de la tarjeta) y el panel de tiendas (PrecioController). Un
    // regalo solo cuenta si se cumplen las tres a la vez:
    //   1. El último scrape de esa ficha de producto seguía viéndolo
    //      (pivot.activa).
    //   2. Está dentro de su vigencia (PromocionRegalo::scopeVigentes).
    //   3. La tienda a la que pertenece la promoción tiene este componente
    //      EN STOCK ahora mismo. Si esa tienda se agota, su regalo
    //      desaparece, aunque otra tienda siga teniendo stock: el regalo
    //      es de esa tienda, no del componente.
    // Como (3) mira precios_actuales por tienda_id de la propia promoción,
    // el regalo de una tienda nunca aparece asociado a otra.
    public function promocionesRegaloVisibles()
    {
        return $this->promocionesRegalo()
            ->wherePivot('activa', true)
            ->vigentes()
            ->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('precios_actuales')
                    ->whereColumn('precios_actuales.componente_id', 'componente_promocion_regalo.componente_id')
                    ->whereColumn('precios_actuales.tienda_id', 'promociones_regalo.tienda_id')
                    ->where('precios_actuales.en_stock', true);
            });
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

    // Componente con al menos una tienda con stock AHORA MISMO. Este es
    // el criterio de negocio "disponible" (no confundir con "visible" en
    // el front, ver scopeVisible): lo usa NotificacionesVerificar para
    // decidir si avisar por email de que algo se agotó o volvió a haber
    // stock, comparando este resultado noche a noche. No tocar su
    // semántica sin revisar ese comando.
    public function scopeDisponible($query)
    {
        return $query->whereHas('preciosActuales', fn ($q) => $q->where('en_stock', true));
    }

    // Componente "visible" en listados/búsqueda del front: tiene al
    // menos un precio actual registrado (se ha podido scrapear con
    // éxito alguna vez), esté o no en stock ahora mismo. Los agotados
    // se siguen mostrando con su último precio conocido (con el badge
    // "Agotado" en el front); solo se ocultan los que nunca se llegaron
    // a scrapear (num_tiendas = 0, no hay dato ninguno que mostrar).
    public function scopeVisible($query)
    {
        return $query->whereHas('preciosActuales');
    }
}
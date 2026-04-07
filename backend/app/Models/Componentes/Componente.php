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

    public function preciosActuales()
    {
        return $this->hasMany(\App\Models\Negocio\EntradaPrecio::class, 'componente_id')
                    ->orderBy('precio', 'asc');
    }

    public function cupones()
    {
        return $this->belongsToMany(
            \App\Models\Negocio\Cupon::class,
            'cupon_componente',
            'componente_id',
            'cupon_id'
        )->withTimestamps();
    }

    public function cuponesActivos()
    {
        return $this->cupones()->activos();
    }

    public function regalos()
    {
        return $this->belongsToMany(
            \App\Models\Negocio\Regalo::class,
            'regalo_componente',
            'componente_id',
            'regalo_id'
        )->withPivot([
            'tienda_id',
            'fecha_inicio',
            'fecha_expiracion',
            'activo',
        ])->withTimestamps();
    }

    public function regalosActivos()
    {
        return $this->regalos()
                    ->wherePivot('activo', true)
                    ->wherePivot('fecha_inicio', '<=', now())
                    ->where(fn($q) =>
                        $q->whereNull('regalo_componente.fecha_expiracion')
                        ->orWhere('regalo_componente.fecha_expiracion', '>=', now())
                    );
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
}
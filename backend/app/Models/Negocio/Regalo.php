<?php

namespace App\Models\Negocio;

use App\Models\BaseModel;

class Regalo extends BaseModel
{
    protected $table = 'regalos';

    protected $fillable = [
        'nombre',
        'tipo',
        'imagen_url',
        'descripcion',
        'valor_estimado',
    ];

    protected $casts = [
        'valor_estimado' => 'decimal:2',
    ];

    const TIPOS = [
        'videojuego',
        'periferico',
        'suscripcion',
        'accesorio',
        'otro',
    ];

    public function componentes()
    {
        return $this->belongsToMany(
            \App\Models\Componentes\Componente::class,
            'regalo_componente',
            'regalo_id',
            'componente_id'
        )->withPivot([
            'tienda_id',
            'fecha_inicio',
            'fecha_expiracion',
            'activo',
        ])->withTimestamps();
    }

    public function scopeActivos($query)
    {
        return $query->whereHas('componentes', fn($q) =>
            $q->where('regalo_componente.activo', true)
              ->where('regalo_componente.fecha_inicio', '<=', now())
              ->where(function ($sq) {
                  $sq->whereNull('regalo_componente.fecha_expiracion')
                     ->orWhere('regalo_componente.fecha_expiracion', '>=', now());
              })
        );
    }
}
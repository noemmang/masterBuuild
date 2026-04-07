<?php

namespace App\Models\Auxiliares;

use App\Models\BaseModel;

class Socket extends BaseModel
{
    protected $table = 'sockets';

    protected $fillable = [
        'nombre',
        'fabricante',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function cpus()
    {
        return $this->hasMany(\App\Models\Componentes\CPU::class, 'socket_id');
    }

    public function placasBases()
    {
        return $this->hasMany(\App\Models\Componentes\PlacaBase::class, 'socket_id');
    }

    public function refrigeracionesAire()
    {
        return $this->belongsToMany(
            \App\Models\Componentes\RefrigeracionAire::class,
            'refrigeracion_aire_socket',
            'socket_id',
            'refrigeracion_aire_id'
        );
    }

    public function refrigeracionesLiquidas()
    {
        return $this->belongsToMany(
            \App\Models\Componentes\RefrigeracionLiquida::class,
            'refrigeracion_liquida_socket',
            'socket_id',
            'refrigeracion_liquida_id'
        );
    }
}
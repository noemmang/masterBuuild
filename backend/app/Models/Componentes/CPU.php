<?php

namespace App\Models\Componentes;

use App\Models\BaseModel;

class CPU extends BaseModel
{
    protected $table = 'cpus';

    protected $fillable = [
        'componente_id',
        'socket_id',
        'arquitectura_id',
        'tipo_memoria_id',
        'nucleos',
        'hilos',
        'frecuencia_base_ghz',
        'frecuencia_boost_ghz',
        'tdp_watts',
        'tdp_max_watts',
        'frecuencia_memoria_max_mhz',
        'memoria_max_gb',
        'grafica_integrada',
        'nombre_grafica_integrada',
        'proceso_nm',
        'incluye_cooler',
        'overclock',
    ];

    protected $casts = [
        'nucleos'                    => 'integer',
        'hilos'                      => 'integer',
        'frecuencia_base_ghz'        => 'decimal:2',
        'frecuencia_boost_ghz'       => 'decimal:2',
        'tdp_watts'                  => 'integer',
        'tdp_max_watts'              => 'integer',
        'frecuencia_memoria_max_mhz' => 'integer',
        'memoria_max_gb'             => 'integer',
        'grafica_integrada'          => 'boolean',
        'proceso_nm'                 => 'integer',
        'incluye_cooler'             => 'boolean',
        'overclock'                  => 'boolean',
    ];

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function socket()
    {
        return $this->belongsTo(\App\Models\Auxiliares\Socket::class, 'socket_id');
    }

    public function arquitectura()
    {
        return $this->belongsTo(\App\Models\Auxiliares\Arquitectura::class, 'arquitectura_id');
    }

    public function tipoMemoria()
    {
        return $this->belongsTo(\App\Models\Auxiliares\TipoMemoria::class, 'tipo_memoria_id');
    }

    // Scope: CPUs compatibles con un socket dado
    public function scopeCompatibleConSocket($query, $socketId)
    {
        return $query->where('socket_id', $socketId);
    }

    // Scope: CPUs con TDP menor o igual a un valor (para filtrar por cooler)
    public function scopeConTdpMaximo($query, $tdp)
    {
        return $query->where('tdp_watts', '<=', $tdp);
    }
}
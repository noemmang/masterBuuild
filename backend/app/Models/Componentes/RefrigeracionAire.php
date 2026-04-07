<?php

namespace App\Models\Componentes;

use App\Models\BaseModel;

class RefrigeracionAire extends BaseModel
{
    protected $table = 'refrigeraciones_aire';

    protected $fillable = [
        'componente_id',
        'tipo_refrigeracion_id',
        'tdp_max_watts',
        'altura_mm',
        'ancho_mm',
        'profundidad_mm',
        'num_ventiladores',
        'tam_ventilador_mm',
        'rpm_min',
        'rpm_max',
        'ruido_db_min',
        'ruido_db_max',
        'num_heatpipes',
        'incluye_pasta_termica',
        'tiene_rgb',
        'disipador_dual_torre',
    ];

    protected $casts = [
        'tdp_max_watts'        => 'integer',
        'altura_mm'            => 'integer',
        'ancho_mm'             => 'integer',
        'profundidad_mm'       => 'integer',
        'num_ventiladores'     => 'integer',
        'tam_ventilador_mm'    => 'integer',
        'rpm_min'              => 'integer',
        'rpm_max'              => 'integer',
        'ruido_db_min'         => 'decimal:1',
        'ruido_db_max'         => 'decimal:1',
        'num_heatpipes'        => 'integer',
        'incluye_pasta_termica'=> 'boolean',
        'tiene_rgb'            => 'boolean',
        'disipador_dual_torre' => 'boolean',
    ];

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function tipoRefrigeracion()
    {
        return $this->belongsTo(\App\Models\Auxiliares\TipoRefrigeracion::class, 'tipo_refrigeracion_id');
    }

    public function socketsCompatibles()
    {
        return $this->belongsToMany(
            \App\Models\Auxiliares\Socket::class,
            'refrigeracion_aire_socket',
            'refrigeracion_aire_id',
            'socket_id'
        );
    }

    public function scopeCompatibleConCPU($query, $socketId, $tdpCpu)
    {
        return $query->whereHas('socketsCompatibles', fn($q) =>
            $q->where('socket_id', $socketId)
        )->where('tdp_max_watts', '>=', $tdpCpu);
    }

    public function scopeCabeEnGabinete($query, $alturaMaxMm)
    {
        return $query->where('altura_mm', '<=', $alturaMaxMm);
    }
}
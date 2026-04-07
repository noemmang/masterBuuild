<?php

namespace App\Models\Componentes;

use App\Models\BaseModel;

class Ventilador extends BaseModel
{
    protected $table = 'ventiladores';

    protected $fillable = [
        'componente_id',
        'tipo_ventilador_id',
        'rpm_min',
        'rpm_max',
        'ruido_db_min',
        'ruido_db_max',
        'flujo_aire_cfm',
        'static_pressure_mmh2o',
        'num_ventiladores',
        'tiene_rgb',
        'pwm',
        'tam_mm',
    ];

    protected $casts = [
        'rpm_min'                  => 'integer',
        'rpm_max'                  => 'integer',
        'ruido_db_min'             => 'decimal:1',
        'ruido_db_max'             => 'decimal:1',
        'flujo_aire_cfm'           => 'decimal:1',
        'static_pressure_mmh2o'    => 'decimal:2',
        'num_ventiladores'         => 'integer',
        'tiene_rgb'                => 'boolean',
        'pwm'                      => 'boolean',
    ];

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function tipoVentilador()
    {
        return $this->belongsTo(\App\Models\Auxiliares\TipoVentilador::class, 'tipo_ventilador_id');
    }
}
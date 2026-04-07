<?php

namespace App\Models\Auxiliares;

use App\Models\BaseModel;

class CertificacionPSU extends BaseModel
{
    protected $table = 'certificaciones_psu';

    protected $fillable = [
        'nombre',
        'descripcion',
        'eficiencia_minima',
        'activo',
    ];

    protected $casts = [
        'activo'             => 'boolean',
        'eficiencia_minima'  => 'decimal:2',
    ];

    public function psus()
    {
        return $this->hasMany(\App\Models\Componentes\PSU::class, 'certificacion_id');
    }
}
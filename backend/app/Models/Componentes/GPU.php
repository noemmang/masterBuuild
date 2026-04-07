<?php

namespace App\Models\Componentes;

use App\Models\BaseModel;

class GPU extends BaseModel
{
    protected $table = 'gpus';

    protected $fillable = [
        'componente_id',
        'arquitectura_id',
        'tipo_vram_id',
        'version_pcie_id',
        'vram_gb',
        'bus_bits',
        'frecuencia_base_mhz',
        'frecuencia_boost_mhz',
        'tdp_watts',
        'slots_pcie',
        'longitud_mm',
        'conectores_alimentacion',
        'psu_minima_watts',
        'salidas_video',
        'ray_tracing',
        'dlss',
        'fsr',
    ];

    protected $casts = [
        'vram_gb'                => 'integer',
        'bus_bits'               => 'integer',
        'frecuencia_base_mhz'    => 'integer',
        'frecuencia_boost_mhz'   => 'integer',
        'tdp_watts'              => 'integer',
        'slots_pcie'             => 'decimal:1',
        'longitud_mm'            => 'integer',
        'conectores_alimentacion' => 'array',
        'psu_minima_watts'       => 'integer',
        'salidas_video'          => 'array',
        'ray_tracing'            => 'boolean',
        'dlss'                   => 'boolean',
        'fsr'                    => 'boolean',
    ];

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function arquitectura()
    {
        return $this->belongsTo(\App\Models\Auxiliares\Arquitectura::class, 'arquitectura_id');
    }

    public function tipoVRAM()
    {
        return $this->belongsTo(\App\Models\Auxiliares\TipoVRAM::class, 'tipo_vram_id');
    }

    public function versionPCIe()
    {
        return $this->belongsTo(\App\Models\Auxiliares\VersionPCIe::class, 'version_pcie_id');
    }

    public function scopeCabeEnGabinete($query, $longitudMaxMm)
    {
        return $query->where('longitud_mm', '<=', $longitudMaxMm);
    }

    public function scopeCompatibleConPSU($query, $wattsDisponibles)
    {
        return $query->where('psu_minima_watts', '<=', $wattsDisponibles);
    }
}
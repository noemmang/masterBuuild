<?php

namespace App\Models\Componentes;

use App\Models\BaseModel;

class PlacaBase extends BaseModel
{
    protected $table = 'placas_base';

    protected $fillable = [
        'componente_id',
        'socket_id',
        'chipset_id',
        'factor_forma_id',
        'tipo_memoria_id',
        'version_pcie_id',
        'slots_memoria',
        'memoria_max_gb',
        'frecuencia_memoria_max_mhz',
        'slots_pcie_x16',
        'slots_pcie_x4',
        'slots_pcie_x1',
        'slots_m2',
        'puertos_sata',
        'puertos_usb_traseros',
        'conector_atx',
        'conector_cpu',
        'wifi',
        'bluetooth',
        'thunderbolt',
        'audio_chipset',
        'lan_chipset',
        'lan_velocidad_gbps',
    ];

    protected $casts = [
        'slots_memoria'              => 'integer',
        'memoria_max_gb'             => 'integer',
        'frecuencia_memoria_max_mhz' => 'integer',
        'slots_pcie_x16'             => 'integer',
        'slots_pcie_x4'              => 'integer',
        'slots_pcie_x1'              => 'integer',
        'slots_m2'                   => 'integer',
        'puertos_sata'               => 'integer',
        'puertos_usb_traseros'       => 'array',
        'wifi'                       => 'boolean',
        'bluetooth'                  => 'boolean',
        'thunderbolt'                => 'boolean',
        'lan_velocidad_gbps'         => 'decimal:1',
    ];

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function socket()
    {
        return $this->belongsTo(\App\Models\Auxiliares\Socket::class, 'socket_id');
    }

    public function chipset()
    {
        return $this->belongsTo(\App\Models\Auxiliares\Chipset::class, 'chipset_id');
    }

    public function factorForma()
    {
        return $this->belongsTo(\App\Models\Auxiliares\FactorForma::class, 'factor_forma_id');
    }

    public function tipoMemoria()
    {
        return $this->belongsTo(\App\Models\Auxiliares\TipoMemoria::class, 'tipo_memoria_id');
    }

    public function versionPCIe()
    {
        return $this->belongsTo(\App\Models\Auxiliares\VersionPCIe::class, 'version_pcie_id');
    }

    public function scopeCompatibleConCPU($query, $socketId, $tipoMemoriaId)
    {
        return $query->where('socket_id', $socketId)
                     ->where('tipo_memoria_id', $tipoMemoriaId);
    }

    public function scopeCompatibleConGabinete($query, $factoresFormaIds)
    {
        return $query->whereIn('factor_forma_id', $factoresFormaIds);
    }
}
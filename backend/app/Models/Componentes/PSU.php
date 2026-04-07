<?php

namespace App\Models\Componentes;

use App\Models\BaseModel;

class PSU extends BaseModel
{
    protected $table = 'psus';

    protected $fillable = [
        'componente_id',
        'certificacion_id',
        'tipo_psu_id',
        'vatios',
        'modular',
        'version_atx',
        'conectores_pcie_16pin',
        'conectores_pcie_8pin',
        'conectores_sata',
        'conectores_molex',
        'largo_mm',
        'ventilador_mm',
        'ventilador_zero_rpm',
    ];

    protected $casts = [
        'vatios'                 => 'integer',
        'conectores_pcie_16pin'  => 'integer',
        'conectores_pcie_8pin'   => 'integer',
        'conectores_sata'        => 'integer',
        'conectores_molex'       => 'integer',
        'largo_mm'               => 'integer',
        'ventilador_mm'          => 'integer',
        'ventilador_zero_rpm'    => 'boolean',
    ];

    const MODULAR = ['no_modular', 'semi_modular', 'full_modular'];

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function certificacion()
    {
        return $this->belongsTo(\App\Models\Auxiliares\CertificacionPSU::class, 'certificacion_id');
    }

    public function tipoPSU()
    {
        return $this->belongsTo(\App\Models\Auxiliares\TipoPSU::class, 'tipo_psu_id');
    }

    public function scopeSuficienteParaSistema($query, $consumoTotal)
    {
        // Margen de seguridad del 20%
        return $query->where('vatios', '>=', $consumoTotal * 1.2);
    }
}
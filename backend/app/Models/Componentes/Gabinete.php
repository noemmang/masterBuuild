<?php

namespace App\Models\Componentes;

use App\Models\BaseModel;
use App\Models\Auxiliares\TipoGabinete;
use App\Models\Auxiliares\EstructuraGabinete;
use App\Models\Auxiliares\FactorForma;
use App\Models\Auxiliares\TipoPSU;

class Gabinete extends BaseModel
{
    protected $table = 'gabinetes';

    protected $fillable = [
        'componente_id',
        'tipo_gabinete_id',
        'estructura_gabinete_id',
        'longitud_gpu_max_mm',
        'altura_cooler_max_mm',
        'largo_psu_max_mm',
        'bahias_35',
        'bahias_25',
        'ventiladores_frontales',
        'ventiladores_traseros',
        'ventiladores_superiores',
        'ventiladores_incluidos',
        'tam_ventilador_frontal_mm',
        'tam_ventilador_superior_mm',
        'tam_ventilador_trasero_mm',
        'soporte_radiadores',
        'puertos_usb_frontales',
        'montaje_vertical_pcie',
        'panel_frontal',
        'ancho_mm',
        'alto_mm',
        'profundidad_mm',
        'profundidad_camara_principal_mm',
        'profundidad_camara_secundaria_mm',
        'particion_min_mm',
        'particion_max_mm',
    ];

    protected $casts = [
        'soporte_radiadores'   => 'array',
        'puertos_usb_frontales'=> 'array',
        'montaje_vertical_pcie'=> 'boolean',
    ];

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function tipoGabinete()
    {
        return $this->belongsTo(TipoGabinete::class, 'tipo_gabinete_id');
    }

    public function estructuraGabinete()
    {
        return $this->belongsTo(EstructuraGabinete::class, 'estructura_gabinete_id');
    }

    public function factoresForma()
    {
        return $this->belongsToMany(FactorForma::class, 'gabinete_factor_forma', 'gabinete_id', 'factor_forma_id');
    }

    public function tiposPSU()
    {
        return $this->belongsToMany(TipoPSU::class, 'gabinete_tipo_psu', 'gabinete_id', 'tipo_psu_id');
    }
}

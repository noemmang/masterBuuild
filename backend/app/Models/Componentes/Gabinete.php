<?php

namespace App\Models\Componentes;

use App\Models\BaseModel;

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
        'longitud_gpu_max_mm'             => 'integer',
        'altura_cooler_max_mm'            => 'integer',
        'largo_psu_max_mm'                => 'integer',
        'bahias_35'                       => 'integer',
        'bahias_25'                       => 'integer',
        'ventiladores_frontales'          => 'integer',
        'ventiladores_traseros'           => 'integer',
        'ventiladores_superiores'         => 'integer',
        'ventiladores_incluidos'          => 'integer',
        'tam_ventilador_frontal_mm'       => 'integer',
        'tam_ventilador_superior_mm'      => 'integer',
        'tam_ventilador_trasero_mm'       => 'integer',
        'soporte_radiadores'              => 'array',
        'puertos_usb_frontales'           => 'array',
        'montaje_vertical_pcie'           => 'boolean',
        'ancho_mm'                        => 'integer',
        'alto_mm'                         => 'integer',
        'profundidad_mm'                  => 'integer',
        'profundidad_camara_principal_mm' => 'integer',
        'profundidad_camara_secundaria_mm'=> 'integer',
        'particion_min_mm'                => 'integer',
        'particion_max_mm'                => 'integer',
    ];

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function tipoGabinete()
    {
        return $this->belongsTo(\App\Models\Auxiliares\TipoGabinete::class, 'tipo_gabinete_id');
    }

    public function estructuraGabinete()
    {
        return $this->belongsTo(\App\Models\Auxiliares\EstructuraGabinete::class, 'estructura_gabinete_id');
    }

    public function factoresForma()
    {
        return $this->belongsToMany(
            \App\Models\Auxiliares\FactorForma::class,
            'gabinete_factor_forma',
            'gabinete_id',
            'factor_forma_id'
        );
    }

    public function tiposPSU()
    {
        return $this->belongsToMany(
            \App\Models\Auxiliares\TipoPSU::class,
            'gabinete_tipo_psu',
            'gabinete_id',
            'tipo_psu_id'
        );
    }

    public function scopeAdmitePlacaBase($query, $factorFormaId)
    {
        return $query->whereHas('factoresForma', fn($q) =>
            $q->where('factor_forma_id', $factorFormaId)
        );
    }

    public function scopeAdmiteGPU($query, $longitudMm)
    {
        return $query->where('longitud_gpu_max_mm', '>=', $longitudMm);
    }

    public function scopeAdmiteCooler($query, $alturaMm)
    {
        return $query->where('altura_cooler_max_mm', '>=', $alturaMm);
    }

    public function scopeAdmiteRadiador($query, $tamanioMm)
    {
        return $query->whereJsonContains('soporte_radiadores', $tamanioMm);
    }
}
<?php

namespace App\Models\Auxiliares;

use App\Models\BaseModel;

class TipoPSU extends BaseModel
{
    protected $table = 'tipos_psu';

    protected $fillable = [
        'nombre',
        'descripcion',
        'largo_max_mm',
        'activo',
    ];

    protected $casts = [
        'activo'      => 'boolean',
        'largo_max_mm' => 'integer',
    ];

    public function psus()
    {
        return $this->hasMany(\App\Models\Componentes\PSU::class, 'tipo_psu_id');
    }

    public function gabinetes()
    {
        return $this->belongsToMany(
            \App\Models\Componentes\Gabinete::class,
            'gabinete_tipo_psu',
            'tipo_psu_id',
            'gabinete_id'
        );
    }
}
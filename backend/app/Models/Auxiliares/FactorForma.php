<?php

namespace App\Models\Auxiliares;

use App\Models\BaseModel;

class FactorForma extends BaseModel
{
    protected $table = 'factores_forma';

    protected $fillable = [
        'nombre',
        'descripcion',
        'ancho_mm',
        'largo_mm',
        'activo',
    ];

    protected $casts = [
        'activo'   => 'boolean',
        'ancho_mm' => 'integer',
        'largo_mm' => 'integer',
    ];

    public function placasBases()
    {
        return $this->hasMany(\App\Models\Componentes\PlacaBase::class, 'factor_forma_id');
    }

    public function gabinetes()
    {
        return $this->belongsToMany(
            \App\Models\Componentes\Gabinete::class,
            'gabinete_factor_forma',
            'factor_forma_id',
            'gabinete_id'
        );
    }
}
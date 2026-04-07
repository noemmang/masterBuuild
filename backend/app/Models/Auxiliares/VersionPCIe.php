<?php

namespace App\Models\Auxiliares;

use App\Models\BaseModel;

class VersionPCIe extends BaseModel
{
    protected $table = 'versiones_pcie';

    protected $fillable = [
        'nombre',
        'descripcion',
        'ancho_banda_gbs',
        'activo',
    ];

    protected $casts = [
        'activo'           => 'boolean',
        'ancho_banda_gbs'  => 'decimal:2',
    ];

    public function gpus()
    {
        return $this->hasMany(\App\Models\Componentes\GPU::class, 'version_pcie_id');
    }

    public function placasBases()
    {
        return $this->hasMany(\App\Models\Componentes\PlacaBase::class, 'version_pcie_id');
    }
}
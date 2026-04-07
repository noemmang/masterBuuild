<?php

namespace App\Models\Auxiliares;

use App\Models\BaseModel;

class EstructuraGabinete extends BaseModel
{
    protected $table = 'estructuras_gabinete';

    protected $fillable = [
        'nombre',
        'descripcion',
        'tiene_camara_secundaria',
        'particion_ajustable',
        'activo',
    ];

    protected $casts = [
        'activo'                  => 'boolean',
        'tiene_camara_secundaria' => 'boolean',
        'particion_ajustable'     => 'boolean',
    ];

    public function gabinetes()
    {
        return $this->hasMany(\App\Models\Componentes\Gabinete::class, 'estructura_gabinete_id');
    }
}
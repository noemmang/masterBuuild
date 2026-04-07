<?php

namespace App\Models\Auxiliares;

use App\Models\BaseModel;

class TipoGabinete extends BaseModel
{
    protected $table = 'tipos_gabinete';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function gabinetes()
    {
        return $this->hasMany(\App\Models\Componentes\Gabinete::class, 'tipo_gabinete_id');
    }
}
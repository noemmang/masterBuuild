<?php

namespace App\Models\Auxiliares;

use App\Models\BaseModel;

class TipoRefrigeracion extends BaseModel
{
    protected $table = 'tipos_refrigeracion';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function refrigeracionesAire()
    {
        return $this->hasMany(\App\Models\Componentes\RefrigeracionAire::class, 'tipo_refrigeracion_id');
    }

    public function refrigeracionesLiquidas()
    {
        return $this->hasMany(\App\Models\Componentes\RefrigeracionLiquida::class, 'tipo_refrigeracion_id');
    }
}
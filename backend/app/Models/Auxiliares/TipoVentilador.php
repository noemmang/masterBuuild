<?php

namespace App\Models\Auxiliares;

use App\Models\BaseModel;

class TipoVentilador extends BaseModel
{
    protected $table = 'tipos_ventilador';

    protected $fillable = [
        'nombre',        // "Normal", "Low Profile"
        'grosor_mm',     // 25mm normal, 15mm low profile
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'grosor_mm' => 'integer',
        'activo'    => 'boolean',
    ];

    public function ventiladores()
    {
        return $this->hasMany(\App\Models\Componentes\Ventilador::class, 'tipo_ventilador_id');
    }
}
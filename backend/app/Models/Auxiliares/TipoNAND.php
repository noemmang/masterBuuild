<?php

namespace App\Models\Auxiliares;

use App\Models\BaseModel;

class TipoNAND extends BaseModel
{
    protected $table = 'tipos_nand';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function almacenamientos()
    {
        return $this->hasMany(\App\Models\Componentes\Almacenamiento::class, 'tipo_nand_id');
    }
}
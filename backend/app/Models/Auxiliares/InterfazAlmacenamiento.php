<?php

namespace App\Models\Auxiliares;

use App\Models\BaseModel;

class InterfazAlmacenamiento extends BaseModel
{
    protected $table = 'interfaces_almacenamiento';

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
        return $this->hasMany(\App\Models\Componentes\Almacenamiento::class, 'interfaz_id');
    }
}
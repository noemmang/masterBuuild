<?php

namespace App\Models\Auxiliares;

use App\Models\BaseModel;

class FactorFormaAlmacenamiento extends BaseModel
{
    protected $table = 'factores_forma_almacenamiento';

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
        return $this->hasMany(\App\Models\Componentes\Almacenamiento::class, 'factor_forma_id');
    }
}
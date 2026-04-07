<?php

namespace App\Models\Auxiliares;

use App\Models\BaseModel;

class Marca extends BaseModel
{
    protected $table = 'marcas';

    protected $fillable = [
        'nombre',
        'tipo',
        'website',
        'logo_url',
        'pais_origen',
    ];

    protected $casts = [
        'tipo' => 'array',
    ];

    public function componentes()
    {
        return $this->hasMany(\App\Models\Componentes\Componente::class, 'marca_id');
    }

    public function componentesFabricados()
    {
        return $this->hasMany(\App\Models\Componentes\Componente::class, 'fabricante_id');
    }
}
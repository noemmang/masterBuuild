<?php

namespace App\Models\Auxiliares;

use App\Models\BaseModel;

class Arquitectura extends BaseModel
{
    protected $table = 'arquitecturas';

    protected $fillable = [
        'nombre',
        'fabricante',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function cpus()
    {
        return $this->hasMany(\App\Models\Componentes\CPU::class, 'arquitectura_id');
    }

    public function gpus()
    {
        return $this->hasMany(\App\Models\Componentes\GPU::class, 'arquitectura_id');
    }
}
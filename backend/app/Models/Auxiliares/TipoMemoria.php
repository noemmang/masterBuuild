<?php

namespace App\Models\Auxiliares;

use App\Models\BaseModel;

class TipoMemoria extends BaseModel
{
    protected $table = 'tipos_memoria';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function rams()
    {
        return $this->hasMany(\App\Models\Componentes\RAM::class, 'tipo_memoria_id');
    }

    public function placasBases()
    {
        return $this->hasMany(\App\Models\Componentes\PlacaBase::class, 'tipo_memoria_id');
    }

    public function cpus()
    {
        return $this->hasMany(\App\Models\Componentes\CPU::class, 'tipo_memoria_id');
    }
}
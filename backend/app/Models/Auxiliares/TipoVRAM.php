<?php

namespace App\Models\Auxiliares;

use App\Models\BaseModel;

class TipoVRAM extends BaseModel
{
    protected $table = 'tipos_vram';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function gpus()
    {
        return $this->hasMany(\App\Models\Componentes\GPU::class, 'tipo_vram_id');
    }
}
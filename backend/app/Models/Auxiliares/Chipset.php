<?php

namespace App\Models\Auxiliares;

use App\Models\BaseModel;

class Chipset extends BaseModel
{
    protected $table = 'chipsets';

    protected $fillable = [
        'nombre',
        'fabricante',
        'socket_id',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function socket()
    {
        return $this->belongsTo(Socket::class, 'socket_id');
    }

    public function placasBases()
    {
        return $this->hasMany(\App\Models\Componentes\PlacaBase::class, 'chipset_id');
    }
}
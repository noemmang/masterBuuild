<?php

namespace App\Models\Componentes;

use App\Models\BaseModel;

class Almacenamiento extends BaseModel
{
    protected $table = 'almacenamientos';

    protected $fillable = [
        'componente_id',
        'interfaz_id',
        'factor_forma_id',
        'tipo_nand_id',
        'tipo',
        'capacidad_gb',
        'velocidad_lectura_mbs',
        'velocidad_escritura_mbs',
        'rpm',
        'cache_mb',
        'tbw',
        'cifrado',
        'dram',
    ];

    protected $casts = [
        'capacidad_gb'            => 'integer',
        'velocidad_lectura_mbs'   => 'integer',
        'velocidad_escritura_mbs' => 'integer',
        'rpm'                     => 'integer',
        'cache_mb'                => 'integer',
        'tbw'                     => 'integer',
        'cifrado'                 => 'boolean',
        'dram'                    => 'boolean',
    ];

    const TIPOS = ['ssd', 'hdd', 'nvme'];

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function interfaz()
    {
        return $this->belongsTo(\App\Models\Auxiliares\InterfazAlmacenamiento::class, 'interfaz_id');
    }

    public function factorForma()
    {
        return $this->belongsTo(\App\Models\Auxiliares\FactorFormaAlmacenamiento::class, 'factor_forma_id');
    }

    public function tipoNAND()
    {
        return $this->belongsTo(\App\Models\Auxiliares\TipoNAND::class, 'tipo_nand_id');
    }

    public function scopeNvme($query)
    {
        return $query->where('tipo', 'nvme');
    }

    public function scopeSsd($query)
    {
        return $query->where('tipo', 'ssd');
    }

    public function scopeHdd($query)
    {
        return $query->where('tipo', 'hdd');
    }
}
<?php

namespace App\Models\Componentes;

use App\Models\BaseModel;

class RAM extends BaseModel
{
    protected $table = 'rams';

    protected $fillable = [
        'componente_id',
        'tipo_memoria_id',
        'capacidad_gb',
        'modulos',
        'capacidad_total_gb',
        'velocidad_mhz',
        'latencia_cas',
        'voltaje',
        'factor_forma',
        'altura_mm',
        'tiene_rgb',
        'ecc',
        'xmp',
        'expo',
    ];

    protected $casts = [
        'capacidad_gb'       => 'integer',
        'modulos'            => 'integer',
        'capacidad_total_gb' => 'integer',
        'velocidad_mhz'      => 'integer',
        'voltaje'            => 'decimal:2',
        'altura_mm'          => 'integer',
        'tiene_rgb'          => 'boolean',
        'ecc'                => 'boolean',
        'xmp'                => 'boolean',
        'expo'               => 'boolean',
    ];

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function tipoMemoria()
    {
        return $this->belongsTo(\App\Models\Auxiliares\TipoMemoria::class, 'tipo_memoria_id');
    }

    public function scopeCompatibleConPlacaBase($query, $tipoMemoriaId, $velocidadMaxMhz)
    {
        return $query->where('tipo_memoria_id', $tipoMemoriaId)
                     ->where('velocidad_mhz', '<=', $velocidadMaxMhz);
    }

    public function scopeCabeConCooler($query, $alturaMaxMm)
    {
        return $query->where('altura_mm', '<=', $alturaMaxMm);
    }
}
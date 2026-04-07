<?php

namespace App\Models\Componentes;

use App\Models\BaseModel;

class RefrigeracionLiquida extends BaseModel
{
    protected $table = 'refrigeraciones_liquidas';

    protected $fillable = [
        'componente_id',
        'tipo_refrigeracion_id',
        'tdp_max_watts',
        // Radiador
        'tam_radiador_mm',
        'ancho_radiador_mm',
        'alto_radiador_mm',
        'grosor_radiador_mm',
        // Bomba/cabezal
        'altura_bomba_mm',
        'ancho_bomba_mm',
        'profundidad_bomba_mm',
        'pantalla_cabezal',
        // Ventiladores
        'num_ventiladores',
        'tam_ventilador_mm',
        'rpm_min',
        'rpm_max',
        'ruido_db_min',
        'ruido_db_max',
        // Extra
        'flujo_personalizable',
        'incluye_pasta_termica',
        'tiene_rgb',
    ];

    protected $casts = [
        'tdp_max_watts'         => 'integer',
        // Radiador
        'tam_radiador_mm'       => 'integer',
        'ancho_radiador_mm'     => 'integer',
        'alto_radiador_mm'      => 'integer',
        'grosor_radiador_mm'    => 'integer',
        // Bomba/cabezal
        'altura_bomba_mm'       => 'integer',
        'ancho_bomba_mm'        => 'integer',
        'profundidad_bomba_mm'  => 'integer',
        'pantalla_cabezal'      => 'boolean',
        // Ventiladores
        'num_ventiladores'      => 'integer',
        'tam_ventilador_mm'     => 'integer',
        'rpm_min'               => 'integer',
        'rpm_max'               => 'integer',
        'ruido_db_min'          => 'decimal:1',
        'ruido_db_max'          => 'decimal:1',
        // Extra
        'flujo_personalizable'  => 'boolean',
        'incluye_pasta_termica' => 'boolean',
        'tiene_rgb'             => 'boolean',
    ];

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function tipoRefrigeracion()
    {
        return $this->belongsTo(\App\Models\Auxiliares\TipoRefrigeracion::class, 'tipo_refrigeracion_id');
    }

    public function socketsCompatibles()
    {
        return $this->belongsToMany(
            \App\Models\Auxiliares\Socket::class,
            'refrigeracion_liquida_socket',
            'refrigeracion_liquida_id',
            'socket_id'
        );
    }

    public function scopeCompatibleConCPU($query, $socketId, $tdpCpu)
    {
        return $query->whereHas('socketsCompatibles', fn($q) =>
            $q->where('socket_id', $socketId)
        )->where('tdp_max_watts', '>=', $tdpCpu);
    }

    public function scopeCabeRadiadorEnGabinete($query, $tamaniosDisponibles)
    {
        return $query->whereIn('tam_radiador_mm', $tamaniosDisponibles);
    }

    // Valida que la bomba no choque con la GPU en montaje vertical
    public function scopeBombaCompatibleConGPU($query, $espacioDisponibleMm)
    {
        return $query->where('altura_bomba_mm', '<=', $espacioDisponibleMm);
    }
}
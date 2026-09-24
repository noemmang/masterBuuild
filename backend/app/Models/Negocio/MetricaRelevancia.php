<?php

namespace App\Models\Negocio;

use App\Models\Componentes\Componente;
use Illuminate\Database\Eloquent\Model;

/**
 * Puntuación de relevancia "actual" de un componente. Ver la migración
 * create_metricas_relevancia_table: es un estado derivado que
 * RelevanciaService::recalcular() regenera entero cada noche, no algo que
 * se actualice fila a fila, así que no lleva timestamps de Eloquent
 * (actualizado_en se rellena a mano en el recálculo).
 */
class MetricaRelevancia extends Model
{
    protected $table = 'metricas_relevancia';

    protected $primaryKey = 'componente_id';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'componente_id',
        'busquedas_30d',
        'selecciones_30d',
        'puntuacion',
        'actualizado_en',
    ];

    protected $casts = [
        'actualizado_en' => 'datetime',
    ];

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }
}

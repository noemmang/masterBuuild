<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaseModel extends Model
{
    use HasUuids, SoftDeletes;

    /**
     * Columnas que HasUuids debe gestionar.
     * Por defecto Laravel usa 'id', nosotros añadimos 'uuid' como campo separado.
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Atributos que no se pueden asignar masivamente en ningún modelo hijo.
     */
    protected $guarded = [
        'id',
        'uuid',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Casts comunes a todos los modelos.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Ocultar siempre el id interno en las respuestas JSON.
     * La API solo expone el uuid.
     */
    protected $hidden = [
        'id',
    ];
}
<?php

namespace App\Models\Negocio;

use App\Models\BaseModel;
use App\Models\Componentes\Componente;
use App\Models\User;

class AlertaPrecio extends BaseModel
{
    protected $table = 'alertas_precio';

    protected $fillable = [
        'user_id',
        'componente_id',
        'precio_objetivo',
        'activa',
        'disparada_en',
    ];

    protected $casts = [
        'precio_objetivo' => 'decimal:2',
        'activa'          => 'boolean',
        'disparada_en'    => 'datetime',
    ];

    public function usuario() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function componente() {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function scopeDelUsuario($query, $userId) {
        return $query->where('user_id', $userId)
                     ->with(['componente.marca', 'componente.preciosActuales.tienda']);
    }

    public function estaDisparada(): bool {
        return $this->disparada_en !== null;
    }
}
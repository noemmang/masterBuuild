<?php

namespace App\Models\Negocio;

use App\Models\BaseModel;
use App\Models\Componentes\Componente;
use App\Models\User;

class ComponenteGuardado extends BaseModel
{
    protected $table = 'componentes_guardados';

    protected $fillable = [
        'user_id',
        'componente_id',
        'notas',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    // Todos los guardados de un usuario con sus precios actuales
    public function scopeDelUsuario($query, $userId)
    {
        return $query->where('user_id', $userId)
                     ->with(['componente.marca', 'componente.preciosActuales.tienda']);
    }
}
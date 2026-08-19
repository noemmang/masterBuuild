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
        'notificado_agotado_en',
    ];

    protected $casts = [
        'notificado_agotado_en' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Igual que AlertaPrecio::estaDisparada(): si tiene fecha, ya se avisó
    // de que este guardado se agotó/desapareció y todavía no ha vuelto.
    public function notificadoComoAgotado(): bool
    {
        return $this->notificado_agotado_en !== null;
    }

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    // Todos los guardados de un usuario con sus precios actuales.
    public function scopeDelUsuario($query, $userId)
    {
        return $query->where('user_id', $userId)
                     ->with([
                         'componente.marca',
                         'componente.preciosActuales.tienda',
                     ]);
    }
}
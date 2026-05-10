<?php

namespace App\Models\Negocio;

use App\Models\BaseModel;
use App\Models\User;

class ConfiguracionGuardada extends BaseModel
{
    protected $table = 'configuraciones_guardadas';

    protected $fillable = [
        'user_id',
        'nombre',
        'notas',
        'total',
        'compatible',
        'slots',
    ];

    protected $casts = [
        'slots'      => 'array',
        'compatible' => 'boolean',
        'total'      => 'float',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeDelUsuario($query, $userId)
    {
        return $query->where('user_id', $userId)->orderByDesc('created_at');
    }
}
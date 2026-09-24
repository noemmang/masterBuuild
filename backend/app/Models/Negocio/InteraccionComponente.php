<?php

namespace App\Models\Negocio;

use App\Models\Componentes\Componente;
use Illuminate\Database\Eloquent\Model;

/**
 * Un evento de interacción con un componente. Ver la migración
 * create_interacciones_componente_table para el porqué de la tabla; este
 * modelo existe sobre todo para dar nombre a los dos tipos válidos y para
 * la relación `componente()` — los inserts en caliente desde
 * InteraccionController van por DB::table() (inserción en lote, sin
 * necesidad de hidratar modelos).
 */
class InteraccionComponente extends Model
{
    protected $table = 'interacciones_componente';

    public $timestamps = false;

    protected $fillable = [
        'componente_id',
        'tipo',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public const TIPO_BUSQUEDA = 'busqueda';
    public const TIPO_SELECCION = 'seleccion';

    public function componente()
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }
}

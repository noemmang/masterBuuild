<?php

namespace App\Http\Controllers\Api\Negocio;

use App\Http\Controllers\Controller;
use App\Models\Componentes\Componente;
use App\Models\Negocio\ComponenteGuardado;
use Illuminate\Http\Request;

class GuardadoController extends Controller
{
    // Listar componentes guardados del usuario autenticado
    public function index(Request $request)
    {
        $guardados = ComponenteGuardado::delUsuario($request->user()->id)
            ->get()
            ->map(fn($g) => [
                'uuid'       => $g->uuid,
                'notas'      => $g->notas,
                'guardado_en'=> $g->created_at->format('Y-m-d H:i'),
                'componente' => [
                    'uuid'          => $g->componente->uuid,
                    'nombre'        => $g->componente->nombre,
                    'categoria'     => $g->componente->categoria,
                    'imagen_url'    => $g->componente->imagen_url,
                    'marca'         => $g->componente->marca->nombre,
                    'mejor_precio'  => $g->tienda_id
                        ? ($g->componente->preciosActuales->firstWhere('tienda_id', $g->tienda_id)?->precioEfectivo()
                          ?? $g->componente->preciosActuales->sortBy('precio')->first()?->precioEfectivo())
                        : $g->componente->preciosActuales->sortBy('precio')->first()?->precioEfectivo(),
                    'tienda'        => $g->tienda_id
                        ? ($g->componente->preciosActuales->firstWhere('tienda_id', $g->tienda_id)?->tienda->nombre
                          ?? $g->componente->preciosActuales->sortBy('precio')->first()?->tienda->nombre)
                        : $g->componente->preciosActuales->sortBy('precio')->first()?->tienda->nombre,
                    'con_cupon'     => $g->componente->cuponesActivos->isNotEmpty(),
                    'con_regalo'    => $g->componente->regalosActivos->isNotEmpty(),
                ],
            ]);

        return response()->json($guardados);
    }

    // Guardar un componente
    public function store(Request $request)
    {
        $data = $request->validate([
            'componente_uuid' => 'required|string',
            'notas'           => 'nullable|string|max:500',
            'tienda_uuid'     => 'nullable|string',
        ]);

        $componente = Componente::where('uuid', $data['componente_uuid'])
            ->activo()
            ->firstOrFail();

        // Verificar si ya está guardado
        $existe = ComponenteGuardado::where('user_id', $request->user()->id)
            ->where('componente_id', $componente->id)
            ->exists();

        if ($existe) {
            return response()->json([
                'message' => 'Este componente ya está en tu lista de guardados',
            ], 422);
        }

        // Resolver tienda si se proporcionó uuid
        $tiendaId = null;
        if (!empty($data['tienda_uuid'])) {
            $tienda = \App\Models\Negocio\Tienda::where('uuid', $data['tienda_uuid'])->first();
            $tiendaId = $tienda?->id;
        }

        $guardado = ComponenteGuardado::create([
            'user_id'      => $request->user()->id,
            'componente_id'=> $componente->id,
            'tienda_id'    => $tiendaId,
            'notas'        => $data['notas'] ?? null,
        ]);

        return response()->json([
            'message' => 'Componente guardado correctamente',
            'uuid'    => $guardado->uuid,
        ], 201);
    }

    // Actualizar notas de un guardado
    public function update(Request $request, string $uuid)
    {
        $guardado = ComponenteGuardado::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $data = $request->validate([
            'notas' => 'nullable|string|max:500',
        ]);

        $guardado->update(['notas' => $data['notas']]);

        return response()->json([
            'message' => 'Notas actualizadas correctamente',
        ]);
    }

    // Eliminar un guardado
    public function destroy(Request $request, string $uuid)
    {
        $guardado = ComponenteGuardado::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $guardado->forceDelete(); // ← en lugar de delete()

        return response()->json([
            'message' => 'Componente eliminado de guardados',
        ]);
    }
}
<?php

namespace App\Http\Controllers\Api\Negocio;

use App\Http\Controllers\Controller;
use App\Models\Negocio\ConfiguracionGuardada;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    // Listar configuraciones del usuario
    public function index(Request $request)
    {
        $configuraciones = ConfiguracionGuardada::delUsuario($request->user()->id)
            ->get()
            ->map(fn($c) => [
                'uuid'       => $c->uuid,
                'nombre'     => $c->nombre,
                'notas'      => $c->notas,
                'total'      => $c->total,
                'compatible' => $c->compatible,
                'creada_en'  => $c->created_at->format('d/m/Y'),
                'slots'      => $c->slots,
            ]);

        return response()->json($configuraciones);
    }

    // Obtener una configuración concreta por uuid (para restaurarla en el
    // configurador sin tener que pedir el listado completo del usuario)
    public function show(Request $request, string $uuid)
    {
        $c = ConfiguracionGuardada::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'uuid'       => $c->uuid,
            'nombre'     => $c->nombre,
            'notas'      => $c->notas,
            'total'      => $c->total,
            'compatible' => $c->compatible,
            'creada_en'  => $c->created_at->format('d/m/Y'),
            'slots'      => $c->slots,
        ]);
    }

    // Guardar una configuración nueva
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'     => 'required|string|max:80',
            'notas'      => 'nullable|string|max:500',
            'total'      => 'required|numeric|min:0',
            'compatible' => 'required|boolean',
            'slots'      => 'required|array|min:1',
            'slots.*.categoria'   => 'required|string',
            'slots.*.label'       => 'required|string',
            'slots.*.componentes' => 'required|array|min:1',
            'slots.*.componentes.*.uuid'     => 'required|string',
            'slots.*.componentes.*.nombre'   => 'required|string',
            'slots.*.componentes.*.cantidad' => 'required|integer|min:1',
            'slots.*.componentes.*.precio'   => 'nullable|numeric',
        ]);

        $config = ConfiguracionGuardada::create([
            'user_id'    => $request->user()->id,
            'nombre'     => $data['nombre'],
            'notas'      => $data['notas'] ?? null,
            'total'      => $data['total'],
            'compatible' => $data['compatible'],
            'slots'      => $data['slots'],
        ]);

        return response()->json([
            'message' => 'Configuración guardada correctamente',
            'uuid'    => $config->uuid,
        ], 201);
    }

    // Actualizar notas de una configuración
    public function update(Request $request, string $uuid)
    {
        $config = ConfiguracionGuardada::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $data = $request->validate([
            'notas' => 'nullable|string|max:500',
        ]);

        $config->update(['notas' => $data['notas']]);

        return response()->json(['message' => 'Notas actualizadas correctamente']);
    }

    // Eliminar una configuración
    public function destroy(Request $request, string $uuid)
    {
        $config = ConfiguracionGuardada::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $config->forceDelete();

        return response()->json(['message' => 'Configuración eliminada correctamente']);
    }
}
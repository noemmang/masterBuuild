<?php

namespace App\Http\Controllers\Api\Negocio;

use App\Http\Controllers\Controller;
use App\Models\Componentes\Componente;
use App\Models\Negocio\AlertaPrecio;
use Illuminate\Http\Request;

class AlertaController extends Controller
{
    public function index(Request $request)
    {
        $alertas = AlertaPrecio::delUsuario($request->user()->id)
            ->get()
            ->map(fn($a) => [
                'uuid'            => $a->uuid,
                'activa'          => $a->activa,
                'precio_objetivo' => (float) $a->precio_objetivo,
                'disparada'       => $a->estaDisparada(),
                'disparada_en'    => $a->disparada_en?->format('Y-m-d H:i'),
                'componente' => [
                    'uuid'         => $a->componente->uuid,
                    'nombre'       => $a->componente->nombre,
                    'categoria'    => $a->componente->categoria,
                    'imagen_url'   => $a->componente->imagen_url,
                    'precio_actual'=> $a->componente->preciosActuales
                        ->sortBy('precio')->first()?->precio,
                    'tienda'       => $a->componente->preciosActuales
                        ->sortBy('precio')->first()?->tienda->nombre,
                ],
            ]);

        return response()->json($alertas);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'componente_uuid'  => 'required|string',
            'precio_objetivo'  => 'required|numeric|min:0',
        ]);

        $componente = Componente::where('uuid', $data['componente_uuid'])
            ->activo()->firstOrFail();

        $alerta = AlertaPrecio::updateOrCreate(
            ['user_id' => $request->user()->id, 'componente_id' => $componente->id],
            ['precio_objetivo' => $data['precio_objetivo'], 'activa' => true, 'disparada_en' => null]
        );

        return response()->json([
            'message' => 'Alerta creada correctamente',
            'uuid'    => $alerta->uuid,
        ], 201);
    }

    public function update(Request $request, string $uuid)
    {
        $alerta = AlertaPrecio::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $data = $request->validate([
            'activa'          => 'sometimes|boolean',
            'precio_objetivo' => 'sometimes|numeric|min:0',
        ]);

        $alerta->update($data);

        return response()->json(['message' => 'Alerta actualizada']);
    }

    public function destroy(Request $request, string $uuid)
    {
        $alerta = AlertaPrecio::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $alerta->forceDelete(); // ← en lugar de delete()

        return response()->json(['message' => 'Alerta eliminada']);
    }
}
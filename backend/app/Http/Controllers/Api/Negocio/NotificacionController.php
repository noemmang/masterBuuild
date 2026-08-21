<?php

namespace App\Http\Controllers\Api\Negocio;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Sirve el panel de notificaciones del header (la campanita, estilo el
 * panel de notificaciones de Azure). No envía nada nuevo: solo lee lo
 * que las notificaciones ya existentes (CuentaCreadaNotification,
 * ComponenteAgotadoNotification, ComponenteDisponibleNotification,
 * AlertaPrecioAlcanzadaNotification) guardan en la tabla estándar de
 * Laravel `notifications` a través de su canal 'database'.
 *
 * CuentaEliminadaNotification no aparece aquí porque nunca pasa por el
 * canal 'database' (ver el comentario en esa clase).
 */
class NotificacionController extends Controller
{
    // Listado paginado del usuario autenticado, más recientes primero,
    // junto con el número de no leídas (para pintar el contador aunque
    // esté fuera de la primera página).
    public function index(Request $request)
    {
        $usuario = $request->user();

        $porPagina  = min((int) $request->query('por_pagina', 20), 50);
        $paginador  = $usuario->notifications()->paginate($porPagina);

        return response()->json([
            'data'       => $paginador->getCollection()->map(fn($n) => $this->formatear($n)),
            'no_leidas'  => $usuario->unreadNotifications()->count(),
            'pagina'     => $paginador->currentPage(),
            'ultima_pagina' => $paginador->lastPage(),
            'total'      => $paginador->total(),
        ]);
    }

    // Endpoint ligero para el sondeo periódico del contador de la
    // campanita: evita traer la lista entera solo para pintar el badge.
    public function contador(Request $request)
    {
        return response()->json([
            'no_leidas' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    // Marcar una notificación como leída (p.ej. al pulsarla para navegar).
    public function marcarLeida(Request $request, string $id)
    {
        $notificacion = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notificacion->markAsRead();

        return response()->json(['message' => 'Notificación marcada como leída']);
    }

    // Marcar todas como leídas (botón "Marcar todo como leído").
    public function marcarTodasLeidas(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Todas las notificaciones marcadas como leídas']);
    }

    // Descartar (eliminar) una notificación concreta.
    public function destroy(Request $request, string $id)
    {
        $notificacion = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notificacion->delete();

        return response()->json(['message' => 'Notificación descartada']);
    }

    // Descartar todas (botón "Descartar todo", como en Azure).
    public function destroyTodas(Request $request)
    {
        $request->user()->notifications()->delete();

        return response()->json(['message' => 'Notificaciones descartadas']);
    }

    private function formatear($notificacion): array
    {
        $data = $notificacion->data;

        return [
            'id'         => $notificacion->id,
            'tipo'       => $data['tipo']    ?? null,
            'titulo'     => $data['titulo']  ?? '',
            'mensaje'    => $data['mensaje'] ?? '',
            'url'        => $data['url']     ?? null,
            'imagen'     => $data['imagen']  ?? null,
            'leida'      => $notificacion->read_at !== null,
            'creada_en'  => $notificacion->created_at?->toIso8601String(),
        ];
    }
}
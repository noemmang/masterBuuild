<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Negocio\AuthController;
use App\Http\Controllers\Api\Componentes\ComponenteController;
use App\Http\Controllers\Api\Componentes\GabineteController;
use App\Http\Controllers\Api\Negocio\PrecioController;
use App\Http\Controllers\Api\Negocio\GuardadoController;
use App\Http\Controllers\Api\Negocio\AlertaController;
use App\Http\Controllers\Api\Negocio\ConfiguracionController;
use App\Http\Controllers\Api\Negocio\NotificacionController;
use App\Http\Controllers\Api\Auxiliares\AuxiliaresController;
use App\Http\Controllers\Api\Configurador\ConfiguradorController;
use App\Http\Controllers\Api\Configurador\RecomendadorController;

Route::prefix('v1')->group(function () {

    // ── Auth — públicas ───────────────────────────────────────
    //
    // throttle:auth limita a 10 intentos/min por IP (ver RateLimiter::for('auth')
    // en AppServiceProvider). Antes estos endpoints no tenían ningún límite:
    // el grupo 'api' de Laravel 11+/13 trae 'throttle:api' comentado por
    // defecto, así que login/register estaban completamente expuestos a
    // fuerza bruta de contraseñas y a registro masivo automatizado.
    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login',    [AuthController::class, 'login']);
    });

    // ── Catálogos auxiliares — públicos ───────────────────────
    Route::get('auxiliares', [AuxiliaresController::class, 'index']);

    // ── Componentes — lectura pública ─────────────────────────
    Route::prefix('componentes')->group(function () {
        Route::get('/',                        [ComponenteController::class, 'index']);
        Route::get('{uuid}',                   [ComponenteController::class, 'show']);
        Route::get('categoria/{categoria}',    [ComponenteController::class, 'porCategoria']);
        Route::get('{uuid}/precios',           [PrecioController::class, 'actuales']);
        Route::get('{uuid}/precios/historial', [PrecioController::class, 'historial']);
        Route::get('{uuid}/gabinete/visor',    [GabineteController::class, 'visor']);
    });

    // ── Configurador — público ────────────────────────────────
    Route::prefix('configurador')->group(function () {
        Route::post('validar',   [ConfiguradorController::class, 'validar']);
        // RecomendadorController existía completo (perfiles, presupuestos,
        // toda la lógica de "constrúyeme un PC") pero no tenía ruta
        // asignada en ningún sitio: era código muerto, inalcanzable desde
        // fuera. Se expone aquí ahora que ya comparte las mismas reglas de
        // compatibilidad placa↔gabinete y psu↔gabinete que el resto.
        Route::post('recomendar', [RecomendadorController::class, 'recomendar']);
    });

    // ── Rutas protegidas ──────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post  ('auth/logout',   [AuthController::class, 'logout']);
        Route::get   ('auth/me',       [AuthController::class, 'me']);
        Route::patch ('auth/me',       [AuthController::class, 'updateMe']);
        Route::patch ('auth/password', [AuthController::class, 'updatePassword']);
        Route::delete('auth/me',       [AuthController::class, 'destroyMe']);

        // Guardados
        Route::prefix('guardados')->group(function () {
            Route::get    ('/',      [GuardadoController::class, 'index']);
            Route::post   ('/',      [GuardadoController::class, 'store']);
            Route::patch  ('{uuid}', [GuardadoController::class, 'update']);
            Route::delete ('{uuid}', [GuardadoController::class, 'destroy']);
        });

        // Alertas de precio
        Route::prefix('alertas')->group(function () {
            Route::get    ('/',      [AlertaController::class, 'index']);
            Route::post   ('/',      [AlertaController::class, 'store']);
            Route::patch  ('{uuid}', [AlertaController::class, 'update']);
            Route::delete ('{uuid}', [AlertaController::class, 'destroy']);
        });

        // Configuraciones guardadas
        Route::prefix('configuraciones')->group(function () {
            Route::get    ('/',      [ConfiguracionController::class, 'index']);
            Route::get    ('{uuid}', [ConfiguracionController::class, 'show']);
            Route::post   ('/',      [ConfiguracionController::class, 'store']);
            Route::patch  ('{uuid}', [ConfiguracionController::class, 'update']);
            Route::delete ('{uuid}', [ConfiguracionController::class, 'destroy']);
        });

        // Notificaciones (campanita del header)
        Route::prefix('notificaciones')->group(function () {
            Route::get    ('/',              [NotificacionController::class, 'index']);
            Route::get    ('contador',       [NotificacionController::class, 'contador']);
            Route::patch  ('leer-todas',     [NotificacionController::class, 'marcarTodasLeidas']);
            Route::patch  ('{id}/leer',      [NotificacionController::class, 'marcarLeida']);
            Route::delete ('/',              [NotificacionController::class, 'destroyTodas']);
            Route::delete ('{id}',           [NotificacionController::class, 'destroy']);
        });
    });
});
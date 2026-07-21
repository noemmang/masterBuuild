<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Negocio\AuthController;
use App\Http\Controllers\Api\Componentes\ComponenteController;
use App\Http\Controllers\Api\Componentes\GabineteController;
use App\Http\Controllers\Api\Negocio\PrecioController;
use App\Http\Controllers\Api\Negocio\GuardadoController;
use App\Http\Controllers\Api\Negocio\AlertaController;
use App\Http\Controllers\Api\Negocio\CuponController;
use App\Http\Controllers\Api\Negocio\ConfiguracionController;
use App\Http\Controllers\Api\Auxiliares\AuxiliaresController;
use App\Http\Controllers\Api\Configurador\ConfiguradorController;
use App\Http\Controllers\Api\Configurador\RecomendadorController;
use App\Http\Controllers\Api\Negocio\RegalosController;

Route::prefix('v1')->group(function () {

    // ── Auth — públicas ───────────────────────────────────────
    Route::prefix('auth')->group(function () {
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
        Route::get('{uuid}/regalos',           [RegalosController::class, 'porComponente']);
        Route::get('{uuid}/gabinete/visor',    [GabineteController::class, 'visor']);
    });

    // ── Configurador — público ────────────────────────────────
    Route::prefix('configurador')->group(function () {
        Route::post('validar',    [ConfiguradorController::class, 'validar']);
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
            Route::post   ('/',      [ConfiguracionController::class, 'store']);
            Route::patch  ('{uuid}', [ConfiguracionController::class, 'update']);
            Route::delete ('{uuid}', [ConfiguracionController::class, 'destroy']);
        });
    });
});
<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Límite para login/register: por defecto el grupo 'api' de Laravel
        // 11+/13 trae 'throttle:api' comentado, así que sin esto estos
        // endpoints no tenían ningún límite de peticiones (riesgo de fuerza
        // bruta de contraseñas y de registro masivo automatizado).
        // 10 intentos por minuto por IP es suficiente para un usuario real
        // que se equivoca de contraseña varias veces, pero frena un ataque.
        RateLimiter::for('auth', function ($request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Señales de relevancia (POST /interacciones/*, ver
        // InteraccionController): son públicas y de escritura, así que sin
        // límite serían un vector fácil para inflar la puntuación de un
        // componente a base de peticiones repetidas. 120/min por IP deja
        // margen de sobra para el uso real (una llamada por búsqueda con
        // texto tecleada + una por cada tarjeta abierta) sin permitir un
        // bombardeo automatizado.
        RateLimiter::for('interacciones', function ($request) {
            return Limit::perMinute(120)->by($request->ip());
        });
    }
}
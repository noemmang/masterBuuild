<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ScrapeController extends Controller
{
    public function ejecutar(Request $request)
    {
        $claveRecibida = (string) $request->header('X-Scrape-Key');
        $claveEsperada = (string) config('services.scrape.clave');

        // hash_equals en vez de === : compara en tiempo constante,
        // para que un atacante no pueda deducir la clave midiendo
        // cuánto tarda la respuesta según cuántos caracteres acierta.
        if ($claveEsperada === '' || !hash_equals($claveEsperada, $claveRecibida)) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $exitCode = Artisan::call('scrape:precios', [
            '--pausa-min' => 1,
            '--pausa-max' => 2,
        ]);

        return response()->json([
            'ok' => $exitCode === 0,
            'salida' => Artisan::output(),
        ]);
    }
}
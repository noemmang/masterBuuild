<?php

namespace App\Services\Scraping;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Avisos del scraping. Antes, cuando una tienda bloqueaba la IP de Azure, el
 * único rastro era un Job "Failed" en el portal y productos con precios
 * antiguos que seguían pareciendo normales: nadie se enteraba durante semanas.
 *
 * Canales (todos opcionales, ver config/scraper.php):
 *  - Log (siempre): nivel error → stderr → Log Analytics de Azure.
 *  - Webhook de Discord o Slack.
 *  - Correo.
 *  - Latido (healthchecks.io o similar): avisa aunque el Job ni siquiera
 *    llegue a arrancar, se cuelgue o lo mate Azure por timeout.
 *
 * Nada de lo que hay aquí puede lanzar una excepción: avisar jamás debe
 * romper el scraping.
 */
class AlertaScraping
{
    /** @param string[] $lineas */
    public function alertar(string $titulo, array $lineas): void
    {
        $texto = "⚠️ {$titulo}\n".implode("\n", array_map(static fn ($l) => "• {$l}", $lineas));

        Log::error('[scraping] '.str_replace("\n", ' | ', $texto));

        $webhook = (string) config('scraper.alert_webhook');
        if ($webhook !== '') {
            try {
                // "content" lo lee Discord y "text" lo lee Slack; cada uno
                // ignora el campo del otro.
                $corto = mb_substr($texto, 0, 1900);
                Http::timeout(10)->post($webhook, ['content' => $corto, 'text' => $corto]);
            } catch (Throwable $e) {
                Log::warning('[scraping] no se pudo enviar el webhook de alerta: '.$e->getMessage());
            }
        }

        $correo = (string) config('scraper.alert_email');
        if ($correo !== '') {
            try {
                Mail::raw($texto, static fn ($m) => $m->to($correo)->subject('[MasterBuild] '.$titulo));
            } catch (Throwable $e) {
                Log::warning('[scraping] no se pudo enviar el correo de alerta: '.$e->getMessage());
            }
        }
    }

    /**
     * Ping al servicio de latido. $estado: "start" | "ok" | "fail".
     * Compatible con healthchecks.io (/start y /fail se añaden a la URL base).
     */
    public function latido(string $estado = 'ok'): void
    {
        $base = rtrim((string) config('scraper.heartbeat_url'), '/');

        if ($base === '') {
            return;
        }

        $url = match ($estado) {
            'start' => $base.'/start',
            'fail'  => $base.'/fail',
            default => $base,
        };

        try {
            Http::timeout(10)->retry(2, 500, throw: false)->get($url);
        } catch (Throwable $e) {
            Log::warning("[scraping] no se pudo enviar el latido ({$estado}): ".$e->getMessage());
        }
    }
}

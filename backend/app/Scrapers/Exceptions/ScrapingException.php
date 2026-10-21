<?php

namespace App\Scrapers\Exceptions;

use Throwable;

class ScrapingException extends \RuntimeException
{
    private bool $bloqueo = false;
    private ?int $codigoHttp = null;

    /**
     * La tienda ha rechazado la petición a propósito (403/429: WAF,
     * detección de bots, límite de peticiones...). NO es un fallo del
     * producto concreto: ScrapePrecios no debe contarlo como fallo
     * consecutivo ni ocultar el producto del front por ello.
     */
    public static function bloqueo(string $mensaje, int $codigoHttp, ?Throwable $previous = null): static
    {
        $e = new static($mensaje, 0, $previous);
        $e->bloqueo = true;
        $e->codigoHttp = $codigoHttp;

        return $e;
    }

    public function esBloqueo(): bool
    {
        return $this->bloqueo;
    }

    public function codigoHttp(): ?int
    {
        return $this->codigoHttp;
    }
}
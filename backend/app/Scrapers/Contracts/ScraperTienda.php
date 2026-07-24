<?php

namespace App\Scrapers\Contracts;

use App\Scrapers\DTO\DatoScrapeado;
use App\Scrapers\Exceptions\ScrapingException;

interface ScraperTienda
{
    /**
     * Descarga la URL de un producto y extrae precio, stock, moneda, etc.
     *
     * @throws ScrapingException si no se puede descargar o interpretar la página.
     */
    public function extraerDatos(string $url): DatoScrapeado;
}

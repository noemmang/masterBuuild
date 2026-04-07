<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Negocio\Tienda;

class TiendasSeeder extends Seeder
{
    public function run(): void
    {
        $tiendas = [
            [
                'nombre'       => 'PCComponentes',
                'url'          => 'https://www.pccomponentes.com',
                'logo_url'     => null,
                'clase_scraper'=> 'App\Scrapers\PcComponentesScraper',
                'url_afiliado' => null,
                'pais'         => 'ES',
                'moneda'       => 'EUR',
                'activo'       => true,
            ],
            [
                'nombre'       => 'Amazon España',
                'url'          => 'https://www.amazon.es',
                'logo_url'     => null,
                'clase_scraper'=> 'App\Scrapers\AmazonScraper',
                'url_afiliado' => null,
                'pais'         => 'ES',
                'moneda'       => 'EUR',
                'activo'       => true,
            ],
            [
                'nombre'       => 'MediaMarkt',
                'url'          => 'https://www.mediamarkt.es',
                'logo_url'     => null,
                'clase_scraper'=> 'App\Scrapers\MediaMarktScraper',
                'url_afiliado' => null,
                'pais'         => 'ES',
                'moneda'       => 'EUR',
                'activo'       => true,
            ],
            [
                'nombre'       => 'El Corte Inglés',
                'url'          => 'https://www.elcorteingles.es',
                'logo_url'     => null,
                'clase_scraper'=> 'App\Scrapers\ElCorteInglesScraper',
                'url_afiliado' => null,
                'pais'         => 'ES',
                'moneda'       => 'EUR',
                'activo'       => true,
            ],
            [
                'nombre'       => 'Coolmod',
                'url'          => 'https://www.coolmod.com',
                'logo_url'     => null,
                'clase_scraper'=> 'App\Scrapers\CoolmodScraper',
                'url_afiliado' => null,
                'pais'         => 'ES',
                'moneda'       => 'EUR',
                'activo'       => true,
            ],
            [
                'nombre'       => 'Alternate',
                'url'          => 'https://www.alternate.es',
                'logo_url'     => null,
                'clase_scraper'=> 'App\Scrapers\AlternateScraper',
                'url_afiliado' => null,
                'pais'         => 'ES',
                'moneda'       => 'EUR',
                'activo'       => true,
            ],
            [
                'nombre'       => 'Newegg',
                'url'          => 'https://www.newegg.com',
                'logo_url'     => null,
                'clase_scraper'=> 'App\Scrapers\NeweggScraper',
                'url_afiliado' => null,
                'pais'         => 'US',
                'moneda'       => 'USD',
                'activo'       => false,
            ],
        ];

        foreach ($tiendas as $tienda) {
            Tienda::create($tienda);
        }
    }
}
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
                'nombre'        => 'PCComponentes',
                'url'           => 'https://www.pccomponentes.com',
                'logo_url'      => null,
                'clase_scraper' => 'App\Scrapers\PcComponentesScraper',
                'url_afiliado'  => null,
                'pais'          => 'ES',
                'moneda'        => 'EUR',
                'activo'        => true,
            ],
            [
                'nombre'        => 'Amazon España',
                'url'           => 'https://www.amazon.es',
                'logo_url'      => null,
                'clase_scraper' => 'App\Scrapers\AmazonScraper',
                'url_afiliado'  => null,
                'pais'          => 'ES',
                'moneda'        => 'EUR',
                'activo'        => true,
            ],
            [
                'nombre'        => 'MediaMarkt',
                'url'           => 'https://www.mediamarkt.es',
                'logo_url'      => null,
                'clase_scraper' => 'App\Scrapers\MediaMarktScraper',
                'url_afiliado'  => null,
                'pais'          => 'ES',
                'moneda'        => 'EUR',
                'activo'        => true,
            ],
            [
                'nombre'        => 'Coolmod',
                'url'           => 'https://www.coolmod.com',
                'logo_url'      => null,
                'clase_scraper' => 'App\Scrapers\CoolmodScraper',
                'url_afiliado'  => null,
                'pais'          => 'ES',
                'moneda'        => 'EUR',
                'activo'        => true,
            ],
            [
                'nombre'        => 'Alternate',
                'url'           => 'https://www.alternate.es',
                'logo_url'      => null,
                'clase_scraper' => 'App\Scrapers\AlternateScraper',
                'url_afiliado'  => null,
                'pais'          => 'ES',
                'moneda'        => 'EUR',
                'activo'        => true,
            ],
            [
                'nombre'        => 'Neobyte',
                'url'           => 'https://www.neobyte.es',
                'logo_url'      => null,
                'clase_scraper' => 'App\Scrapers\NeobyteScraper',
                'url_afiliado'  => null,
                'pais'          => 'ES',
                'moneda'        => 'EUR',
                'activo'        => true,
            ],
            [
                'nombre'        => 'PcBox',
                'url'           => 'https://www.pcbox.com',
                'logo_url'      => null,
                'clase_scraper' => 'App\Scrapers\PcBoxScraper',
                'url_afiliado'  => null,
                'pais'          => 'ES',
                'moneda'        => 'EUR',
                'activo'        => true,
            ],
            [
                'nombre'        => 'Red Computer',
                'url'           => 'https://www.redcomputer.es',
                'logo_url'      => null,
                'clase_scraper' => 'App\Scrapers\RedComputerScraper',
                'url_afiliado'  => null,
                'pais'          => 'ES',
                'moneda'        => 'EUR',
                'activo'        => true,
            ],
            [
                'nombre'        => 'Info Computer',
                'url'           => 'https://www.infocomputer.es',
                'logo_url'      => null,
                'clase_scraper' => 'App\Scrapers\InfoComputerScraper',
                'url_afiliado'  => null,
                'pais'          => 'ES',
                'moneda'        => 'EUR',
                'activo'        => true,
            ],
            [
                'nombre'        => 'Life Informática',
                'url'           => 'https://www.lifeinformatica.com',
                'logo_url'      => null,
                'clase_scraper' => 'App\Scrapers\LifeInformaticaScraper',
                'url_afiliado'  => null,
                'pais'          => 'ES',
                'moneda'        => 'EUR',
                'activo'        => true,
            ],
            [
                'nombre'        => 'FNAC',
                'url'           => 'https://www.fnac.es',
                'logo_url'      => null,
                'clase_scraper' => 'App\Scrapers\FnacScraper',
                'url_afiliado'  => null,
                'pais'          => 'ES',
                'moneda'        => 'EUR',
                'activo'        => true,
            ],
            [
                'nombre'        => 'Worten',
                'url'           => 'https://www.worten.es',
                'logo_url'      => null,
                'clase_scraper' => 'App\Scrapers\WortenScraper',
                'url_afiliado'  => null,
                'pais'          => 'ES',
                'moneda'        => 'EUR',
                'activo'        => true,
            ],
            [
                'nombre'        => 'CaseKing',
                'url'           => 'https://www.CaseKing.es',
                'logo_url'      => null,
                'clase_scraper' => 'App\Scrapers\CaseKingScraper',
                'url_afiliado'  => null,
                'pais'          => 'ES',
                'moneda'        => 'EUR',
                'activo'        => true,
            ],
            [
                'nombre'        => 'APP Informática',
                'url'           => 'https://www.appinformatica.com',
                'logo_url'      => null,
                'clase_scraper' => 'App\Scrapers\appinformaticaScraper',
                'url_afiliado'  => null,
                'pais'          => 'ES',
                'moneda'        => 'EUR',
                'activo'        => true,
            ],
            [
                'nombre'        => 'Aussar',
                'url'           => 'https://www.aussar.es/',
                'logo_url'      => null,
                'clase_scraper' => 'App\Scrapers\AussarScraper',
                'url_afiliado'  => null,
                'pais'          => 'ES',
                'moneda'        => 'EUR',
                'activo'        => true,
            ],
        ];

        foreach ($tiendas as $tienda) {
            Tienda::create($tienda);
        }
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Auxiliares\Marca;

class MarcasSeeder extends Seeder
{
    public function run(): void
    {
        $marcas = [
            // Fabricantes de chips
            [
                'nombre'      => 'Intel',
                'tipo'        => ['manufacturer', 'assembler'],
                'website'     => 'https://www.intel.com',
                'logo_url'    => null,
                'pais_origen' => 'US',
            ],
            [
                'nombre'      => 'AMD',
                'tipo'        => ['manufacturer', 'assembler'],
                'website'     => 'https://www.amd.com',
                'logo_url'    => null,
                'pais_origen' => 'US',
            ],
            [
                'nombre'      => 'NVIDIA',
                'tipo'        => ['manufacturer'],
                'website'     => 'https://www.nvidia.com',
                'logo_url'    => null,
                'pais_origen' => 'US',
            ],
            // Ensambladores
            [
                'nombre'      => 'ASUS',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.asus.com',
                'logo_url'    => null,
                'pais_origen' => 'TW',
            ],
            [
                'nombre'      => 'MSI',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.msi.com',
                'logo_url'    => null,
                'pais_origen' => 'TW',
            ],
            [
                'nombre'      => 'Gigabyte',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.gigabyte.com',
                'logo_url'    => null,
                'pais_origen' => 'TW',
            ],
            [
                'nombre'      => 'ASRock',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.asrock.com',
                'logo_url'    => null,
                'pais_origen' => 'TW',
            ],
            [
                'nombre'      => 'Sapphire',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.sapphiretech.com',
                'logo_url'    => null,
                'pais_origen' => 'HK',
            ],
            [
                'nombre'      => 'PowerColor',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.powercolor.com',
                'logo_url'    => null,
                'pais_origen' => 'TW',
            ],
            [
                'nombre'      => 'XFX',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.xfxforce.com',
                'logo_url'    => null,
                'pais_origen' => 'US',
            ],
            // Marcas de RAM
            [
                'nombre'      => 'Corsair',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.corsair.com',
                'logo_url'    => null,
                'pais_origen' => 'US',
            ],
            [
                'nombre'      => 'G.Skill',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.gskill.com',
                'logo_url'    => null,
                'pais_origen' => 'TW',
            ],
            [
                'nombre'      => 'Kingston',
                'tipo'        => ['manufacturer', 'assembler'],
                'website'     => 'https://www.kingston.com',
                'logo_url'    => null,
                'pais_origen' => 'US',
            ],
            [
                'nombre'      => 'Crucial',
                'tipo'        => ['manufacturer', 'assembler'],
                'website'     => 'https://www.crucial.com',
                'logo_url'    => null,
                'pais_origen' => 'US',
            ],
            [
                'nombre'      => 'TeamGroup',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.teamgroupinc.com',
                'logo_url'    => null,
                'pais_origen' => 'TW',
            ],
            // Marcas de almacenamiento
            [
                'nombre'      => 'Samsung',
                'tipo'        => ['manufacturer', 'assembler'],
                'website'     => 'https://www.samsung.com',
                'logo_url'    => null,
                'pais_origen' => 'KR',
            ],
            [
                'nombre'      => 'WD',
                'tipo'        => ['manufacturer', 'assembler'],
                'website'     => 'https://www.westerndigital.com',
                'logo_url'    => null,
                'pais_origen' => 'US',
            ],
            [
                'nombre'      => 'Seagate',
                'tipo'        => ['manufacturer', 'assembler'],
                'website'     => 'https://www.seagate.com',
                'logo_url'    => null,
                'pais_origen' => 'US',
            ],
            [
                'nombre'      => 'SK Hynix',
                'tipo'        => ['manufacturer'],
                'website'     => 'https://www.skhynix.com',
                'logo_url'    => null,
                'pais_origen' => 'KR',
            ],
            // Marcas de PSU
            [
                'nombre'      => 'Seasonic',
                'tipo'        => ['manufacturer', 'assembler'],
                'website'     => 'https://www.seasonic.com',
                'logo_url'    => null,
                'pais_origen' => 'TW',
            ],
            [
                'nombre'      => 'be quiet!',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.bequiet.com',
                'logo_url'    => null,
                'pais_origen' => 'DE',
            ],
            [
                'nombre'      => 'EVGA',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.evga.com',
                'logo_url'    => null,
                'pais_origen' => 'US',
            ],
            // Marcas de gabinetes
            [
                'nombre'      => 'Fractal Design',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.fractal-design.com',
                'logo_url'    => null,
                'pais_origen' => 'SE',
            ],
            [
                'nombre'      => 'Lian Li',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.lian-li.com',
                'logo_url'    => null,
                'pais_origen' => 'TW',
            ],
            [
                'nombre'      => 'Noctua',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.noctua.at',
                'logo_url'    => null,
                'pais_origen' => 'AT',
            ],
            [
                'nombre'      => 'Cooler Master',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.coolermaster.com',
                'logo_url'    => null,
                'pais_origen' => 'TW',
            ],
            [
                'nombre'      => 'NZXT',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.nzxt.com',
                'logo_url'    => null,
                'pais_origen' => 'US',
            ],
            [
                'nombre'      => 'Phanteks',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.phanteks.com',
                'logo_url'    => null,
                'pais_origen' => 'NL',
            ],
        ];

        foreach ($marcas as $marca) {
            Marca::create($marca);
        }
    }
}
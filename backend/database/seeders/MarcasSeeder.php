<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Auxiliares\Marca;

class MarcasSeeder extends Seeder
{
    public function run(): void
    {
        $marcas = [
            // ── Fabricantes de chips ──────────────────────────────────────
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

            // ── Ensambladores GPU / Placa Base ────────────────────────────
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
            [
                'nombre'      => 'INNO3D',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.inno3d.com',
                'logo_url'    => null,
                'pais_origen' => 'HK',
            ],
            [
                'nombre'      => 'GALAX',
                'tipo'        => ['assembler'], 
                'website'     => 'https://www.galax.com',
                'logo_url'    => null,
                'pais_origen' => 'HK',
            ],
            [
                'nombre'      => 'Gainward',
                'tipo'        => ['assembler'], 
                'website'     => 'https://www.gainward.com',
                'logo_url'    => null,
                'pais_origen' => 'TW',
            ],
            [
                'nombre'      => 'PNY',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.pny.com',
                'logo_url'    => null,
                'pais_origen' => 'US',
            ],
            [
                'nombre'      => 'Colorful',
                'tipo'        => ['assembler'], 
                'website'     => 'https://www.colorful.cn',
                'logo_url'    => null,
                'pais_origen' => 'CN',
            ],

            // ── RAM ───────────────────────────────────────────────────────
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
            [
                'nombre'      => 'Patriot', 
                'tipo'        => ['assembler'],
                'website'     => 'https://www.patriotmemory.com', 
                'logo_url'    => null,
                'pais_origen' => 'US',
            ],
            [
                'nombre'      => 'Silicon Power',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.silicon-power.com', 
                'logo_url'    => null,
                'pais_origen' => 'TW',
            ],
            [
                'nombre'      => 'klevv',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.klevv.com/', 
                'logo_url'    => null,
                'pais_origen' => 'KR',
            ],

            // ── Almacenamiento ────────────────────────────────────────────
            [
                'nombre'      => 'Samsung',
                'tipo'        => ['manufacturer', 'assembler'],
                'website'     => 'https://www.samsung.com',
                'logo_url'    => null,
                'pais_origen' => 'KR',
            ],
            [
                'nombre'      => 'Western Digital',
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

            // ── PSU ───────────────────────────────────────────────────────
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
            [
                'nombre'      => 'XPG',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.xpg.com/',
                'logo_url'    => null,
                'pais_origen' => 'TW',
            ],

            // ── Gabinetes y refrigeración ─────────────────────────────────
            [
                'nombre'      => 'Fractal Design',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.fractal-design.com',
                'logo_url'    => null,
                'pais_origen' => 'SE',
            ],
            [
                'nombre'      => 'ID-Cooling',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.idcooling.com',
                'logo_url'    => null,
                'pais_origen' => 'CN',
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
            [
                'nombre'      => 'Zotac',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.zotac.com',
                'logo_url'    => null,
                'pais_origen' => 'HK',
            ],
            [
                'nombre'      => 'Palit',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.palit.com',
                'logo_url'    => null,
                'pais_origen' => 'HK',
            ],
            [
                'nombre'      => 'ADATA',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.adata.com',
                'logo_url'    => null,
                'pais_origen' => 'TW',
            ],
            [
                'nombre'      => 'Sabrent',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.sabrent.com',
                'logo_url'    => null,
                'pais_origen' => 'US',
            ],
            [
                'nombre'      => 'Lexar',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.lexar.com',
                'logo_url'    => null,
                'pais_origen' => 'US',
            ],
            [
                'nombre'      => 'Antec',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.antec.com',
                'logo_url'    => null,
                'pais_origen' => 'US',
            ],
            [
                'nombre'      => 'Arctic',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.arctic.de',
                'logo_url'    => null,
                'pais_origen' => 'CH',
            ],
            [
                'nombre'      => 'Biostar',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.biostar.com.tw',
                'logo_url'    => null,
                'pais_origen' => 'TW',
            ],
            [
                'nombre'      => 'EK Water Blocks',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.ekwb.com',
                'logo_url'    => null,
                'pais_origen' => 'SI',
            ],
            [
                'nombre'      => 'Scythe',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.scythe-eu.com',
                'logo_url'    => null,
                'pais_origen' => 'JP',
            ],
            [
                'nombre'      => 'Silverstone',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.silverstonetek.com',
                'logo_url'    => null,
                'pais_origen' => 'TW',
            ],
            [
                'nombre'      => 'Thermalright',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.thermalright.com',
                'logo_url'    => null,
                'pais_origen' => 'CN',
            ],
            [
                'nombre'      => 'Super Flower',
                'tipo'        => ['manufacturer', 'assembler'],
                'website'     => 'https://www.superflower.com.tw',
                'logo_url'    => null,
                'pais_origen' => 'TW',
            ],
            [
                'nombre'      => 'DeepCool',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.deepcool.com',
                'logo_url'    => null,
                'pais_origen' => 'CN',
            ],
            [
                'nombre'      => 'Thermaltake',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.thermaltake.com',
                'logo_url'    => null,
                'pais_origen' => 'TW', 
            ],
            [
                'nombre'      => 'Watercool',
                'tipo'        => ['assembler'],
                'website'     => 'https://watercool.de',
                'logo_url'    => null,
                'pais_origen' => 'DE',
            ],

            // ── Marcas de gabinetes SFF / ITX (nuevas) ───────────────────
            [
                'nombre'      => 'Cougar',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.cougargaming.com',
                'logo_url'    => null,
                'pais_origen' => 'TW',
            ],
            [
                'nombre'      => 'Jonsbo',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.jonsbo.com',
                'logo_url'    => null,
                'pais_origen' => 'CN',
            ],
            [
                'nombre'      => 'FormD',
                'tipo'        => ['assembler'],
                'website'     => 'https://formdworks.com',
                'logo_url'    => null,
                'pais_origen' => 'US',
            ],
            [
                'nombre'      => 'InWin',
                'tipo'        => ['assembler'],
                'website'     => 'https://www.inwin-style.com',
                'logo_url'    => null,
                'pais_origen' => 'TW',
            ],
            [
                'nombre'      => 'Dan Cases',
                'tipo'        => ['assembler'],
                'website'     => 'https://dan-cases.com',
                'logo_url'    => null,
                'pais_origen' => 'DE',
            ],
            [
                'nombre'      => 'NCASE',
                'tipo'        => ['assembler'],
                'website'     => 'https://ncased.com',
                'logo_url'    => null,
                'pais_origen' => 'TW',
            ],
        ];

        foreach ($marcas as $marca) {
            Marca::create($marca);
        }
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Auxiliares\Marca;
use App\Models\Auxiliares\Socket;
use App\Models\Auxiliares\Arquitectura;
use App\Models\Auxiliares\TipoMemoria;
use App\Models\Auxiliares\TipoVRAM;
use App\Models\Auxiliares\VersionPCIe;
use App\Models\Auxiliares\CertificacionPSU;
use App\Models\Auxiliares\TipoPSU;
use App\Models\Auxiliares\FactorForma;
use App\Models\Auxiliares\Chipset;
use App\Models\Auxiliares\TipoGabinete;
use App\Models\Auxiliares\EstructuraGabinete;
use App\Models\Auxiliares\InterfazAlmacenamiento;
use App\Models\Auxiliares\FactorFormaAlmacenamiento;
use App\Models\Auxiliares\TipoNAND;
use App\Models\Auxiliares\TipoRefrigeracion;
use App\Models\Auxiliares\TipoVentilador;
use App\Models\Componentes\Componente;
use App\Models\Componentes\CPU;
use App\Models\Componentes\GPU;
use App\Models\Componentes\RAM;
use App\Models\Componentes\PlacaBase;
use App\Models\Componentes\PSU;
use App\Models\Componentes\Almacenamiento;
use App\Models\Componentes\Gabinete;
use App\Models\Componentes\RefrigeracionAire;
use App\Models\Componentes\RefrigeracionLiquida;
use App\Models\Componentes\Ventilador;
use App\Models\Negocio\Tienda;
use App\Models\Negocio\EntradaPrecio;

class ComponentesSeeder extends Seeder
{
    private array $tiendas = [];

    public function run(): void
    {
        // Cache de tiendas
        foreach (['PCComponentes', 'Amazon España', 'Coolmod', 'Alternate', 'MediaMarkt'] as $nombre) {
            $t = Tienda::where('nombre', $nombre)->first();
            if ($t) $this->tiendas[$nombre] = $t;
        }

        $this->seedCPUs();
        $this->seedGPUs();
        $this->seedRAMs();
        $this->seedPlacasBase();
        $this->seedAlmacenamientos();
        $this->seedPSUs();
        $this->seedGabinetes();
        $this->seedRefrigeracionAire();
        $this->seedRefrigeracionLiquida();
        $this->seedVentiladores();
    }

    // ── Helpers ───────────────────────────────────────────────

    private function componente(array $data): Componente
    {
        return Componente::create(array_merge(['activo' => true, 'imagen_url' => null], $data));
    }

    private function precios(Componente $comp, array $precios): void
    {
        foreach ($precios as $nombre => $precio) {
            if (!isset($this->tiendas[$nombre])) continue;
            EntradaPrecio::create([
                'componente_id' => $comp->id,
                'tienda_id'     => $this->tiendas[$nombre]->id,
                'precio'        => $precio,
                'url'           => $this->tiendas[$nombre]->url . '/p/' . strtolower(str_replace(' ', '-', $comp->nombre)),
                'en_stock'    => true,
                'scraped_at'=> now(),
            ]);
        }
    }

    private function aux(string $model, string $campo, string $valor): mixed
    {
        return $model::where($campo, $valor)->first();
    }

    // ── CPUs ──────────────────────────────────────────────────

    private function seedCPUs(): void
    {
        $amd   = Marca::where('nombre', 'AMD')->first();
        $intel = Marca::where('nombre', 'Intel')->first();
        $am5   = Socket::where('nombre', 'AM5')->first();
        $am4   = Socket::where('nombre', 'AM4')->first();
        $lga1700 = Socket::where('nombre', 'LGA1700')->first();
        $lga1851 = Socket::where('nombre', 'LGA1851')->first();
        $zen3  = Arquitectura::where('nombre', 'Zen 3')->first();
        $zen4  = Arquitectura::where('nombre', 'Zen 4')->first();
        $zen5  = Arquitectura::where('nombre', 'Zen 5')->first();
        $rl    = Arquitectura::where('nombre', 'Raptor Lake')->first();
        $rlr   = Arquitectura::where('nombre', 'Raptor Lake R')->first();
        $al    = Arquitectura::where('nombre', 'Arrow Lake')->first();
        $ddr4  = TipoMemoria::where('nombre', 'DDR4')->first();
        $ddr5  = TipoMemoria::where('nombre', 'DDR5')->first();

        $cpus = [
            // AMD AM5
            ['nombre' => 'AMD Ryzen 9 9950X', 'marca' => $amd, 'socket' => $am5, 'arq' => $zen5, 'mem' => $ddr5,
             'nucleos' => 16, 'hilos' => 32, 'base' => 4.3, 'boost' => 5.7, 'tdp' => 170, 'tdp_max' => 230, 'nm' => 4, 'igpu' => false, 'oc' => true, 'mem_mhz' => 5600,
             'precios' => ['PCComponentes' => 629.99, 'Amazon España' => 649.99, 'Coolmod' => 624.99]],

            ['nombre' => 'AMD Ryzen 9 9900X', 'marca' => $amd, 'socket' => $am5, 'arq' => $zen5, 'mem' => $ddr5,
             'nucleos' => 12, 'hilos' => 24, 'base' => 4.4, 'boost' => 5.6, 'tdp' => 120, 'tdp_max' => 162, 'nm' => 4, 'igpu' => false, 'oc' => true, 'mem_mhz' => 5600,
             'precios' => ['PCComponentes' => 449.99, 'Amazon España' => 459.99, 'Alternate' => 444.99]],

            ['nombre' => 'AMD Ryzen 7 9700X', 'marca' => $amd, 'socket' => $am5, 'arq' => $zen5, 'mem' => $ddr5,
             'nucleos' => 8, 'hilos' => 16, 'base' => 3.8, 'boost' => 5.5, 'tdp' => 65, 'tdp_max' => 88, 'nm' => 4, 'igpu' => false, 'oc' => true, 'mem_mhz' => 5600,
             'precios' => ['PCComponentes' => 329.99, 'Amazon España' => 339.99, 'Coolmod' => 325.99]],

            ['nombre' => 'AMD Ryzen 7 9800X3D', 'marca' => $amd, 'socket' => $am5, 'arq' => $zen5, 'mem' => $ddr5,
             'nucleos' => 8, 'hilos' => 16, 'base' => 4.7, 'boost' => 5.2, 'tdp' => 120, 'tdp_max' => 162, 'nm' => 4, 'igpu' => false, 'oc' => false, 'mem_mhz' => 5600,
             'precios' => ['PCComponentes' => 479.99, 'Amazon España' => 489.99, 'Coolmod' => 474.99, 'Alternate' => 469.99]],

            ['nombre' => 'AMD Ryzen 5 9600X', 'marca' => $amd, 'socket' => $am5, 'arq' => $zen5, 'mem' => $ddr5,
             'nucleos' => 6, 'hilos' => 12, 'base' => 3.9, 'boost' => 5.4, 'tdp' => 65, 'tdp_max' => 88, 'nm' => 4, 'igpu' => false, 'oc' => true, 'mem_mhz' => 5600,
             'precios' => ['PCComponentes' => 229.99, 'Amazon España' => 239.99, 'Coolmod' => 224.99]],

            ['nombre' => 'AMD Ryzen 7 7800X3D', 'marca' => $amd, 'socket' => $am5, 'arq' => $zen4, 'mem' => $ddr5,
             'nucleos' => 8, 'hilos' => 16, 'base' => 4.2, 'boost' => 5.0, 'tdp' => 120, 'tdp_max' => 162, 'nm' => 5, 'igpu' => false, 'oc' => false, 'mem_mhz' => 5200,
             'precios' => ['PCComponentes' => 389.99, 'Amazon España' => 399.99, 'Alternate' => 384.99]],

            ['nombre' => 'AMD Ryzen 5 7600X', 'marca' => $amd, 'socket' => $am5, 'arq' => $zen4, 'mem' => $ddr5,
             'nucleos' => 6, 'hilos' => 12, 'base' => 4.7, 'boost' => 5.3, 'tdp' => 105, 'tdp_max' => 142, 'nm' => 5, 'igpu' => false, 'oc' => true, 'mem_mhz' => 5200,
             'precios' => ['PCComponentes' => 199.99, 'Amazon España' => 209.99, 'Coolmod' => 195.99]],

            // AMD AM4
            ['nombre' => 'AMD Ryzen 9 5900X', 'marca' => $amd, 'socket' => $am4, 'arq' => $zen3, 'mem' => $ddr4,
             'nucleos' => 12, 'hilos' => 24, 'base' => 3.7, 'boost' => 4.8, 'tdp' => 105, 'tdp_max' => 142, 'nm' => 7, 'igpu' => false, 'oc' => true, 'mem_mhz' => 3200,
             'precios' => ['PCComponentes' => 179.99, 'Amazon España' => 189.99, 'Alternate' => 175.99]],

            ['nombre' => 'AMD Ryzen 5 5600X', 'marca' => $amd, 'socket' => $am4, 'arq' => $zen3, 'mem' => $ddr4,
             'nucleos' => 6, 'hilos' => 12, 'base' => 3.7, 'boost' => 4.6, 'tdp' => 65, 'tdp_max' => 88, 'nm' => 7, 'igpu' => false, 'oc' => true, 'mem_mhz' => 3200,
             'precios' => ['PCComponentes' => 119.99, 'Amazon España' => 124.99, 'Coolmod' => 115.99]],

            // Intel LGA1700
            ['nombre' => 'Intel Core i9-14900K', 'marca' => $intel, 'socket' => $lga1700, 'arq' => $rlr, 'mem' => $ddr5,
             'nucleos' => 24, 'hilos' => 32, 'base' => 3.2, 'boost' => 6.0, 'tdp' => 125, 'tdp_max' => 253, 'nm' => 10, 'igpu' => true, 'oc' => true, 'mem_mhz' => 5600,
             'precios' => ['PCComponentes' => 489.99, 'Amazon España' => 499.99, 'MediaMarkt' => 509.99]],

            ['nombre' => 'Intel Core i7-14700K', 'marca' => $intel, 'socket' => $lga1700, 'arq' => $rlr, 'mem' => $ddr5,
             'nucleos' => 20, 'hilos' => 28, 'base' => 3.4, 'boost' => 5.6, 'tdp' => 125, 'tdp_max' => 253, 'nm' => 10, 'igpu' => true, 'oc' => true, 'mem_mhz' => 5600,
             'precios' => ['PCComponentes' => 329.99, 'Amazon España' => 339.99, 'Alternate' => 324.99]],

            ['nombre' => 'Intel Core i5-14600K', 'marca' => $intel, 'socket' => $lga1700, 'arq' => $rlr, 'mem' => $ddr5,
             'nucleos' => 14, 'hilos' => 20, 'base' => 3.5, 'boost' => 5.3, 'tdp' => 125, 'tdp_max' => 181, 'nm' => 10, 'igpu' => true, 'oc' => true, 'mem_mhz' => 5600,
             'precios' => ['PCComponentes' => 269.99, 'Amazon España' => 279.99, 'Coolmod' => 264.99]],

            ['nombre' => 'Intel Core i5-13600KF', 'marca' => $intel, 'socket' => $lga1700, 'arq' => $rl, 'mem' => $ddr5,
             'nucleos' => 14, 'hilos' => 20, 'base' => 3.5, 'boost' => 5.1, 'tdp' => 125, 'tdp_max' => 181, 'nm' => 10, 'igpu' => false, 'oc' => true, 'mem_mhz' => 5600,
             'precios' => ['PCComponentes' => 229.99, 'Amazon España' => 239.99, 'Coolmod' => 224.99]],

            // Intel LGA1851
            ['nombre' => 'Intel Core Ultra 9 285K', 'marca' => $intel, 'socket' => $lga1851, 'arq' => $al, 'mem' => $ddr5,
             'nucleos' => 24, 'hilos' => 24, 'base' => 3.7, 'boost' => 5.7, 'tdp' => 125, 'tdp_max' => 250, 'nm' => 3, 'igpu' => true, 'oc' => true, 'mem_mhz' => 6400,
             'precios' => ['PCComponentes' => 549.99, 'Amazon España' => 569.99, 'MediaMarkt' => 579.99]],

            ['nombre' => 'Intel Core Ultra 7 265K', 'marca' => $intel, 'socket' => $lga1851, 'arq' => $al, 'mem' => $ddr5,
             'nucleos' => 20, 'hilos' => 20, 'base' => 3.9, 'boost' => 5.5, 'tdp' => 125, 'tdp_max' => 250, 'nm' => 3, 'igpu' => true, 'oc' => true, 'mem_mhz' => 6400,
             'precios' => ['PCComponentes' => 379.99, 'Amazon España' => 389.99, 'Alternate' => 374.99]],

            ['nombre' => 'Intel Core Ultra 5 245K', 'marca' => $intel, 'socket' => $lga1851, 'arq' => $al, 'mem' => $ddr5,
             'nucleos' => 14, 'hilos' => 14, 'base' => 3.6, 'boost' => 5.2, 'tdp' => 125, 'tdp_max' => 159, 'nm' => 3, 'igpu' => true, 'oc' => true, 'mem_mhz' => 6400,
             'precios' => ['PCComponentes' => 249.99, 'Amazon España' => 259.99, 'Coolmod' => 244.99]],
        ];

        foreach ($cpus as $data) {
            $comp = $this->componente([
                'nombre' => $data['nombre'], 'marca_id' => $data['marca']->id,
                'fabricante_id' => $data['marca']->id, 'categoria' => 'cpu',
                'descripcion' => $data['nombre'] . ', ' . $data['nucleos'] . ' núcleos, arquitectura ' . $data['arq']->nombre,
            ]);
            CPU::create([
                'componente_id' => $comp->id, 'socket_id' => $data['socket']->id,
                'arquitectura_id' => $data['arq']->id, 'tipo_memoria_id' => $data['mem']->id,
                'nucleos' => $data['nucleos'], 'hilos' => $data['hilos'],
                'frecuencia_base_ghz' => $data['base'], 'frecuencia_boost_ghz' => $data['boost'],
                'tdp_watts' => $data['tdp'], 'tdp_max_watts' => $data['tdp_max'],
                'frecuencia_memoria_max_mhz' => $data['mem_mhz'], 'memoria_max_gb' => 192,
                'grafica_integrada' => $data['igpu'], 'proceso_nm' => $data['nm'],
                'incluye_cooler' => false, 'overclock' => $data['oc'],
            ]);
            $this->precios($comp, $data['precios']);
        }
    }

    // ── GPUs ──────────────────────────────────────────────────

    private function seedGPUs(): void
    {
        $nvidia  = Marca::where('nombre', 'NVIDIA')->first();
        $amd     = Marca::where('nombre', 'AMD')->first();
        $asus    = Marca::where('nombre', 'ASUS')->first();
        $msi     = Marca::where('nombre', 'MSI')->first();
        $gigabyte= Marca::where('nombre', 'Gigabyte')->first();
        $sapphire= Marca::where('nombre', 'Sapphire')->first();
        $powercolor = Marca::where('nombre', 'PowerColor')->first();
        $ada     = Arquitectura::where('nombre', 'Ada Lovelace')->first();
        $blackwell = Arquitectura::where('nombre', 'Blackwell')->first();
        $rdna3   = Arquitectura::where('nombre', 'RDNA 3')->first();
        $rdna4   = Arquitectura::where('nombre', 'RDNA 4')->first();
        $gddr6   = TipoVRAM::where('nombre', 'GDDR6')->first();
        $gddr6x  = TipoVRAM::where('nombre', 'GDDR6X')->first();
        $gddr7   = TipoVRAM::where('nombre', 'GDDR7')->first();
        $pcie40  = VersionPCIe::where('nombre', 'PCIe 4.0')->first();
        $pcie50  = VersionPCIe::where('nombre', 'PCIe 5.0')->first();

        $gpus = [
            // RTX 5090
            ['nombre' => 'ASUS ROG Astral RTX 5090 32GB', 'ensamblador' => $asus, 'fabricante' => $nvidia,
             'arq' => $blackwell, 'vram_tipo' => $gddr7, 'pcie' => $pcie50,
             'vram' => 32, 'bus' => 512, 'base' => 2010, 'boost' => 2407, 'tdp' => 575, 'slots' => 3.5, 'long' => 366,
             'conectores' => ['1x16pin'], 'psu_min' => 1000, 'rt' => true, 'dlss' => true, 'fsr' => true,
             'precios' => ['PCComponentes' => 2199.99, 'Amazon España' => 2249.99, 'Alternate' => 2179.99]],

            // RTX 5080
            ['nombre' => 'MSI Gaming Trio RTX 5080 16GB', 'ensamblador' => $msi, 'fabricante' => $nvidia,
             'arq' => $blackwell, 'vram_tipo' => $gddr7, 'pcie' => $pcie50,
             'vram' => 16, 'bus' => 256, 'base' => 2295, 'boost' => 2617, 'tdp' => 360, 'slots' => 3.5, 'long' => 355,
             'conectores' => ['1x16pin'], 'psu_min' => 850, 'rt' => true, 'dlss' => true, 'fsr' => true,
             'precios' => ['PCComponentes' => 1149.99, 'Amazon España' => 1179.99, 'Coolmod' => 1139.99]],

            // RTX 5070 Ti
            ['nombre' => 'Gigabyte Aorus Master RTX 5070 Ti 16GB', 'ensamblador' => $gigabyte, 'fabricante' => $nvidia,
             'arq' => $blackwell, 'vram_tipo' => $gddr7, 'pcie' => $pcie50,
             'vram' => 16, 'bus' => 256, 'base' => 2452, 'boost' => 2452, 'tdp' => 300, 'slots' => 3.5, 'long' => 340,
             'conectores' => ['1x16pin'], 'psu_min' => 750, 'rt' => true, 'dlss' => true, 'fsr' => true,
             'precios' => ['PCComponentes' => 849.99, 'Amazon España' => 869.99, 'Alternate' => 844.99]],

            // RTX 5070
            ['nombre' => 'ASUS TUF Gaming RTX 5070 12GB OC', 'ensamblador' => $asus, 'fabricante' => $nvidia,
             'arq' => $blackwell, 'vram_tipo' => $gddr7, 'pcie' => $pcie50,
             'vram' => 12, 'bus' => 192, 'base' => 2332, 'boost' => 2512, 'tdp' => 250, 'slots' => 3.0, 'long' => 318,
             'conectores' => ['1x16pin'], 'psu_min' => 650, 'rt' => true, 'dlss' => true, 'fsr' => true,
             'precios' => ['PCComponentes' => 619.99, 'Amazon España' => 639.99, 'Coolmod' => 609.99]],

            // RTX 4090
            ['nombre' => 'ASUS ROG Strix RTX 4090 OC 24GB', 'ensamblador' => $asus, 'fabricante' => $nvidia,
             'arq' => $ada, 'vram_tipo' => $gddr6x, 'pcie' => $pcie40,
             'vram' => 24, 'bus' => 384, 'base' => 2235, 'boost' => 2640, 'tdp' => 450, 'slots' => 3.5, 'long' => 357,
             'conectores' => ['1x16pin'], 'psu_min' => 850, 'rt' => true, 'dlss' => true, 'fsr' => true,
             'precios' => ['PCComponentes' => 1749.99, 'Amazon España' => 1799.99, 'Alternate' => 1729.99]],

            // RTX 4080 Super
            ['nombre' => 'MSI Gaming X Slim RTX 4080 Super 16GB', 'ensamblador' => $msi, 'fabricante' => $nvidia,
             'arq' => $ada, 'vram_tipo' => $gddr6x, 'pcie' => $pcie40,
             'vram' => 16, 'bus' => 256, 'base' => 2295, 'boost' => 2625, 'tdp' => 320, 'slots' => 2.5, 'long' => 337,
             'conectores' => ['1x16pin'], 'psu_min' => 750, 'rt' => true, 'dlss' => true, 'fsr' => true,
             'precios' => ['PCComponentes' => 979.99, 'Amazon España' => 999.99, 'Coolmod' => 969.99]],

            // RTX 4070 Ti Super
            ['nombre' => 'ASUS TUF Gaming RTX 4070 Ti Super OC 16GB', 'ensamblador' => $asus, 'fabricante' => $nvidia,
             'arq' => $ada, 'vram_tipo' => $gddr6x, 'pcie' => $pcie40,
             'vram' => 16, 'bus' => 256, 'base' => 2340, 'boost' => 2640, 'tdp' => 285, 'slots' => 3.0, 'long' => 336,
             'conectores' => ['2x8pin'], 'psu_min' => 700, 'rt' => true, 'dlss' => true, 'fsr' => true,
             'precios' => ['PCComponentes' => 799.99, 'Amazon España' => 819.99, 'Alternate' => 789.99]],

            // RTX 4070 Super
            ['nombre' => 'Gigabyte Gaming OC RTX 4070 Super 12GB', 'ensamblador' => $gigabyte, 'fabricante' => $nvidia,
             'arq' => $ada, 'vram_tipo' => $gddr6x, 'pcie' => $pcie40,
             'vram' => 12, 'bus' => 192, 'base' => 1980, 'boost' => 2475, 'tdp' => 220, 'slots' => 2.5, 'long' => 300,
             'conectores' => ['2x8pin'], 'psu_min' => 650, 'rt' => true, 'dlss' => true, 'fsr' => true,
             'precios' => ['PCComponentes' => 579.99, 'Amazon España' => 599.99, 'Coolmod' => 569.99]],

            // RTX 4060 Ti
            ['nombre' => 'MSI Ventus 2X RTX 4060 Ti 8GB OC', 'ensamblador' => $msi, 'fabricante' => $nvidia,
             'arq' => $ada, 'vram_tipo' => $gddr6, 'pcie' => $pcie40,
             'vram' => 8, 'bus' => 128, 'base' => 2310, 'boost' => 2535, 'tdp' => 165, 'slots' => 2.0, 'long' => 237,
             'conectores' => ['1x8pin'], 'psu_min' => 550, 'rt' => true, 'dlss' => true, 'fsr' => true,
             'precios' => ['PCComponentes' => 389.99, 'Amazon España' => 399.99, 'MediaMarkt' => 409.99]],

            // RTX 4060
            ['nombre' => 'ASUS Dual RTX 4060 OC 8GB', 'ensamblador' => $asus, 'fabricante' => $nvidia,
             'arq' => $ada, 'vram_tipo' => $gddr6, 'pcie' => $pcie40,
             'vram' => 8, 'bus' => 128, 'base' => 1830, 'boost' => 2460, 'tdp' => 115, 'slots' => 2.0, 'long' => 240,
             'conectores' => ['1x8pin'], 'psu_min' => 550, 'rt' => true, 'dlss' => true, 'fsr' => true,
             'precios' => ['PCComponentes' => 299.99, 'Amazon España' => 309.99, 'Coolmod' => 294.99]],

            // RX 9070 XT
            ['nombre' => 'Sapphire Nitro+ RX 9070 XT 16GB', 'ensamblador' => $sapphire, 'fabricante' => $amd,
             'arq' => $rdna4, 'vram_tipo' => $gddr6, 'pcie' => $pcie50,
             'vram' => 16, 'bus' => 256, 'base' => 1840, 'boost' => 2970, 'tdp' => 304, 'slots' => 2.5, 'long' => 320,
             'conectores' => ['2x8pin'], 'psu_min' => 700, 'rt' => true, 'dlss' => false, 'fsr' => true,
             'precios' => ['PCComponentes' => 599.99, 'Amazon España' => 619.99, 'Alternate' => 589.99]],

            // RX 9070
            ['nombre' => 'PowerColor Fighter RX 9070 16GB', 'ensamblador' => $powercolor, 'fabricante' => $amd,
             'arq' => $rdna4, 'vram_tipo' => $gddr6, 'pcie' => $pcie50,
             'vram' => 16, 'bus' => 256, 'base' => 1718, 'boost' => 2520, 'tdp' => 220, 'slots' => 2.5, 'long' => 295,
             'conectores' => ['2x8pin'], 'psu_min' => 650, 'rt' => true, 'dlss' => false, 'fsr' => true,
             'precios' => ['PCComponentes' => 479.99, 'Amazon España' => 499.99, 'Coolmod' => 469.99]],

            // RX 7900 XTX
            ['nombre' => 'Sapphire Nitro+ RX 7900 XTX 24GB', 'ensamblador' => $sapphire, 'fabricante' => $amd,
             'arq' => $rdna3, 'vram_tipo' => $gddr6, 'pcie' => $pcie40,
             'vram' => 24, 'bus' => 384, 'base' => 1855, 'boost' => 2615, 'tdp' => 355, 'slots' => 2.5, 'long' => 325,
             'conectores' => ['3x8pin'], 'psu_min' => 800, 'rt' => true, 'dlss' => false, 'fsr' => true,
             'precios' => ['PCComponentes' => 799.99, 'Amazon España' => 829.99, 'Alternate' => 789.99]],

            // RX 7800 XT
            ['nombre' => 'Gigabyte Gaming OC RX 7800 XT 16GB', 'ensamblador' => $gigabyte, 'fabricante' => $amd,
             'arq' => $rdna3, 'vram_tipo' => $gddr6, 'pcie' => $pcie40,
             'vram' => 16, 'bus' => 256, 'base' => 1295, 'boost' => 2430, 'tdp' => 263, 'slots' => 2.5, 'long' => 310,
             'conectores' => ['2x8pin'], 'psu_min' => 650, 'rt' => true, 'dlss' => false, 'fsr' => true,
             'precios' => ['PCComponentes' => 429.99, 'Amazon España' => 449.99, 'Coolmod' => 419.99]],

            // RX 7600
            ['nombre' => 'MSI Mech 2X RX 7600 8GB OC', 'ensamblador' => $msi, 'fabricante' => $amd,
             'arq' => $rdna3, 'vram_tipo' => $gddr6, 'pcie' => $pcie40,
             'vram' => 8, 'bus' => 128, 'base' => 1720, 'boost' => 2755, 'tdp' => 165, 'slots' => 2.0, 'long' => 231,
             'conectores' => ['1x8pin'], 'psu_min' => 550, 'rt' => true, 'dlss' => false, 'fsr' => true,
             'precios' => ['PCComponentes' => 239.99, 'Amazon España' => 249.99, 'MediaMarkt' => 259.99]],
        ];

        foreach ($gpus as $data) {
            $comp = $this->componente([
                'nombre' => $data['nombre'], 'marca_id' => $data['ensamblador']->id,
                'fabricante_id' => $data['fabricante']->id, 'categoria' => 'gpu',
                'descripcion' => $data['nombre'] . ', ' . $data['vram'] . 'GB ' . $data['vram_tipo']->nombre,
            ]);
            GPU::create([
                'componente_id' => $comp->id, 'arquitectura_id' => $data['arq']->id,
                'tipo_vram_id' => $data['vram_tipo']->id, 'version_pcie_id' => $data['pcie']->id,
                'vram_gb' => $data['vram'], 'bus_bits' => $data['bus'],
                'frecuencia_base_mhz' => $data['base'], 'frecuencia_boost_mhz' => $data['boost'],
                'tdp_watts' => $data['tdp'], 'slots_pcie' => $data['slots'], 'longitud_mm' => $data['long'],
                'conectores_alimentacion' => $data['conectores'], 'psu_minima_watts' => $data['psu_min'],
                'salidas_video' => ['3xDisplayPort', '1xHDMI'],
                'ray_tracing' => $data['rt'], 'dlss' => $data['dlss'], 'fsr' => $data['fsr'],
            ]);
            $this->precios($comp, $data['precios']);
        }
    }

    // ── RAMs ──────────────────────────────────────────────────

    private function seedRAMs(): void
    {
        $corsair  = Marca::where('nombre', 'Corsair')->first();
        $gskill   = Marca::where('nombre', 'G.Skill')->first();
        $kingston = Marca::where('nombre', 'Kingston')->first();
        $crucial  = Marca::where('nombre', 'Crucial')->first();
        $teamgroup= Marca::where('nombre', 'TeamGroup')->first();
        $ddr4     = TipoMemoria::where('nombre', 'DDR4')->first();
        $ddr5     = TipoMemoria::where('nombre', 'DDR5')->first();

        $rams = [
            // DDR5
            ['nombre' => 'Corsair Vengeance DDR5 32GB 6000MHz CL30', 'marca' => $corsair, 'mem' => $ddr5,
             'cap' => 16, 'mod' => 2, 'mhz' => 6000, 'cl' => 30, 'v' => 1.35, 'rgb' => false, 'xmp' => true,
             'precios' => ['PCComponentes' => 89.99, 'Amazon España' => 94.99, 'Coolmod' => 87.99]],

            ['nombre' => 'G.Skill Trident Z5 RGB DDR5 32GB 6400MHz CL32', 'marca' => $gskill, 'mem' => $ddr5,
             'cap' => 16, 'mod' => 2, 'mhz' => 6400, 'cl' => 32, 'v' => 1.40, 'rgb' => true, 'xmp' => true,
             'precios' => ['PCComponentes' => 119.99, 'Amazon España' => 124.99, 'Alternate' => 114.99]],

            ['nombre' => 'G.Skill Trident Z5 RGB DDR5 64GB 6000MHz CL30', 'marca' => $gskill, 'mem' => $ddr5,
             'cap' => 32, 'mod' => 2, 'mhz' => 6000, 'cl' => 30, 'v' => 1.35, 'rgb' => true, 'xmp' => true,
             'precios' => ['PCComponentes' => 199.99, 'Amazon España' => 209.99, 'Alternate' => 194.99]],

            ['nombre' => 'Kingston Fury Beast DDR5 32GB 5200MHz CL40', 'marca' => $kingston, 'mem' => $ddr5,
             'cap' => 16, 'mod' => 2, 'mhz' => 5200, 'cl' => 40, 'v' => 1.25, 'rgb' => false, 'xmp' => true,
             'precios' => ['PCComponentes' => 74.99, 'Amazon España' => 79.99, 'MediaMarkt' => 84.99]],

            ['nombre' => 'Crucial Pro DDR5 32GB 5600MHz CL46', 'marca' => $crucial, 'mem' => $ddr5,
             'cap' => 16, 'mod' => 2, 'mhz' => 5600, 'cl' => 46, 'v' => 1.10, 'rgb' => false, 'xmp' => true,
             'precios' => ['PCComponentes' => 69.99, 'Amazon España' => 74.99, 'Coolmod' => 67.99]],

            ['nombre' => 'TeamGroup T-Force Delta RGB DDR5 32GB 6000MHz CL38', 'marca' => $teamgroup, 'mem' => $ddr5,
             'cap' => 16, 'mod' => 2, 'mhz' => 6000, 'cl' => 38, 'v' => 1.25, 'rgb' => true, 'xmp' => true,
             'precios' => ['PCComponentes' => 84.99, 'Amazon España' => 89.99, 'Alternate' => 82.99]],

            // DDR4
            ['nombre' => 'Corsair Vengeance LPX DDR4 32GB 3600MHz CL18', 'marca' => $corsair, 'mem' => $ddr4,
             'cap' => 16, 'mod' => 2, 'mhz' => 3600, 'cl' => 18, 'v' => 1.35, 'rgb' => false, 'xmp' => true,
             'precios' => ['PCComponentes' => 59.99, 'Amazon España' => 64.99, 'Coolmod' => 57.99]],

            ['nombre' => 'G.Skill Ripjaws V DDR4 32GB 3600MHz CL16', 'marca' => $gskill, 'mem' => $ddr4,
             'cap' => 16, 'mod' => 2, 'mhz' => 3600, 'cl' => 16, 'v' => 1.35, 'rgb' => false, 'xmp' => true,
             'precios' => ['PCComponentes' => 54.99, 'Amazon España' => 59.99, 'Alternate' => 52.99]],

            ['nombre' => 'Kingston Fury Beast DDR4 16GB 3200MHz CL16', 'marca' => $kingston, 'mem' => $ddr4,
             'cap' => 8, 'mod' => 2, 'mhz' => 3200, 'cl' => 16, 'v' => 1.35, 'rgb' => false, 'xmp' => true,
             'precios' => ['PCComponentes' => 34.99, 'Amazon España' => 39.99, 'MediaMarkt' => 44.99]],

            ['nombre' => 'Corsair Dominator Platinum RGB DDR4 64GB 3600MHz CL18', 'marca' => $corsair, 'mem' => $ddr4,
             'cap' => 16, 'mod' => 4, 'mhz' => 3600, 'cl' => 18, 'v' => 1.35, 'rgb' => true, 'xmp' => true,
             'precios' => ['PCComponentes' => 189.99, 'Amazon España' => 199.99, 'Alternate' => 184.99]],
        ];

        foreach ($rams as $data) {
            $comp = $this->componente([
                'nombre' => $data['nombre'], 'marca_id' => $data['marca']->id,
                'categoria' => 'ram',
                'descripcion' => $data['nombre'] . ', ' . ($data['cap'] * $data['mod']) . 'GB total',
            ]);
            RAM::create([
                'componente_id' => $comp->id, 'tipo_memoria_id' => $data['mem']->id,
                'capacidad_gb' => $data['cap'], 'modulos' => $data['mod'],
                'capacidad_total_gb' => $data['cap'] * $data['mod'],
                'velocidad_mhz' => $data['mhz'], 'latencia_cas' => $data['cl'],
                'voltaje' => $data['v'], 'factor_forma' => 'DIMM', 'altura_mm' => 44,
                'tiene_rgb' => $data['rgb'], 'ecc' => false, 'xmp' => $data['xmp'], 'expo' => $data['xmp'],
            ]);
            $this->precios($comp, $data['precios']);
        }
    }

    // ── Placas Base ───────────────────────────────────────────

    private function seedPlacasBase(): void
    {
        $asus     = Marca::where('nombre', 'ASUS')->first();
        $msi      = Marca::where('nombre', 'MSI')->first();
        $gigabyte = Marca::where('nombre', 'Gigabyte')->first();
        $asrock   = Marca::where('nombre', 'ASRock')->first();
        $am5      = Socket::where('nombre', 'AM5')->first();
        $am4      = Socket::where('nombre', 'AM4')->first();
        $lga1700  = Socket::where('nombre', 'LGA1700')->first();
        $lga1851  = Socket::where('nombre', 'LGA1851')->first();
        $ddr4     = TipoMemoria::where('nombre', 'DDR4')->first();
        $ddr5     = TipoMemoria::where('nombre', 'DDR5')->first();
        $atx      = FactorForma::where('nombre', 'ATX')->first();
        $matx     = FactorForma::where('nombre', 'mATX')->first();
        $itx      = FactorForma::where('nombre', 'ITX')->first();
        $pcie50   = VersionPCIe::where('nombre', 'PCIe 5.0')->first();
        $pcie40   = VersionPCIe::where('nombre', 'PCIe 4.0')->first();
        $x670e    = Chipset::where('nombre', 'X670E')->first();
        $x670     = Chipset::where('nombre', 'X670')->first();
        $b650e    = Chipset::where('nombre', 'B650E')->first();
        $b650     = Chipset::where('nombre', 'B650')->first();
        $x570     = Chipset::where('nombre', 'X570')->first();
        $b550     = Chipset::where('nombre', 'B550')->first();
        $z790     = Chipset::where('nombre', 'Z790')->first();
        $b760     = Chipset::where('nombre', 'B760')->first();
        $z890     = Chipset::where('nombre', 'Z890')->first();
        $b860     = Chipset::where('nombre', 'B860')->first();

        $placas = [
            // AM5 X670E
            ['nombre' => 'ASUS ROG Crosshair X670E Hero', 'marca' => $asus, 'socket' => $am5, 'chipset' => $x670e, 'ff' => $atx, 'mem' => $ddr5, 'pcie' => $pcie50,
             'slots_mem' => 4, 'mem_max' => 256, 'mem_mhz' => 6400, 'slots_x16' => 2, 'slots_m2' => 5, 'sata' => 6, 'wifi' => true, 'bt' => true,
             'precios' => ['PCComponentes' => 449.99, 'Amazon España' => 469.99, 'Alternate' => 444.99]],

            ['nombre' => 'MSI MEG X670E Ace', 'marca' => $msi, 'socket' => $am5, 'chipset' => $x670e, 'ff' => $atx, 'mem' => $ddr5, 'pcie' => $pcie50,
             'slots_mem' => 4, 'mem_max' => 256, 'mem_mhz' => 6400, 'slots_x16' => 2, 'slots_m2' => 5, 'sata' => 6, 'wifi' => true, 'bt' => true,
             'precios' => ['PCComponentes' => 479.99, 'Amazon España' => 499.99, 'Coolmod' => 469.99]],

            // AM5 B650E
            ['nombre' => 'ASUS ROG Strix B650E-F Gaming WiFi', 'marca' => $asus, 'socket' => $am5, 'chipset' => $b650e, 'ff' => $atx, 'mem' => $ddr5, 'pcie' => $pcie50,
             'slots_mem' => 4, 'mem_max' => 256, 'mem_mhz' => 6000, 'slots_x16' => 1, 'slots_m2' => 4, 'sata' => 4, 'wifi' => true, 'bt' => true,
             'precios' => ['PCComponentes' => 279.99, 'Amazon España' => 299.99, 'Alternate' => 274.99]],

            // AM5 B650
            ['nombre' => 'MSI MAG B650 Tomahawk WiFi', 'marca' => $msi, 'socket' => $am5, 'chipset' => $b650, 'ff' => $atx, 'mem' => $ddr5, 'pcie' => $pcie40,
             'slots_mem' => 4, 'mem_max' => 128, 'mem_mhz' => 5600, 'slots_x16' => 1, 'slots_m2' => 3, 'sata' => 6, 'wifi' => true, 'bt' => true,
             'precios' => ['PCComponentes' => 189.99, 'Amazon España' => 199.99, 'Coolmod' => 184.99]],

            ['nombre' => 'Gigabyte B650 Aorus Elite AX', 'marca' => $gigabyte, 'socket' => $am5, 'chipset' => $b650, 'ff' => $atx, 'mem' => $ddr5, 'pcie' => $pcie40,
             'slots_mem' => 4, 'mem_max' => 192, 'mem_mhz' => 5600, 'slots_x16' => 1, 'slots_m2' => 4, 'sata' => 6, 'wifi' => true, 'bt' => true,
             'precios' => ['PCComponentes' => 199.99, 'Amazon España' => 209.99, 'Alternate' => 194.99]],

            ['nombre' => 'ASRock B650M Pro RS WiFi', 'marca' => $asrock, 'socket' => $am5, 'chipset' => $b650, 'ff' => $matx, 'mem' => $ddr5, 'pcie' => $pcie40,
             'slots_mem' => 4, 'mem_max' => 128, 'mem_mhz' => 5600, 'slots_x16' => 1, 'slots_m2' => 2, 'sata' => 4, 'wifi' => true, 'bt' => true,
             'precios' => ['PCComponentes' => 129.99, 'Amazon España' => 139.99, 'Coolmod' => 124.99]],

            // AM4 X570
            ['nombre' => 'ASUS TUF Gaming X570-Plus WiFi', 'marca' => $asus, 'socket' => $am4, 'chipset' => $x570, 'ff' => $atx, 'mem' => $ddr4, 'pcie' => $pcie40,
             'slots_mem' => 4, 'mem_max' => 128, 'mem_mhz' => 4400, 'slots_x16' => 2, 'slots_m2' => 3, 'sata' => 6, 'wifi' => true, 'bt' => true,
             'precios' => ['PCComponentes' => 149.99, 'Amazon España' => 159.99, 'Alternate' => 144.99]],

            // AM4 B550
            ['nombre' => 'MSI MAG B550 Tomahawk', 'marca' => $msi, 'socket' => $am4, 'chipset' => $b550, 'ff' => $atx, 'mem' => $ddr4, 'pcie' => $pcie40,
             'slots_mem' => 4, 'mem_max' => 128, 'mem_mhz' => 5100, 'slots_x16' => 1, 'slots_m2' => 2, 'sata' => 6, 'wifi' => false, 'bt' => false,
             'precios' => ['PCComponentes' => 99.99, 'Amazon España' => 109.99, 'Coolmod' => 97.99]],

            // LGA1700 Z790
            ['nombre' => 'ASUS ROG Maximus Z790 Hero', 'marca' => $asus, 'socket' => $lga1700, 'chipset' => $z790, 'ff' => $atx, 'mem' => $ddr5, 'pcie' => $pcie50,
             'slots_mem' => 4, 'mem_max' => 192, 'mem_mhz' => 7800, 'slots_x16' => 2, 'slots_m2' => 5, 'sata' => 6, 'wifi' => true, 'bt' => true,
             'precios' => ['PCComponentes' => 549.99, 'Amazon España' => 579.99, 'Alternate' => 539.99]],

            ['nombre' => 'MSI MAG Z790 Tomahawk WiFi', 'marca' => $msi, 'socket' => $lga1700, 'chipset' => $z790, 'ff' => $atx, 'mem' => $ddr5, 'pcie' => $pcie50,
             'slots_mem' => 4, 'mem_max' => 192, 'mem_mhz' => 7200, 'slots_x16' => 1, 'slots_m2' => 4, 'sata' => 6, 'wifi' => true, 'bt' => true,
             'precios' => ['PCComponentes' => 229.99, 'Amazon España' => 249.99, 'Coolmod' => 224.99]],

            // LGA1700 B760
            ['nombre' => 'Gigabyte B760 Gaming X AX WiFi', 'marca' => $gigabyte, 'socket' => $lga1700, 'chipset' => $b760, 'ff' => $atx, 'mem' => $ddr5, 'pcie' => $pcie50,
             'slots_mem' => 4, 'mem_max' => 192, 'mem_mhz' => 7600, 'slots_x16' => 1, 'slots_m2' => 3, 'sata' => 4, 'wifi' => true, 'bt' => true,
             'precios' => ['PCComponentes' => 159.99, 'Amazon España' => 169.99, 'Alternate' => 154.99]],

            // LGA1851 Z890
            ['nombre' => 'ASUS ROG Maximus Z890 Apex', 'marca' => $asus, 'socket' => $lga1851, 'chipset' => $z890, 'ff' => $atx, 'mem' => $ddr5, 'pcie' => $pcie50,
             'slots_mem' => 4, 'mem_max' => 256, 'mem_mhz' => 9000, 'slots_x16' => 2, 'slots_m2' => 6, 'sata' => 6, 'wifi' => true, 'bt' => true,
             'precios' => ['PCComponentes' => 649.99, 'Amazon España' => 679.99, 'Alternate' => 639.99]],

            // LGA1851 B860
            ['nombre' => 'MSI PRO B860M-P WiFi', 'marca' => $msi, 'socket' => $lga1851, 'chipset' => $b860, 'ff' => $matx, 'mem' => $ddr5, 'pcie' => $pcie50,
             'slots_mem' => 4, 'mem_max' => 192, 'mem_mhz' => 6400, 'slots_x16' => 1, 'slots_m2' => 2, 'sata' => 4, 'wifi' => true, 'bt' => true,
             'precios' => ['PCComponentes' => 139.99, 'Amazon España' => 149.99, 'Coolmod' => 134.99]],
        ];

        foreach ($placas as $data) {
            $comp = $this->componente([
                'nombre' => $data['nombre'], 'marca_id' => $data['marca']->id,
                'categoria' => 'placa_base',
                'descripcion' => $data['nombre'] . ', socket ' . $data['socket']->nombre . ', ' . $data['ff']->nombre,
            ]);
            PlacaBase::create([
                'componente_id' => $comp->id, 'socket_id' => $data['socket']->id,
                'chipset_id' => $data['chipset']->id, 'factor_forma_id' => $data['ff']->id,
                'tipo_memoria_id' => $data['mem']->id, 'version_pcie_id' => $data['pcie']->id,
                'slots_memoria' => $data['slots_mem'], 'memoria_max_gb' => $data['mem_max'],
                'frecuencia_memoria_max_mhz' => $data['mem_mhz'],
                'slots_pcie_x16' => $data['slots_x16'], 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1,
                'slots_m2' => $data['slots_m2'], 'puertos_sata' => $data['sata'],
                'puertos_usb_traseros' => ['4xUSB 3.2', '2xUSB 2.0'],
                'conector_atx' => '24pin', 'conector_cpu' => '8+4pin',
                'wifi' => $data['wifi'], 'bluetooth' => $data['bt'], 'thunderbolt' => false,
                'audio_chipset' => 'Realtek ALC4080', 'lan_chipset' => 'Intel I225-V', 'lan_velocidad_gbps' => 2.5,
            ]);
            $this->precios($comp, $data['precios']);
        }
    }

    // ── Almacenamientos ───────────────────────────────────────

    private function seedAlmacenamientos(): void
    {
        $samsung  = Marca::where('nombre', 'Samsung')->first();
        $wd       = Marca::where('nombre', 'WD')->first();
        $seagate  = Marca::where('nombre', 'Seagate')->first();
        $crucial  = Marca::where('nombre', 'Crucial')->first();
        $kingston = Marca::where('nombre', 'Kingston')->first();
        $skhynix  = Marca::where('nombre', 'SK Hynix')->first();

        $nvme50 = InterfazAlmacenamiento::where('nombre', 'NVMe PCIe 5.0')->first();
        $nvme40 = InterfazAlmacenamiento::where('nombre', 'NVMe PCIe 4.0')->first();
        $nvme30 = InterfazAlmacenamiento::where('nombre', 'NVMe PCIe 3.0')->first();
        $sata   = InterfazAlmacenamiento::where('nombre', 'SATA III')->first();

        $m2_2280 = FactorFormaAlmacenamiento::where('nombre', 'M.2 2280')->first();
        $ff25    = FactorFormaAlmacenamiento::where('nombre', '2.5"')->first();
        $ff35    = FactorFormaAlmacenamiento::where('nombre', '3.5"')->first();

        $tlc = TipoNAND::where('nombre', 'TLC')->first();
        $qlc = TipoNAND::where('nombre', 'QLC')->first();

        $almacenamientos = [
            // NVMe PCIe 5.0
            ['nombre' => 'Samsung 9100 Pro 2TB NVMe PCIe 5.0', 'marca' => $samsung, 'interfaz' => $nvme50, 'ff' => $m2_2280, 'nand' => $tlc,
             'cap' => 2000, 'lect' => 14800, 'escr' => 13400,
             'precios' => ['PCComponentes' => 289.99, 'Amazon España' => 299.99, 'Alternate' => 284.99]],

            ['nombre' => 'WD Black SN850X 2TB NVMe PCIe 5.0', 'marca' => $wd, 'interfaz' => $nvme50, 'ff' => $m2_2280, 'nand' => $tlc,
             'cap' => 2000, 'lect' => 14900, 'escr' => 13000,
             'precios' => ['PCComponentes' => 269.99, 'Amazon España' => 279.99, 'Coolmod' => 264.99]],

            // NVMe PCIe 4.0
            ['nombre' => 'Samsung 990 Pro 2TB NVMe PCIe 4.0', 'marca' => $samsung, 'interfaz' => $nvme40, 'ff' => $m2_2280, 'nand' => $tlc,
             'cap' => 2000, 'lect' => 7450, 'escr' => 6900,
             'precios' => ['PCComponentes' => 149.99, 'Amazon España' => 159.99, 'Alternate' => 144.99]],

            ['nombre' => 'Samsung 990 Pro 1TB NVMe PCIe 4.0', 'marca' => $samsung, 'interfaz' => $nvme40, 'ff' => $m2_2280, 'nand' => $tlc,
             'cap' => 1000, 'lect' => 7450, 'escr' => 6900,
             'precios' => ['PCComponentes' => 89.99, 'Amazon España' => 94.99, 'Coolmod' => 87.99]],

            ['nombre' => 'WD Black SN850X 1TB NVMe PCIe 4.0', 'marca' => $wd, 'interfaz' => $nvme40, 'ff' => $m2_2280, 'nand' => $tlc,
             'cap' => 1000, 'lect' => 7300, 'escr' => 6600,
             'precios' => ['PCComponentes' => 84.99, 'Amazon España' => 89.99, 'Alternate' => 82.99]],

            ['nombre' => 'SK Hynix Platinum P41 2TB NVMe PCIe 4.0', 'marca' => $skhynix, 'interfaz' => $nvme40, 'ff' => $m2_2280, 'nand' => $tlc,
             'cap' => 2000, 'lect' => 7000, 'escr' => 6500,
             'precios' => ['PCComponentes' => 134.99, 'Amazon España' => 139.99, 'Coolmod' => 129.99]],

            ['nombre' => 'Crucial T705 2TB NVMe PCIe 5.0', 'marca' => $crucial, 'interfaz' => $nvme50, 'ff' => $m2_2280, 'nand' => $tlc,
             'cap' => 2000, 'lect' => 14500, 'escr' => 12700,
             'precios' => ['PCComponentes' => 249.99, 'Amazon España' => 259.99, 'Alternate' => 244.99]],

            ['nombre' => 'Kingston KC3000 2TB NVMe PCIe 4.0', 'marca' => $kingston, 'interfaz' => $nvme40, 'ff' => $m2_2280, 'nand' => $tlc,
             'cap' => 2000, 'lect' => 7000, 'escr' => 7000,
             'precios' => ['PCComponentes' => 119.99, 'Amazon España' => 129.99, 'Coolmod' => 114.99]],

            // NVMe PCIe 3.0
            ['nombre' => 'Samsung 970 EVO Plus 1TB NVMe PCIe 3.0', 'marca' => $samsung, 'interfaz' => $nvme30, 'ff' => $m2_2280, 'nand' => $tlc,
             'cap' => 1000, 'lect' => 3500, 'escr' => 3300,
             'precios' => ['PCComponentes' => 64.99, 'Amazon España' => 69.99, 'MediaMarkt' => 74.99]],

            // SATA SSD
            ['nombre' => 'Samsung 870 EVO 1TB SATA', 'marca' => $samsung, 'interfaz' => $sata, 'ff' => $ff25, 'nand' => $tlc,
             'cap' => 1000, 'lect' => 560, 'escr' => 530,
             'precios' => ['PCComponentes' => 74.99, 'Amazon España' => 79.99, 'Alternate' => 72.99]],

            ['nombre' => 'Samsung 870 EVO 2TB SATA', 'marca' => $samsung, 'interfaz' => $sata, 'ff' => $ff25, 'nand' => $tlc,
             'cap' => 2000, 'lect' => 560, 'escr' => 530,
             'precios' => ['PCComponentes' => 124.99, 'Amazon España' => 129.99, 'Coolmod' => 119.99]],

            ['nombre' => 'Crucial MX500 2TB SATA', 'marca' => $crucial, 'interfaz' => $sata, 'ff' => $ff25, 'nand' => $tlc,
             'cap' => 2000, 'lect' => 560, 'escr' => 510,
             'precios' => ['PCComponentes' => 99.99, 'Amazon España' => 109.99, 'MediaMarkt' => 114.99]],

            // HDD
            ['nombre' => 'Seagate Barracuda 4TB HDD', 'marca' => $seagate, 'interfaz' => $sata, 'ff' => $ff35, 'nand' => null,
             'cap' => 4000, 'lect' => 190, 'escr' => 190,
             'precios' => ['PCComponentes' => 74.99, 'Amazon España' => 79.99, 'Alternate' => 72.99]],

            ['nombre' => 'WD Blue 4TB HDD', 'marca' => $wd, 'interfaz' => $sata, 'ff' => $ff35, 'nand' => null,
             'cap' => 4000, 'lect' => 180, 'escr' => 180,
             'precios' => ['PCComponentes' => 69.99, 'Amazon España' => 74.99, 'Coolmod' => 67.99]],

            ['nombre' => 'Seagate IronWolf 8TB NAS HDD', 'marca' => $seagate, 'interfaz' => $sata, 'ff' => $ff35, 'nand' => null,
             'cap' => 8000, 'lect' => 210, 'escr' => 210,
             'precios' => ['PCComponentes' => 174.99, 'Amazon España' => 184.99, 'Alternate' => 169.99]],
        ];

        foreach ($almacenamientos as $data) {
            $tipo = $data['interfaz']->nombre === 'SATA III' ?
                ($data['ff']->nombre === '3.5"' ? 'hdd' : 'ssd') : 'nvme';

            $comp = $this->componente([
                'nombre' => $data['nombre'], 'marca_id' => $data['marca']->id,
                'categoria' => 'almacenamiento',
                'descripcion' => $data['nombre'] . ', ' . $data['cap'] . 'GB, ' . $data['interfaz']->nombre,
            ]);
            Almacenamiento::create([
                'componente_id' => $comp->id, 'interfaz_id' => $data['interfaz']->id,
                'factor_forma_id' => $data['ff']->id, 'tipo_nand_id' => $data['nand']?->id,
                'tipo' => $tipo, 'capacidad_gb' => $data['cap'],
                'velocidad_lectura_mbs' => $data['lect'], 'velocidad_escritura_mbs' => $data['escr'],
                'rpm' => $tipo === 'hdd' ? 7200 : null, 'dram' => $tipo !== 'hdd',
            ]);
            $this->precios($comp, $data['precios']);
        }
    }

    // ── PSUs ──────────────────────────────────────────────────

    private function seedPSUs(): void
    {
        $seasonic = Marca::where('nombre', 'Seasonic')->first();
        $corsair  = Marca::where('nombre', 'Corsair')->first();
        $bequiet  = Marca::where('nombre', 'be quiet!')->first();
        $asus     = Marca::where('nombre', 'ASUS')->first();
        $msi      = Marca::where('nombre', 'MSI')->first();

        $gold     = CertificacionPSU::where('nombre', '80+ Gold')->first();
        $platinum = CertificacionPSU::where('nombre', '80+ Platinum')->first();
        $titanium = CertificacionPSU::where('nombre', '80+ Titanium')->first();
        $bronze   = CertificacionPSU::where('nombre', '80+ Bronze')->first();
        $atx      = TipoPSU::where('nombre', 'ATX')->first();
        $sfx      = TipoPSU::where('nombre', 'SFX')->first();
        $sfxl     = TipoPSU::where('nombre', 'SFX-L')->first();

        $psus = [
            // Corsair
            ['nombre' => 'Corsair RM1000x SHIFT 1000W 80+ Gold', 'marca' => $corsair, 'cert' => $gold, 'tipo' => $atx,
             'vatios' => 1000, 'modular' => 'full_modular', 'version_atx' => 'ATX 3.0', 'largo' => 150, 'vent' => 135,
             'precios' => ['PCComponentes' => 179.99, 'Amazon España' => 189.99, 'Coolmod' => 174.99]],

            ['nombre' => 'Corsair RM850x 850W 80+ Gold', 'marca' => $corsair, 'cert' => $gold, 'tipo' => $atx,
             'vatios' => 850, 'modular' => 'full_modular', 'version_atx' => 'ATX 3.0', 'largo' => 150, 'vent' => 135,
             'precios' => ['PCComponentes' => 139.99, 'Amazon España' => 149.99, 'Alternate' => 134.99]],

            ['nombre' => 'Corsair RM750e 750W 80+ Gold', 'marca' => $corsair, 'cert' => $gold, 'tipo' => $atx,
             'vatios' => 750, 'modular' => 'full_modular', 'version_atx' => 'ATX 3.0', 'largo' => 140, 'vent' => 120,
             'precios' => ['PCComponentes' => 99.99, 'Amazon España' => 109.99, 'Coolmod' => 97.99]],

            ['nombre' => 'Corsair HX1200i 1200W 80+ Platinum', 'marca' => $corsair, 'cert' => $platinum, 'tipo' => $atx,
             'vatios' => 1200, 'modular' => 'full_modular', 'version_atx' => 'ATX 3.0', 'largo' => 160, 'vent' => 135,
             'precios' => ['PCComponentes' => 249.99, 'Amazon España' => 269.99, 'Alternate' => 244.99]],

            // Seasonic
            ['nombre' => 'Seasonic Focus GX-1000 1000W 80+ Gold', 'marca' => $seasonic, 'cert' => $gold, 'tipo' => $atx,
             'vatios' => 1000, 'modular' => 'full_modular', 'version_atx' => 'ATX 3.0', 'largo' => 140, 'vent' => 120,
             'precios' => ['PCComponentes' => 169.99, 'Amazon España' => 179.99, 'Coolmod' => 164.99]],

            ['nombre' => 'Seasonic Focus GX-850 850W 80+ Gold', 'marca' => $seasonic, 'cert' => $gold, 'tipo' => $atx,
             'vatios' => 850, 'modular' => 'full_modular', 'version_atx' => 'ATX 3.0', 'largo' => 140, 'vent' => 120,
             'precios' => ['PCComponentes' => 139.99, 'Amazon España' => 149.99, 'Alternate' => 134.99]],

            ['nombre' => 'Seasonic Vertex PX-1200 1200W 80+ Platinum', 'marca' => $seasonic, 'cert' => $platinum, 'tipo' => $atx,
             'vatios' => 1200, 'modular' => 'full_modular', 'version_atx' => 'ATX 3.0', 'largo' => 160, 'vent' => 135,
             'precios' => ['PCComponentes' => 229.99, 'Amazon España' => 249.99, 'Coolmod' => 224.99]],

            // be quiet!
            ['nombre' => 'be quiet! Dark Power 13 1000W 80+ Titanium', 'marca' => $bequiet, 'cert' => $titanium, 'tipo' => $atx,
             'vatios' => 1000, 'modular' => 'full_modular', 'version_atx' => 'ATX 3.0', 'largo' => 160, 'vent' => 135,
             'precios' => ['PCComponentes' => 229.99, 'Amazon España' => 249.99, 'Alternate' => 224.99]],

            ['nombre' => 'be quiet! Pure Power 12M 850W 80+ Gold', 'marca' => $bequiet, 'cert' => $gold, 'tipo' => $atx,
             'vatios' => 850, 'modular' => 'full_modular', 'version_atx' => 'ATX 3.0', 'largo' => 140, 'vent' => 120,
             'precios' => ['PCComponentes' => 119.99, 'Amazon España' => 129.99, 'Coolmod' => 114.99]],

            ['nombre' => 'be quiet! Straight Power 12 1000W 80+ Platinum', 'marca' => $bequiet, 'cert' => $platinum, 'tipo' => $atx,
             'vatios' => 1000, 'modular' => 'full_modular', 'version_atx' => 'ATX 3.0', 'largo' => 160, 'vent' => 135,
             'precios' => ['PCComponentes' => 189.99, 'Amazon España' => 199.99, 'Alternate' => 184.99]],

            // SFX
            ['nombre' => 'Corsair SF750 750W SFX 80+ Platinum', 'marca' => $corsair, 'cert' => $platinum, 'tipo' => $sfx,
             'vatios' => 750, 'modular' => 'full_modular', 'version_atx' => 'SFX', 'largo' => 100, 'vent' => 92,
             'precios' => ['PCComponentes' => 159.99, 'Amazon España' => 169.99, 'Coolmod' => 154.99]],

            ['nombre' => 'Seasonic Focus SGX-650 650W SFX-L 80+ Gold', 'marca' => $seasonic, 'cert' => $gold, 'tipo' => $sfxl,
             'vatios' => 650, 'modular' => 'full_modular', 'version_atx' => 'SFX-L', 'largo' => 130, 'vent' => 120,
             'precios' => ['PCComponentes' => 119.99, 'Amazon España' => 129.99, 'Alternate' => 114.99]],

            // Entry level
            ['nombre' => 'MSI MAG A650BN 650W 80+ Bronze', 'marca' => $msi, 'cert' => $bronze, 'tipo' => $atx,
             'vatios' => 650, 'modular' => 'no_modular', 'version_atx' => 'ATX', 'largo' => 140, 'vent' => 120,
             'precios' => ['PCComponentes' => 59.99, 'Amazon España' => 64.99, 'MediaMarkt' => 69.99]],
        ];

        foreach ($psus as $data) {
            $comp = $this->componente([
                'nombre' => $data['nombre'], 'marca_id' => $data['marca']->id,
                'fabricante_id' => $data['marca']->id, 'categoria' => 'psu',
                'descripcion' => $data['nombre'] . ', ' . $data['vatios'] . 'W',
            ]);
            PSU::create([
                'componente_id' => $comp->id, 'certificacion_id' => $data['cert']->id,
                'tipo_psu_id' => $data['tipo']->id, 'vatios' => $data['vatios'],
                'modular' => $data['modular'], 'version_atx' => $data['version_atx'],
                'conectores_pcie_16pin' => $data['vatios'] >= 1000 ? 2 : 1,
                'conectores_pcie_8pin' => 4, 'conectores_sata' => 12, 'conectores_molex' => 4,
                'largo_mm' => $data['largo'], 'ventilador_mm' => $data['vent'], 'ventilador_zero_rpm' => true,
            ]);
            $this->precios($comp, $data['precios']);
        }
    }

    // ── Gabinetes ─────────────────────────────────────────────

    private function seedGabinetes(): void
    {
        $fractal  = Marca::where('nombre', 'Fractal Design')->first();
        $lianli   = Marca::where('nombre', 'Lian Li')->first();
        $nzxt     = Marca::where('nombre', 'NZXT')->first();
        $bequiet  = Marca::where('nombre', 'be quiet!')->first();
        $phanteks = Marca::where('nombre', 'Phanteks')->first();
        $corsair  = Marca::where('nombre', 'Corsair')->first();

        $fullTower = TipoGabinete::where('nombre', 'Full Tower')->first();
        $midTower  = TipoGabinete::where('nombre', 'Mid Tower')->first();
        $miniTower = TipoGabinete::where('nombre', 'Mini Tower')->first();
        $miniITX   = TipoGabinete::where('nombre', 'Mini-ITX')->first();
        $sff       = TipoGabinete::where('nombre', 'SFF')->first();

        $conv      = EstructuraGabinete::where('nombre', 'Convencional')->first();
        $sandwich  = EstructuraGabinete::where('nombre', 'Sandwich')->first();
        $sandwichV = EstructuraGabinete::where('nombre', 'Sandwich variable')->first();

        $atx  = FactorForma::where('nombre', 'ATX')->first();
        $matx = FactorForma::where('nombre', 'mATX')->first();
        $itx  = FactorForma::where('nombre', 'ITX')->first();
        $eatx = FactorForma::where('nombre', 'E-ATX')->first();
        $atxPsu  = TipoPSU::where('nombre', 'ATX')->first();
        $sfxPsu  = TipoPSU::where('nombre', 'SFX')->first();
        $sfxlPsu = TipoPSU::where('nombre', 'SFX-L')->first();

        $gabinetes = [
            // Fractal Design
            ['nombre' => 'Fractal Design Define 7 XL', 'marca' => $fractal, 'tipo' => $fullTower, 'estructura' => $conv,
             'ancho' => 232, 'alto' => 568, 'fondo' => 583, 'gpu_max' => 491, 'cooler_max' => 185, 'psu_max' => 300,
             'rad' => [120, 140, 240, 280, 360, 420], 'ff' => [$atx, $matx, $eatx], 'psu_tipos' => [$atxPsu],
             'bahias35' => 3, 'bahias25' => 3, 'vent_front' => 3, 'vent_top' => 3, 'vent_rear' => 1, 'panel_frontal' => 'glass',
             'precios' => ['PCComponentes' => 189.99, 'Amazon España' => 199.99, 'Alternate' => 184.99]],

            ['nombre' => 'Fractal Design Define 7', 'marca' => $fractal, 'tipo' => $midTower, 'estructura' => $conv,
             'ancho' => 214, 'alto' => 475, 'fondo' => 543, 'gpu_max' => 491, 'cooler_max' => 185, 'psu_max' => 300,
             'rad' => [120, 140, 240, 280, 360], 'ff' => [$atx, $matx], 'psu_tipos' => [$atxPsu],
             'bahias35' => 2, 'bahias25' => 3, 'vent_front' => 3, 'vent_top' => 2, 'vent_rear' => 1, 'panel_frontal' => 'glass',
             'precios' => ['PCComponentes' => 139.99, 'Amazon España' => 149.99, 'Coolmod' => 134.99]],

            ['nombre' => 'Fractal Design Pop Air', 'marca' => $fractal, 'tipo' => $midTower, 'estructura' => $conv,
             'ancho' => 213, 'alto' => 464, 'fondo' => 427, 'gpu_max' => 380, 'cooler_max' => 168, 'psu_max' => 200,
             'rad' => [120, 240], 'ff' => [$atx, $matx], 'psu_tipos' => [$atxPsu],
             'bahias35' => 2, 'bahias25' => 2, 'vent_front' => 3, 'vent_top' => 2, 'vent_rear' => 1, 'panel_frontal' => 'mesh',
             'precios' => ['PCComponentes' => 79.99, 'Amazon España' => 84.99, 'Alternate' => 77.99]],

            // Lian Li
            ['nombre' => 'Lian Li O11 Dynamic EVO', 'marca' => $lianli, 'tipo' => $midTower, 'estructura' => $sandwich,
             'ancho' => 285, 'alto' => 459, 'fondo' => 459, 'gpu_max' => 420, 'cooler_max' => 167, 'psu_max' => 200,
             'rad' => [120, 240, 360], 'ff' => [$atx, $matx, $eatx], 'psu_tipos' => [$atxPsu],
             'bahias35' => 0, 'bahias25' => 6, 'vent_front' => 0, 'vent_top' => 3, 'vent_rear' => 1, 'panel_frontal' => 'glass',
             'precios' => ['PCComponentes' => 129.99, 'Amazon España' => 139.99, 'Coolmod' => 124.99]],

            ['nombre' => 'Lian Li O11 Air Mini', 'marca' => $lianli, 'tipo' => $miniTower, 'estructura' => $sandwich,
             'ancho' => 230, 'alto' => 390, 'fondo' => 350, 'gpu_max' => 340, 'cooler_max' => 157, 'psu_max' => 170,
             'rad' => [120, 240], 'ff' => [$matx, $itx], 'psu_tipos' => [$atxPsu],
             'bahias35' => 0, 'bahias25' => 3, 'vent_front' => 2, 'vent_top' => 2, 'vent_rear' => 1, 'panel_frontal' => 'glass',
             'precios' => ['PCComponentes' => 89.99, 'Amazon España' => 94.99, 'Alternate' => 87.99]],

            ['nombre' => 'Lian Li Terra', 'marca' => $lianli, 'tipo' => $miniITX, 'estructura' => $sandwichV,
             'ancho' => 195, 'alto' => 340, 'fondo' => 290, 'gpu_max' => 322, 'cooler_max' => 130, 'psu_max' => 130,
             'rad' => [120, 240], 'ff' => [$itx], 'psu_tipos' => [$sfxPsu, $sfxlPsu],
             'bahias35' => 0, 'bahias25' => 2, 'vent_front' => 0, 'vent_top' => 2, 'vent_rear' => 1, 'panel_frontal' => 'glass',
             'precios' => ['PCComponentes' => 109.99, 'Amazon España' => 119.99, 'Coolmod' => 104.99]],

            // NZXT
            ['nombre' => 'NZXT H9 Elite', 'marca' => $nzxt, 'tipo' => $midTower, 'estructura' => $sandwich,
             'ancho' => 280, 'alto' => 490, 'fondo' => 470, 'gpu_max' => 435, 'cooler_max' => 185, 'psu_max' => 220,
             'rad' => [120, 140, 240, 280, 360], 'ff' => [$atx, $matx, $eatx], 'psu_tipos' => [$atxPsu],
             'bahias35' => 0, 'bahias25' => 4, 'vent_front' => 0, 'vent_top' => 3, 'vent_rear' => 1, 'panel_frontal' => 'glass',
             'precios' => ['PCComponentes' => 219.99, 'Amazon España' => 229.99, 'Alternate' => 214.99]],

            ['nombre' => 'NZXT H7 Flow', 'marca' => $nzxt, 'tipo' => $midTower, 'estructura' => $conv,
             'ancho' => 230, 'alto' => 480, 'fondo' => 505, 'gpu_max' => 400, 'cooler_max' => 185, 'psu_max' => 220,
             'rad' => [120, 140, 240, 280, 360], 'ff' => [$atx, $matx], 'psu_tipos' => [$atxPsu],
             'bahias35' => 2, 'bahias25' => 2, 'vent_front' => 3, 'vent_top' => 2, 'vent_rear' => 1, 'panel_frontal' => 'mesh',
             'precios' => ['PCComponentes' => 129.99, 'Amazon España' => 139.99, 'Coolmod' => 124.99]],

            // be quiet!
            ['nombre' => 'be quiet! Silent Base 802', 'marca' => $bequiet, 'tipo' => $midTower, 'estructura' => $conv,
             'ancho' => 243, 'alto' => 513, 'fondo' => 553, 'gpu_max' => 369, 'cooler_max' => 185, 'psu_max' => 270,
             'rad' => [120, 140, 240, 280, 360], 'ff' => [$atx, $matx, $eatx], 'psu_tipos' => [$atxPsu],
             'bahias35' => 3, 'bahias25' => 3, 'vent_front' => 3, 'vent_top' => 3, 'vent_rear' => 1, 'panel_frontal' => 'glass',
             'precios' => ['PCComponentes' => 149.99, 'Amazon España' => 159.99, 'Alternate' => 144.99]],

            ['nombre' => 'be quiet! Pure Base 500DX', 'marca' => $bequiet, 'tipo' => $midTower, 'estructura' => $conv,
             'ancho' => 219, 'alto' => 450, 'fondo' => 450, 'gpu_max' => 369, 'cooler_max' => 185, 'psu_max' => 200,
             'rad' => [120, 140, 240, 280], 'ff' => [$atx, $matx], 'psu_tipos' => [$atxPsu],
             'bahias35' => 2, 'bahias25' => 2, 'vent_front' => 3, 'vent_top' => 2, 'vent_rear' => 1, 'panel_frontal' => 'glass',
             'precios' => ['PCComponentes' => 89.99, 'Amazon España' => 94.99, 'Coolmod' => 87.99]],

            // Phanteks
            ['nombre' => 'Phanteks Eclipse G500A DRGB', 'marca' => $phanteks, 'tipo' => $midTower, 'estructura' => $conv,
             'ancho' => 220, 'alto' => 510, 'fondo' => 470, 'gpu_max' => 435, 'cooler_max' => 190, 'psu_max' => 220,
             'rad' => [120, 140, 240, 280, 360], 'ff' => [$atx, $matx, $eatx], 'psu_tipos' => [$atxPsu],
             'bahias35' => 2, 'bahias25' => 2, 'vent_front' => 3, 'vent_top' => 3, 'vent_rear' => 1, 'panel_frontal' => 'mesh',
             'precios' => ['PCComponentes' => 109.99, 'Amazon España' => 119.99, 'Alternate' => 107.99]],

            // Corsair
            ['nombre' => 'Corsair 5000D Airflow', 'marca' => $corsair, 'tipo' => $midTower, 'estructura' => $conv,
             'ancho' => 230, 'alto' => 520, 'fondo' => 520, 'gpu_max' => 420, 'cooler_max' => 170, 'psu_max' => 225,
             'rad' => [120, 140, 240, 280, 360], 'ff' => [$atx, $matx, $eatx], 'psu_tipos' => [$atxPsu],
             'bahias35' => 2, 'bahias25' => 6, 'vent_front' => 3, 'vent_top' => 3, 'vent_rear' => 1, 'panel_frontal' => 'mesh',
             'precios' => ['PCComponentes' => 124.99, 'Amazon España' => 134.99, 'Coolmod' => 119.99]],
        ];

        foreach ($gabinetes as $data) {
            $comp = $this->componente([
                'nombre' => $data['nombre'], 'marca_id' => $data['marca']->id,
                'fabricante_id' => $data['marca']->id, 'categoria' => 'gabinete',
                'descripcion' => $data['nombre'] . ', ' . $data['tipo']->nombre,
            ]);
            $gab = Gabinete::create([
                'componente_id' => $comp->id, 'tipo_gabinete_id' => $data['tipo']->id,
                'estructura_gabinete_id' => $data['estructura']->id,
                'ancho_mm' => $data['ancho'], 'alto_mm' => $data['alto'], 'profundidad_mm' => $data['fondo'],
                'longitud_gpu_max_mm' => $data['gpu_max'], 'altura_cooler_max_mm' => $data['cooler_max'],
                'largo_psu_max_mm' => $data['psu_max'],
                'soporte_radiadores' => $data['rad'],
                'bahias_35' => $data['bahias35'], 'bahias_25' => $data['bahias25'],
                'ventiladores_frontales' => $data['vent_front'], 'ventiladores_superiores' => $data['vent_top'],
                'ventiladores_traseros' => $data['vent_rear'], 'ventiladores_incluidos' => 0,
                'puertos_usb_frontales' => ['2xUSB 3.0', '1xUSB-C'], 'montaje_vertical_pcie' => true,
                'panel_frontal' => $data['panel_frontal'],
            ]);
            // Factores de forma compatibles
            foreach ($data['ff'] as $ff) { $gab->factoresForma()->attach($ff->id); }
            // PSU tipos compatibles
            foreach ($data['psu_tipos'] as $pt) { $gab->tiposPSU()->attach($pt->id); }
            $this->precios($comp, $data['precios']);
        }
    }

    // ── Refrigeración Aire ────────────────────────────────────

    private function seedRefrigeracionAire(): void
    {
        $noctua   = Marca::where('nombre', 'Noctua')->first();
        $bequiet  = Marca::where('nombre', 'be quiet!')->first();
        $corsair  = Marca::where('nombre', 'Corsair')->first();
        $am5      = Socket::where('nombre', 'AM5')->first();
        $am4      = Socket::where('nombre', 'AM4')->first();
        $lga1700  = Socket::where('nombre', 'LGA1700')->first();
        $lga1851  = Socket::where('nombre', 'LGA1851')->first();
        $aire     = TipoRefrigeracion::where('nombre', 'Aire')->first();

        $enfriadores = [
            ['nombre' => 'Noctua NH-D15 G2', 'marca' => $noctua,
             'altura' => 168, 'tdp_max' => 300, 'vent' => 2, 'tam_vent' => 150, 'rpm_max' => 1500, 'db' => 24.6, 'rgb' => false,
             'sockets' => [$am5, $am4, $lga1700, $lga1851],
             'precios' => ['PCComponentes' => 109.99, 'Amazon España' => 119.99, 'Alternate' => 107.99]],

            ['nombre' => 'Noctua NH-U12S Redux', 'marca' => $noctua,
             'altura' => 158, 'tdp_max' => 180, 'vent' => 1, 'tam_vent' => 120, 'rpm_max' => 1500, 'db' => 22.4, 'rgb' => false,
             'sockets' => [$am5, $am4, $lga1700, $lga1851],
             'precios' => ['PCComponentes' => 49.99, 'Amazon España' => 54.99, 'Coolmod' => 47.99]],

            ['nombre' => 'Noctua NH-L9a-AM5', 'marca' => $noctua,
             'altura' => 37, 'tdp_max' => 65, 'vent' => 1, 'tam_vent' => 92, 'rpm_max' => 2500, 'db' => 23.6, 'rgb' => false,
             'sockets' => [$am5],
             'precios' => ['PCComponentes' => 44.99, 'Amazon España' => 49.99, 'Alternate' => 42.99]],

            ['nombre' => 'be quiet! Dark Rock Pro 5', 'marca' => $bequiet,
             'altura' => 168, 'tdp_max' => 270, 'vent' => 2, 'tam_vent' => 135, 'rpm_max' => 1500, 'db' => 24.3, 'rgb' => false,
             'sockets' => [$am5, $am4, $lga1700, $lga1851],
             'precios' => ['PCComponentes' => 89.99, 'Amazon España' => 94.99, 'Alternate' => 87.99]],

            ['nombre' => 'be quiet! Pure Rock 2', 'marca' => $bequiet,
             'altura' => 155, 'tdp_max' => 150, 'vent' => 1, 'tam_vent' => 120, 'rpm_max' => 1500, 'db' => 26.8, 'rgb' => false,
             'sockets' => [$am5, $am4, $lga1700, $lga1851],
             'precios' => ['PCComponentes' => 29.99, 'Amazon España' => 34.99, 'MediaMarkt' => 39.99]],

            ['nombre' => 'be quiet! Shadow Rock 3', 'marca' => $bequiet,
             'altura' => 165, 'tdp_max' => 190, 'vent' => 1, 'tam_vent' => 120, 'rpm_max' => 1600, 'db' => 25.5, 'rgb' => false,
             'sockets' => [$am5, $am4, $lga1700, $lga1851],
             'precios' => ['PCComponentes' => 44.99, 'Amazon España' => 49.99, 'Coolmod' => 42.99]],

            ['nombre' => 'Corsair A115 Dual Tower', 'marca' => $corsair,
             'altura' => 170, 'tdp_max' => 300, 'vent' => 2, 'tam_vent' => 140, 'rpm_max' => 1800, 'db' => 29.0, 'rgb' => false,
             'sockets' => [$am5, $am4, $lga1700, $lga1851],
             'precios' => ['PCComponentes' => 84.99, 'Amazon España' => 89.99, 'Alternate' => 82.99]],
        ];

        foreach ($enfriadores as $data) {
            $comp = $this->componente([
                'nombre' => $data['nombre'], 'marca_id' => $data['marca']->id,
                'fabricante_id' => $data['marca']->id, 'categoria' => 'refrigeracion_aire',
                'descripcion' => $data['nombre'] . ', TDP max ' . $data['tdp_max'] . 'W',
            ]);
            $ref = RefrigeracionAire::create([
                'componente_id' => $comp->id, 'tipo_refrigeracion_id' => $aire->id,
                'tdp_max_watts' => $data['tdp_max'], 'altura_mm' => $data['altura'],
                'num_ventiladores' => $data['vent'], 'tam_ventilador_mm' => $data['tam_vent'],
                'rpm_max' => $data['rpm_max'], 'ruido_db_max' => $data['db'],
                'tiene_rgb' => $data['rgb'], 'incluye_pasta_termica' => true,
            ]);
            foreach ($data['sockets'] as $socket) {
                $ref->socketsCompatibles()->attach($socket->id);
            }
            $this->precios($comp, $data['precios']);
        }
    }

    // ── Refrigeración Líquida ─────────────────────────────────

    private function seedRefrigeracionLiquida(): void
    {
        $corsair  = Marca::where('nombre', 'Corsair')->first();
        $nzxt     = Marca::where('nombre', 'NZXT')->first();
        $bequiet  = Marca::where('nombre', 'be quiet!')->first();
        $lianli   = Marca::where('nombre', 'Lian Li')->first();
        $am5      = Socket::where('nombre', 'AM5')->first();
        $am4      = Socket::where('nombre', 'AM4')->first();
        $lga1700  = Socket::where('nombre', 'LGA1700')->first();
        $lga1851  = Socket::where('nombre', 'LGA1851')->first();
        $aio      = TipoRefrigeracion::where('nombre', 'AIO')->first();

        $aios = [
            // 360mm
            ['nombre' => 'Corsair iCUE H150i Elite LCD XT 360mm', 'marca' => $corsair,
             'rad' => 360, 'ancho_rad' => 120, 'alto_rad' => 360, 'grosor_rad' => 27, 'tdp_max' => 400,
             'num_vent' => 3, 'tam_vent' => 120, 'rpm_max' => 2400, 'db_max' => 36.0, 'rgb' => true, 'pantalla' => true,
             'sockets' => [$am5, $am4, $lga1700, $lga1851],
             'precios' => ['PCComponentes' => 199.99, 'Amazon España' => 219.99, 'Alternate' => 194.99]],

            ['nombre' => 'NZXT Kraken 360 RGB', 'marca' => $nzxt,
             'rad' => 360, 'ancho_rad' => 120, 'alto_rad' => 360, 'grosor_rad' => 27, 'tdp_max' => 350,
             'num_vent' => 3, 'tam_vent' => 120, 'rpm_max' => 1800, 'db_max' => 33.0, 'rgb' => true, 'pantalla' => true,
             'sockets' => [$am5, $am4, $lga1700, $lga1851],
             'precios' => ['PCComponentes' => 179.99, 'Amazon España' => 189.99, 'Coolmod' => 174.99]],

            ['nombre' => 'be quiet! Pure Loop 2 FX 360mm', 'marca' => $bequiet,
             'rad' => 360, 'ancho_rad' => 120, 'alto_rad' => 360, 'grosor_rad' => 27, 'tdp_max' => 350,
             'num_vent' => 3, 'tam_vent' => 120, 'rpm_max' => 1800, 'db_max' => 34.0, 'rgb' => true, 'pantalla' => false,
             'sockets' => [$am5, $am4, $lga1700, $lga1851],
             'precios' => ['PCComponentes' => 149.99, 'Amazon España' => 159.99, 'Alternate' => 144.99]],

            ['nombre' => 'Lian Li Galahad II Trinity 360mm', 'marca' => $lianli,
             'rad' => 360, 'ancho_rad' => 120, 'alto_rad' => 360, 'grosor_rad' => 27, 'tdp_max' => 400,
             'num_vent' => 3, 'tam_vent' => 120, 'rpm_max' => 1900, 'db_max' => 32.0, 'rgb' => true, 'pantalla' => false,
             'sockets' => [$am5, $am4, $lga1700, $lga1851],
             'precios' => ['PCComponentes' => 139.99, 'Amazon España' => 149.99, 'Coolmod' => 134.99]],

            // 280mm
            ['nombre' => 'Corsair iCUE H115i Elite Capellix XT 280mm', 'marca' => $corsair,
             'rad' => 280, 'ancho_rad' => 140, 'alto_rad' => 280, 'grosor_rad' => 27, 'tdp_max' => 350,
             'num_vent' => 2, 'tam_vent' => 140, 'rpm_max' => 2000, 'db_max' => 37.0, 'rgb' => true, 'pantalla' => false,
             'sockets' => [$am5, $am4, $lga1700, $lga1851],
             'precios' => ['PCComponentes' => 159.99, 'Amazon España' => 169.99, 'Alternate' => 154.99]],

            ['nombre' => 'NZXT Kraken 280 RGB', 'marca' => $nzxt,
             'rad' => 280, 'ancho_rad' => 140, 'alto_rad' => 280, 'grosor_rad' => 27, 'tdp_max' => 300,
             'num_vent' => 2, 'tam_vent' => 140, 'rpm_max' => 1500, 'db_max' => 30.0, 'rgb' => true, 'pantalla' => true,
             'sockets' => [$am5, $am4, $lga1700, $lga1851],
             'precios' => ['PCComponentes' => 149.99, 'Amazon España' => 159.99, 'Coolmod' => 144.99]],

            // 240mm
            ['nombre' => 'Corsair iCUE H100i RGB Elite 240mm', 'marca' => $corsair,
             'rad' => 240, 'ancho_rad' => 120, 'alto_rad' => 240, 'grosor_rad' => 27, 'tdp_max' => 250,
             'num_vent' => 2, 'tam_vent' => 120, 'rpm_max' => 2400, 'db_max' => 37.0, 'rgb' => true, 'pantalla' => false,
             'sockets' => [$am5, $am4, $lga1700, $lga1851],
             'precios' => ['PCComponentes' => 109.99, 'Amazon España' => 119.99, 'Alternate' => 107.99]],

            ['nombre' => 'be quiet! Pure Loop 2 240mm', 'marca' => $bequiet,
             'rad' => 240, 'ancho_rad' => 120, 'alto_rad' => 240, 'grosor_rad' => 27, 'tdp_max' => 250,
             'num_vent' => 2, 'tam_vent' => 120, 'rpm_max' => 1800, 'db_max' => 31.0, 'rgb' => false, 'pantalla' => false,
             'sockets' => [$am5, $am4, $lga1700, $lga1851],
             'precios' => ['PCComponentes' => 89.99, 'Amazon España' => 94.99, 'Coolmod' => 87.99]],

            ['nombre' => 'NZXT Kraken 240 RGB', 'marca' => $nzxt,
             'rad' => 240, 'ancho_rad' => 120, 'alto_rad' => 240, 'grosor_rad' => 27, 'tdp_max' => 280,
             'num_vent' => 2, 'tam_vent' => 120, 'rpm_max' => 1800, 'db_max' => 32.0, 'rgb' => true, 'pantalla' => true,
             'sockets' => [$am5, $am4, $lga1700, $lga1851],
             'precios' => ['PCComponentes' => 119.99, 'Amazon España' => 129.99, 'Alternate' => 114.99]],
        ];

        foreach ($aios as $data) {
            $comp = $this->componente([
                'nombre' => $data['nombre'], 'marca_id' => $data['marca']->id,
                'fabricante_id' => $data['marca']->id, 'categoria' => 'refrigeracion_liquida',
                'descripcion' => $data['nombre'] . ', radiador ' . $data['rad'] . 'mm',
            ]);
            $ref = RefrigeracionLiquida::create([
                'componente_id' => $comp->id, 'tipo_refrigeracion_id' => $aio->id,
                'tdp_max_watts' => $data['tdp_max'],
                'tam_radiador_mm' => $data['rad'], 'ancho_radiador_mm' => $data['ancho_rad'],
                'alto_radiador_mm' => $data['alto_rad'], 'grosor_radiador_mm' => $data['grosor_rad'],
                'altura_bomba_mm' => 53, 'ancho_bomba_mm' => 53, 'profundidad_bomba_mm' => 53,
                'pantalla_cabezal' => $data['pantalla'],
                'num_ventiladores' => $data['num_vent'], 'tam_ventilador_mm' => $data['tam_vent'],
                'rpm_max' => $data['rpm_max'], 'ruido_db_max' => $data['db_max'],
                'flujo_personalizable' => false, 'incluye_pasta_termica' => true, 'tiene_rgb' => $data['rgb'],
            ]);
            foreach ($data['sockets'] as $socket) {
                $ref->socketsCompatibles()->attach($socket->id);
            }
            $this->precios($comp, $data['precios']);
        }
    }

    // ── Ventiladores ──────────────────────────────────────────

    private function seedVentiladores(): void
    {
        $noctua  = Marca::where('nombre', 'Noctua')->first();
        $bequiet = Marca::where('nombre', 'be quiet!')->first();
        $corsair = Marca::where('nombre', 'Corsair')->first();
        $lianli  = Marca::where('nombre', 'Lian Li')->first();

        $normal = TipoVentilador::where('nombre', 'Normal')->first();

        $ventiladores = [
            ['nombre' => 'Noctua NF-A12x25 PWM (pack 3)', 'marca' => $noctua, 'tipo' => $normal,
             'rpm_min' => 450, 'rpm_max' => 2000, 'db_min' => 9.3, 'db_max' => 22.6, 'cfm' => 60.1, 'num' => 3, 'rgb' => false, 'pwm' => true,
             'precios' => ['PCComponentes' => 74.99, 'Amazon España' => 79.99, 'Alternate' => 72.99]],

            ['nombre' => 'Noctua NF-A14 PWM (pack 3)', 'marca' => $noctua, 'tipo' => $normal,
             'rpm_min' => 300, 'rpm_max' => 1500, 'db_min' => 7.2, 'db_max' => 24.6, 'cfm' => 82.5, 'num' => 3, 'rgb' => false, 'pwm' => true,
             'precios' => ['PCComponentes' => 79.99, 'Amazon España' => 84.99, 'Coolmod' => 77.99]],

            ['nombre' => 'be quiet! Light Wings 120mm PWM (pack 3)', 'marca' => $bequiet, 'tipo' => $normal,
             'rpm_min' => 200, 'rpm_max' => 1900, 'db_min' => 5.0, 'db_max' => 28.5, 'cfm' => 54.7, 'num' => 3, 'rgb' => true, 'pwm' => true,
             'precios' => ['PCComponentes' => 59.99, 'Amazon España' => 64.99, 'Alternate' => 57.99]],

            ['nombre' => 'be quiet! Light Wings 140mm PWM (pack 3)', 'marca' => $bequiet, 'tipo' => $normal,
             'rpm_min' => 200, 'rpm_max' => 1600, 'db_min' => 5.0, 'db_max' => 31.1, 'cfm' => 75.1, 'num' => 3, 'rgb' => true, 'pwm' => true,
             'precios' => ['PCComponentes' => 64.99, 'Amazon España' => 69.99, 'Coolmod' => 62.99]],

            ['nombre' => 'Corsair AF120 Elite RGB (pack 3)', 'marca' => $corsair, 'tipo' => $normal,
             'rpm_min' => 400, 'rpm_max' => 2100, 'db_min' => 16.0, 'db_max' => 31.0, 'cfm' => 52.2, 'num' => 3, 'rgb' => true, 'pwm' => true,
             'precios' => ['PCComponentes' => 54.99, 'Amazon España' => 59.99, 'MediaMarkt' => 64.99]],

            ['nombre' => 'Corsair LL120 RGB (pack 3)', 'marca' => $corsair, 'tipo' => $normal,
             'rpm_min' => 600, 'rpm_max' => 1500, 'db_min' => 16.0, 'db_max' => 24.8, 'cfm' => 43.3, 'num' => 3, 'rgb' => true, 'pwm' => true,
             'precios' => ['PCComponentes' => 69.99, 'Amazon España' => 74.99, 'Alternate' => 67.99]],

            ['nombre' => 'Lian Li Uni Fan SL120 V2 RGB (pack 3)', 'marca' => $lianli, 'tipo' => $normal,
             'rpm_min' => 200, 'rpm_max' => 1900, 'db_min' => 15.0, 'db_max' => 30.0, 'cfm' => 52.7, 'num' => 3, 'rgb' => true, 'pwm' => true,
             'precios' => ['PCComponentes' => 79.99, 'Amazon España' => 84.99, 'Coolmod' => 77.99]],

            ['nombre' => 'Lian Li Uni Fan SL140 V2 RGB (pack 3)', 'marca' => $lianli, 'tipo' => $normal,
             'rpm_min' => 200, 'rpm_max' => 1600, 'db_min' => 15.0, 'db_max' => 29.0, 'cfm' => 72.0, 'num' => 3, 'rgb' => true, 'pwm' => true,
             'precios' => ['PCComponentes' => 89.99, 'Amazon España' => 94.99, 'Alternate' => 87.99]],

            ['nombre' => 'Noctua NF-A12x25 PWM (unidad)', 'marca' => $noctua, 'tipo' => $normal,
             'rpm_min' => 450, 'rpm_max' => 2000, 'db_min' => 9.3, 'db_max' => 22.6, 'cfm' => 60.1, 'num' => 1, 'rgb' => false, 'pwm' => true,
             'precios' => ['PCComponentes' => 29.99, 'Amazon España' => 32.99, 'Coolmod' => 28.99]],

            ['nombre' => 'be quiet! Pure Wings 3 120mm PWM (unidad)', 'marca' => $bequiet, 'tipo' => $normal,
             'rpm_min' => 300, 'rpm_max' => 1600, 'db_min' => 5.0, 'db_max' => 25.8, 'cfm' => 42.3, 'num' => 1, 'rgb' => false, 'pwm' => true,
             'precios' => ['PCComponentes' => 12.99, 'Amazon España' => 14.99, 'MediaMarkt' => 16.99]],
        ];

        foreach ($ventiladores as $data) {
            $comp = $this->componente([
                'nombre' => $data['nombre'], 'marca_id' => $data['marca']->id,
                'fabricante_id' => $data['marca']->id, 'categoria' => 'ventilador',
                'descripcion' => $data['nombre'] . ', pack de ' . $data['num'],
            ]);
            Ventilador::create([
                'componente_id' => $comp->id, 'tipo_ventilador_id' => $data['tipo']->id,
                'rpm_min' => $data['rpm_min'], 'rpm_max' => $data['rpm_max'],
                'ruido_db_min' => $data['db_min'], 'ruido_db_max' => $data['db_max'],
                'flujo_aire_cfm' => $data['cfm'], 'num_ventiladores' => $data['num'],
                'tiene_rgb' => $data['rgb'], 'pwm' => $data['pwm'],
            ]);
            $this->precios($comp, $data['precios']);
        }
    }
}
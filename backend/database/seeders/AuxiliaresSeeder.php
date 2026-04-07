<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Auxiliares\Socket;
use App\Models\Auxiliares\TipoMemoria;
use App\Models\Auxiliares\TipoVRAM;
use App\Models\Auxiliares\Arquitectura;
use App\Models\Auxiliares\Chipset;
use App\Models\Auxiliares\FactorForma;
use App\Models\Auxiliares\TipoGabinete;
use App\Models\Auxiliares\EstructuraGabinete;
use App\Models\Auxiliares\InterfazAlmacenamiento;
use App\Models\Auxiliares\FactorFormaAlmacenamiento;
use App\Models\Auxiliares\CertificacionPSU;
use App\Models\Auxiliares\TipoPSU;
use App\Models\Auxiliares\TipoNAND;
use App\Models\Auxiliares\VersionPCIe;
use App\Models\Auxiliares\TipoRefrigeracion;
use App\Models\Auxiliares\TipoVentilador;

class AuxiliaresSeeder extends Seeder
{
    public function run(): void
    {
        // Sockets
        $sockets = [
            ['nombre' => 'AM4',    'fabricante' => 'AMD',   'descripcion' => 'Socket AMD AM4 (Ryzen 1000-5000)'],
            ['nombre' => 'AM5',    'fabricante' => 'AMD',   'descripcion' => 'Socket AMD AM5 (Ryzen 7000+)'],
            ['nombre' => 'TR4',    'fabricante' => 'AMD',   'descripcion' => 'Socket AMD ThreadRipper TR4'],
            ['nombre' => 'TRX50',  'fabricante' => 'AMD',   'descripcion' => 'Socket AMD ThreadRipper Pro TRX50'],
            ['nombre' => 'LGA1700','fabricante' => 'Intel', 'descripcion' => 'Socket Intel LGA1700 (12th-13th Gen)'],
            ['nombre' => 'LGA1851','fabricante' => 'Intel', 'descripcion' => 'Socket Intel LGA1851 (Core Ultra 200)'],
            ['nombre' => 'LGA2066','fabricante' => 'Intel', 'descripcion' => 'Socket Intel LGA2066 (X-series)'],
        ];
        foreach ($sockets as $socket) {
            Socket::create([...$socket, 'activo' => true]);
        }

        // Tipos de memoria
        $tiposMemoria = [
            ['nombre' => 'DDR4',    'descripcion' => 'Double Data Rate 4'],
            ['nombre' => 'DDR5',    'descripcion' => 'Double Data Rate 5'],
            ['nombre' => 'LPDDR5',  'descripcion' => 'Low Power DDR5'],
            ['nombre' => 'LPDDR5X', 'descripcion' => 'Low Power DDR5X'],
        ];
        foreach ($tiposMemoria as $tipo) {
            TipoMemoria::create([...$tipo, 'activo' => true]);
        }

        // Tipos de VRAM
        $tiposVRAM = [
            ['nombre' => 'GDDR6',  'descripcion' => 'Graphics DDR6'],
            ['nombre' => 'GDDR6X', 'descripcion' => 'Graphics DDR6X'],
            ['nombre' => 'GDDR7',  'descripcion' => 'Graphics DDR7'],
            ['nombre' => 'HBM2e',  'descripcion' => 'High Bandwidth Memory 2e'],
            ['nombre' => 'HBM3',   'descripcion' => 'High Bandwidth Memory 3'],
        ];
        foreach ($tiposVRAM as $tipo) {
            TipoVRAM::create([...$tipo, 'activo' => true]);
        }

        // Arquitecturas
        $arquitecturas = [
            ['nombre' => 'Zen 3',        'fabricante' => 'AMD',    'descripcion' => 'AMD Zen 3 (Ryzen 5000)'],
            ['nombre' => 'Zen 4',        'fabricante' => 'AMD',    'descripcion' => 'AMD Zen 4 (Ryzen 7000)'],
            ['nombre' => 'Zen 5',        'fabricante' => 'AMD',    'descripcion' => 'AMD Zen 5 (Ryzen 9000)'],
            ['nombre' => 'Raptor Lake',  'fabricante' => 'Intel',  'descripcion' => 'Intel 13th Gen'],
            ['nombre' => 'Raptor Lake R','fabricante' => 'Intel',  'descripcion' => 'Intel 14th Gen Refresh'],
            ['nombre' => 'Arrow Lake',   'fabricante' => 'Intel',  'descripcion' => 'Intel Core Ultra 200'],
            ['nombre' => 'Ada Lovelace', 'fabricante' => 'NVIDIA', 'descripcion' => 'NVIDIA RTX 4000 series'],
            ['nombre' => 'Blackwell',    'fabricante' => 'NVIDIA', 'descripcion' => 'NVIDIA RTX 5000 series'],
            ['nombre' => 'RDNA 3',       'fabricante' => 'AMD',    'descripcion' => 'AMD RX 7000 series'],
            ['nombre' => 'RDNA 4',       'fabricante' => 'AMD',    'descripcion' => 'AMD RX 9000 series'],
        ];
        foreach ($arquitecturas as $arq) {
            Arquitectura::create([...$arq, 'activo' => true]);
        }

        // Chipsets (necesitan socket_id)
        $chipsets = [
            // AMD AM5
            ['nombre' => 'X670E', 'fabricante' => 'AMD', 'socket' => 'AM5', 'descripcion' => 'AMD X670E - Gama alta AM5'],
            ['nombre' => 'X670',  'fabricante' => 'AMD', 'socket' => 'AM5', 'descripcion' => 'AMD X670 - Gama alta AM5'],
            ['nombre' => 'B650E', 'fabricante' => 'AMD', 'socket' => 'AM5', 'descripcion' => 'AMD B650E - Gama media AM5'],
            ['nombre' => 'B650',  'fabricante' => 'AMD', 'socket' => 'AM5', 'descripcion' => 'AMD B650 - Gama media AM5'],
            ['nombre' => 'A620',  'fabricante' => 'AMD', 'socket' => 'AM5', 'descripcion' => 'AMD A620 - Gama entrada AM5'],
            // AMD AM4
            ['nombre' => 'X570',  'fabricante' => 'AMD', 'socket' => 'AM4', 'descripcion' => 'AMD X570 - Gama alta AM4'],
            ['nombre' => 'B550',  'fabricante' => 'AMD', 'socket' => 'AM4', 'descripcion' => 'AMD B550 - Gama media AM4'],
            // Intel LGA1700
            ['nombre' => 'Z790',  'fabricante' => 'Intel', 'socket' => 'LGA1700', 'descripcion' => 'Intel Z790 - Gama alta LGA1700'],
            ['nombre' => 'B760',  'fabricante' => 'Intel', 'socket' => 'LGA1700', 'descripcion' => 'Intel B760 - Gama media LGA1700'],
            ['nombre' => 'H770',  'fabricante' => 'Intel', 'socket' => 'LGA1700', 'descripcion' => 'Intel H770 - Gama media LGA1700'],
            // Intel LGA1851
            ['nombre' => 'Z890',  'fabricante' => 'Intel', 'socket' => 'LGA1851', 'descripcion' => 'Intel Z890 - Gama alta LGA1851'],
            ['nombre' => 'B860',  'fabricante' => 'Intel', 'socket' => 'LGA1851', 'descripcion' => 'Intel B860 - Gama media LGA1851'],
        ];
        foreach ($chipsets as $chipset) {
            $socketId = Socket::where('nombre', $chipset['socket'])->first()->id;
            Chipset::create([
                'nombre'      => $chipset['nombre'],
                'fabricante'  => $chipset['fabricante'],
                'socket_id'   => $socketId,
                'descripcion' => $chipset['descripcion'],
                'activo'      => true,
            ]);
        }

        // Factores de forma de placa base
        $factoresForma = [
            ['nombre' => 'ATX',    'descripcion' => 'ATX estándar',      'ancho_mm' => 305, 'largo_mm' => 244],
            ['nombre' => 'mATX',   'descripcion' => 'Micro ATX',         'ancho_mm' => 244, 'largo_mm' => 244],
            ['nombre' => 'ITX',    'descripcion' => 'Mini ITX',          'ancho_mm' => 170, 'largo_mm' => 170],
            ['nombre' => 'E-ATX',  'descripcion' => 'Extended ATX',      'ancho_mm' => 305, 'largo_mm' => 330],
            ['nombre' => 'DTX',    'descripcion' => 'DTX',               'ancho_mm' => 244, 'largo_mm' => 203],
        ];
        foreach ($factoresForma as $ff) {
            FactorForma::create([...$ff, 'activo' => true]);
        }

        // Tipos de gabinete
        $tiposGabinete = [
            ['nombre' => 'Full Tower',  'descripcion' => 'Gabinete Full Tower, máximo espacio'],
            ['nombre' => 'Mid Tower',   'descripcion' => 'Gabinete Mid Tower, tamaño estándar'],
            ['nombre' => 'Mini Tower',  'descripcion' => 'Gabinete Mini Tower, compacto'],
            ['nombre' => 'Mini-ITX',    'descripcion' => 'Gabinete Mini-ITX, muy compacto'],
            ['nombre' => 'SFF',         'descripcion' => 'Small Form Factor, ultra compacto'],
        ];
        foreach ($tiposGabinete as $tipo) {
            TipoGabinete::create([...$tipo, 'activo' => true]);
        }

        // Estructuras de gabinete
        $estructuras = [
            ['nombre' => 'Convencional',        'descripcion' => 'Una sola cámara estándar',                    'tiene_camara_secundaria' => false, 'particion_ajustable' => false],
            ['nombre' => 'Sandwich',             'descripcion' => 'Dos cámaras fijas separadas',                 'tiene_camara_secundaria' => true,  'particion_ajustable' => false],
            ['nombre' => 'Sandwich variable',    'descripcion' => 'Dos cámaras con partición ajustable (Terra)', 'tiene_camara_secundaria' => true,  'particion_ajustable' => true],
        ];
        foreach ($estructuras as $estructura) {
            EstructuraGabinete::create([...$estructura, 'activo' => true]);
        }

        // Interfaces de almacenamiento
        $interfaces = [
            ['nombre' => 'NVMe PCIe 5.0', 'descripcion' => 'NVMe sobre PCIe 5.0'],
            ['nombre' => 'NVMe PCIe 4.0', 'descripcion' => 'NVMe sobre PCIe 4.0'],
            ['nombre' => 'NVMe PCIe 3.0', 'descripcion' => 'NVMe sobre PCIe 3.0'],
            ['nombre' => 'SATA III',       'descripcion' => 'SATA III 6Gb/s'],
            ['nombre' => 'SAS',            'descripcion' => 'Serial Attached SCSI'],
        ];
        foreach ($interfaces as $interfaz) {
            InterfazAlmacenamiento::create([...$interfaz, 'activo' => true]);
        }

        // Factores de forma de almacenamiento
        $ffAlmacenamiento = [
            ['nombre' => 'M.2 2280', 'descripcion' => 'M.2 22x80mm (estándar)'],
            ['nombre' => 'M.2 2242', 'descripcion' => 'M.2 22x42mm (compacto)'],
            ['nombre' => 'M.2 22110','descripcion' => 'M.2 22x110mm (largo)'],
            ['nombre' => '2.5"',     'descripcion' => '2.5 pulgadas (laptop/SSD)'],
            ['nombre' => '3.5"',     'descripcion' => '3.5 pulgadas (HDD desktop)'],
        ];
        foreach ($ffAlmacenamiento as $ff) {
            FactorFormaAlmacenamiento::create([...$ff, 'activo' => true]);
        }

        // Certificaciones PSU
        $certificaciones = [
            ['nombre' => '80+ White',    'descripcion' => '80% eficiencia mínima',          'eficiencia_minima' => 80.00],
            ['nombre' => '80+ Bronze',   'descripcion' => '82% eficiencia mínima',          'eficiencia_minima' => 82.00],
            ['nombre' => '80+ Silver',   'descripcion' => '85% eficiencia mínima',          'eficiencia_minima' => 85.00],
            ['nombre' => '80+ Gold',     'descripcion' => '87% eficiencia mínima',          'eficiencia_minima' => 87.00],
            ['nombre' => '80+ Platinum', 'descripcion' => '90% eficiencia mínima',          'eficiencia_minima' => 90.00],
            ['nombre' => '80+ Titanium', 'descripcion' => '92% eficiencia mínima',          'eficiencia_minima' => 92.00],
        ];
        foreach ($certificaciones as $cert) {
            CertificacionPSU::create([...$cert, 'activo' => true]);
        }

        // Tipos de PSU
        $tiposPSU = [
            ['nombre' => 'ATX',    'descripcion' => 'ATX estándar',    'largo_max_mm' => 160],
            ['nombre' => 'SFX',    'descripcion' => 'Small Form Factor','largo_max_mm' => 100],
            ['nombre' => 'SFX-L',  'descripcion' => 'SFX Large',       'largo_max_mm' => 130],
            ['nombre' => 'TFX',    'descripcion' => 'Thin Form Factor', 'largo_max_mm' => 175],
            ['nombre' => 'FlexATX','descripcion' => 'Flex ATX',        'largo_max_mm' => 150],
        ];
        foreach ($tiposPSU as $tipo) {
            TipoPSU::create([...$tipo, 'activo' => true]);
        }

        // Tipos de NAND
        $tiposNAND = [
            ['nombre' => 'SLC', 'descripcion' => 'Single Level Cell - máxima durabilidad'],
            ['nombre' => 'MLC', 'descripcion' => 'Multi Level Cell - alta durabilidad'],
            ['nombre' => 'TLC', 'descripcion' => 'Triple Level Cell - equilibrio rendimiento/precio'],
            ['nombre' => 'QLC', 'descripcion' => 'Quad Level Cell - máxima densidad'],
            ['nombre' => 'PLC', 'descripcion' => 'Penta Level Cell - ultra alta densidad'],
        ];
        foreach ($tiposNAND as $tipo) {
            TipoNAND::create([...$tipo, 'activo' => true]);
        }

        // Versiones PCIe
        $versionesPCIe = [
            ['nombre' => 'PCIe 3.0', 'descripcion' => 'PCIe 3.0 - 8 GT/s',  'ancho_banda_gbs' => 8.00],
            ['nombre' => 'PCIe 4.0', 'descripcion' => 'PCIe 4.0 - 16 GT/s', 'ancho_banda_gbs' => 16.00],
            ['nombre' => 'PCIe 5.0', 'descripcion' => 'PCIe 5.0 - 32 GT/s', 'ancho_banda_gbs' => 32.00],
        ];
        foreach ($versionesPCIe as $version) {
            VersionPCIe::create([...$version, 'activo' => true]);
        }

        // Tipos de refrigeración
        $tiposRefrigeracion = [
            ['nombre' => 'Aire',         'descripcion' => 'Refrigeración por aire con disipador'],
            ['nombre' => 'AIO',          'descripcion' => 'All In One - refrigeración líquida cerrada'],
            ['nombre' => 'Custom Loop',  'descripcion' => 'Loop personalizado de refrigeración líquida'],
        ];
        foreach ($tiposRefrigeracion as $tipo) {
            TipoRefrigeracion::create([...$tipo, 'activo' => true]);
        }

        // Tipos de ventilador
        $tiposVentilador = [
            ['nombre' => 'Normal',      'descripcion' => 'Ventilador estándar 25mm de grosor', 'grosor_mm' => 25],
            ['nombre' => 'Low Profile', 'descripcion' => 'Ventilador de perfil bajo 15mm',     'grosor_mm' => 15],
            ['nombre' => 'Slim',        'descripcion' => 'Ventilador slim 10mm de grosor',     'grosor_mm' => 10],
        ];
        foreach ($tiposVentilador as $tipo) {
            TipoVentilador::create([...$tipo, 'activo' => true]);
        }
    }
}
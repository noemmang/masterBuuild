<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Auxiliares\Arquitectura;
use App\Models\Auxiliares\CertificacionPSU;
use App\Models\Auxiliares\Chipset;
use App\Models\Auxiliares\EstructuraGabinete;
use App\Models\Auxiliares\FactorForma;
use App\Models\Auxiliares\FactorFormaAlmacenamiento;
use App\Models\Auxiliares\InterfazAlmacenamiento;
use App\Models\Auxiliares\Marca;
use App\Models\Auxiliares\Socket;
use App\Models\Auxiliares\TipoGabinete;
use App\Models\Auxiliares\TipoMemoria;
use App\Models\Auxiliares\TipoNAND;
use App\Models\Auxiliares\TipoPSU;
use App\Models\Auxiliares\TipoRefrigeracion;
use App\Models\Auxiliares\TipoVentilador;
use App\Models\Auxiliares\TipoVRAM;
use App\Models\Auxiliares\VersionPCIe;

class AuxiliaresSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSockets();
        $this->seedArquitecturas();
        $this->seedTiposMemoria();
        $this->seedChipsets();
        $this->seedFactoresForma();
        $this->seedFactoresFormaAlmacenamiento();
        $this->seedInterfacesAlmacenamiento();
        $this->seedTiposNAND();
        $this->seedVersionesPCIe();
        $this->seedTiposVRAM();
        $this->seedCertificacionesPSU();
        $this->seedTiposPSU();
        $this->seedTiposRefrigeracion();
        $this->seedTiposVentilador();
        $this->seedTiposGabinete();
        $this->seedEstructurasGabinete();
    }

    // ── Sockets ───────────────────────────────────────────────────────────────

    protected function seedSockets(): void
    {
        $sockets = [
            // AMD
            ['nombre' => 'AM4',    'fabricante' => 'AMD',   'activo' => true,  'descripcion' => 'Socket AMD AM4. Compatible con Ryzen 1000–5000 (Zen–Zen 3). DDR4. 1331 pines.'],
            ['nombre' => 'AM5',    'fabricante' => 'AMD',   'activo' => true,  'descripcion' => 'Socket AMD AM5. Compatible con Ryzen 7000–9000 (Zen 4–Zen 5). DDR5. LGA1718.'],
            // Intel
            ['nombre' => 'LGA1700','fabricante' => 'Intel', 'activo' => true,  'descripcion' => 'Socket Intel LGA1700. Compatible con 12ª–14ª gen (Alder/Raptor Lake). DDR4/DDR5.'],
            ['nombre' => 'LGA1851','fabricante' => 'Intel', 'activo' => true,  'descripcion' => 'Socket Intel LGA1851. Compatible con Core Ultra 200S (Arrow Lake). DDR5.'],
            ['nombre' => 'LGA1200','fabricante' => 'Intel', 'activo' => false, 'descripcion' => 'Socket Intel LGA1200. Compatible con 10ª–11ª gen (Comet/Rocket Lake). DDR4.'],
        ];

        foreach ($sockets as $data) {
            Socket::create($data);
        }
    }

    // ── Arquitecturas ─────────────────────────────────────────────────────────

    protected function seedArquitecturas(): void
    {
        $arquitecturas = [
            // AMD CPU
            ['nombre' => 'Zen 3',          'fabricante' => 'AMD',   'activo' => true,  'descripcion' => 'Arquitectura AMD Zen 3 (7 nm TSMC). IPC +19% sobre Zen 2. Ryzen 5000 / EPYC Milan.'],
            ['nombre' => 'Zen 4',          'fabricante' => 'AMD',   'activo' => true,  'descripcion' => 'Arquitectura AMD Zen 4 (5 nm TSMC). Soporte DDR5 y PCIe 5.0. Ryzen 7000.'],
            ['nombre' => 'Zen 5',          'fabricante' => 'AMD',   'activo' => true,  'descripcion' => 'Arquitectura AMD Zen 5 (4 nm TSMC). IPC mejorado ~16%. Ryzen 9000.'],
            // Intel CPU
            ['nombre' => 'Alder Lake',     'fabricante' => 'Intel', 'activo' => true,  'descripcion' => 'Intel 12ª gen (Intel 7 / 10 nm). Primera arquitectura híbrida P+E cores.'],
            ['nombre' => 'Raptor Lake',    'fabricante' => 'Intel', 'activo' => true,  'descripcion' => 'Intel 13ª gen (Intel 7). Más núcleos E y frecuencias más altas que Alder Lake.'],
            ['nombre' => 'Raptor Lake Refresh', 'fabricante' => 'Intel', 'activo' => true, 'descripcion' => 'Intel 14ª gen. Refresh de Raptor Lake con frecuencias incrementadas.'],
            ['nombre' => 'Arrow Lake',     'fabricante' => 'Intel', 'activo' => true,  'descripcion' => 'Intel Core Ultra 200S (3 nm TSMC + Intel 20A). Nuevo diseño modular tile.'],
            // AMD GPU
            ['nombre' => 'RDNA 2',         'fabricante' => 'AMD',   'activo' => true,  'descripcion' => 'Arquitectura GPU AMD RDNA 2 (7 nm). RX 6000 series. PCIe 4.0 e Infinity Cache.'],
            ['nombre' => 'RDNA 3',         'fabricante' => 'AMD',   'activo' => true,  'descripcion' => 'Arquitectura GPU AMD RDNA 3 (5 nm chiplet). RX 7000 series. PCIe 4.0.'],
            ['nombre' => 'RDNA 4',         'fabricante' => 'AMD',   'activo' => true,  'descripcion' => 'Arquitectura GPU AMD RDNA 4 (4 nm). RX 9000 series. PCIe 5.0.'],
            // NVIDIA GPU
            ['nombre' => 'Ampere',         'fabricante' => 'NVIDIA','activo' => true,  'descripcion' => 'Arquitectura GPU NVIDIA Ampere (8 nm Samsung). RTX 3000 series.'],
            ['nombre' => 'Ada Lovelace',   'fabricante' => 'NVIDIA','activo' => true,  'descripcion' => 'Arquitectura GPU NVIDIA Ada Lovelace (4 nm TSMC). RTX 4000 series.'],
            ['nombre' => 'Blackwell',      'fabricante' => 'NVIDIA','activo' => true,  'descripcion' => 'Arquitectura GPU NVIDIA Blackwell (4 nm TSMC). RTX 5000 series.'],
            // INTEL GPU
            ['nombre' => 'Xe-HPG',  'fabricante' => 'Intel', 'activo' => true, 'descripcion' => 'Arquitectura GPU Intel Arc A-series (Alchemist). 4 nm Samsung.'],
            ['nombre' => 'Xe2-HPG', 'fabricante' => 'Intel', 'activo' => true, 'descripcion' => 'Arquitectura GPU Intel Arc B-series (Battlemage). TSMC N5.'],
        ];

        foreach ($arquitecturas as $data) {
            Arquitectura::create($data);
        }
    }

    // ── Tipos de memoria ──────────────────────────────────────────────────────

    protected function seedTiposMemoria(): void
    {
        $tipos = [
            ['nombre' => 'DDR4', 'activo' => true,  'descripcion' => 'Double Data Rate 4. Frecuencias estándar 2133–3200 MHz (XMP hasta 5000+). Voltaje 1.2 V.'],
            ['nombre' => 'DDR5', 'activo' => true,  'descripcion' => 'Double Data Rate 5. Frecuencias estándar 4800–6400 MHz (XMP hasta 8000+). Voltaje 1.1 V. ECC on-die.'],
        ];

        foreach ($tipos as $data) {
            TipoMemoria::create($data);
        }
    }

    // ── Chipsets ──────────────────────────────────────────────────────────────

    protected function seedChipsets(): void
    {
        // Necesitamos los IDs de socket
        $am4    = Socket::where('nombre', 'AM4')->first()->id;
        $am5    = Socket::where('nombre', 'AM5')->first()->id;
        $lga1700= Socket::where('nombre', 'LGA1700')->first()->id;
        $lga1851= Socket::where('nombre', 'LGA1851')->first()->id;

        $chipsets = [
            // AMD AM4
            ['nombre' => 'B550',  'fabricante' => 'AMD',   'socket_id' => $am4,     'activo' => true,  'descripcion' => 'Chipset AMD B550 (AM4). PCIe 4.0 en CPU lanes. OC de memoria. Gama media.'],
            ['nombre' => 'X570',  'fabricante' => 'AMD',   'socket_id' => $am4,     'activo' => true,  'descripcion' => 'Chipset AMD X570 (AM4). PCIe 4.0 completo. Chipset activo con ventilador.'],
            ['nombre' => 'A520',  'fabricante' => 'AMD',   'socket_id' => $am4,     'activo' => true,  'descripcion' => 'Chipset AMD A520 (AM4). Gama de entrada. Sin OC de CPU ni PCIe 4.0.'],
            // AMD AM5
            ['nombre' => 'B650',  'fabricante' => 'AMD',   'socket_id' => $am5,     'activo' => true,  'descripcion' => 'Chipset AMD B650 (AM5). PCIe 5.0 en CPU. DDR5. Gama media con OC.'],
            ['nombre' => 'B650E', 'fabricante' => 'AMD',   'socket_id' => $am5,     'activo' => true,  'descripcion' => 'Chipset AMD B650E (AM5). PCIe 5.0 en CPU y M.2. Superset de B650.'],
            ['nombre' => 'X670',  'fabricante' => 'AMD',   'socket_id' => $am5,     'activo' => true,  'descripcion' => 'Chipset AMD X670 (AM5). Doble chipset. PCIe 5.0 extendido. Gama alta.'],
            ['nombre' => 'X670E', 'fabricante' => 'AMD',   'socket_id' => $am5,     'activo' => true,  'descripcion' => 'Chipset AMD X670E (AM5). PCIe 5.0 en slot GPU y M.2. Flagship AM5.'],
            ['nombre' => 'X870',  'fabricante' => 'AMD',   'socket_id' => $am5,     'activo' => true,  'descripcion' => 'Chipset AMD X870 (AM5). Sucesor de X670. USB4 y WiFi 7 obligatorios.'],
            ['nombre' => 'X870E', 'fabricante' => 'AMD',   'socket_id' => $am5,     'activo' => true,  'descripcion' => 'Chipset AMD X870E (AM5). Flagship Zen 5. PCIe 5.0 completo, USB4 40 Gbps.'],
            // Intel LGA1700
            ['nombre' => 'B660',  'fabricante' => 'Intel', 'socket_id' => $lga1700, 'activo' => true,  'descripcion' => 'Chipset Intel B660 (LGA1700, 12ª gen). OC de memoria. Sin OC de CPU. Gama media.'],
            ['nombre' => 'H670',  'fabricante' => 'Intel', 'socket_id' => $lga1700, 'activo' => true,  'descripcion' => 'Chipset Intel H670 (LGA1700, 12ª gen). Más conectividad que B660. Sin OC CPU.'],
            ['nombre' => 'Z690',  'fabricante' => 'Intel', 'socket_id' => $lga1700, 'activo' => true,  'descripcion' => 'Chipset Intel Z690 (LGA1700, 12ª gen). OC completo. PCIe 5.0. DDR4/DDR5.'],
            ['nombre' => 'Z790',  'fabricante' => 'Intel', 'socket_id' => $lga1700, 'activo' => true,  'descripcion' => 'Chipset Intel Z790 (LGA1700, 13ª/14ª gen). Más M.2 y USB 3.2 que Z690.'],
            ['nombre' => 'B760',  'fabricante' => 'Intel', 'socket_id' => $lga1700, 'activo' => true,  'descripcion' => 'Chipset Intel B760 (LGA1700, 13ª/14ª gen). OC de memoria. Gama media-baja.'],
            // Intel LGA1851
            ['nombre' => 'Z890',  'fabricante' => 'Intel', 'socket_id' => $lga1851, 'activo' => true,  'descripcion' => 'Chipset Intel Z890 (LGA1851, Core Ultra 200S). PCIe 5.0. DDR5. OC completo.'],
            ['nombre' => 'B860',  'fabricante' => 'Intel', 'socket_id' => $lga1851, 'activo' => true,  'descripcion' => 'Chipset Intel B860 (LGA1851, Core Ultra 200S). Gama media. OC de memoria.'],
        ];

        foreach ($chipsets as $data) {
            Chipset::create($data);
        }
    }

    // ── Factores de forma (placa base) ────────────────────────────────────────

    protected function seedFactoresForma(): void
    {
        $factores = [
            ['nombre' => 'ATX',        'ancho_mm' => 244, 'largo_mm' => 305, 'activo' => true,  'descripcion' => 'Factor de forma estándar. 7 slots de expansión. El más común en PCs de escritorio.'],
            ['nombre' => 'Micro-ATX',  'ancho_mm' => 244, 'largo_mm' => 244, 'activo' => true,  'descripcion' => 'Versión reducida del ATX. Hasta 4 slots de expansión. Buena relación tamaño/precio.'],
            ['nombre' => 'Mini-ITX',   'ancho_mm' => 170, 'largo_mm' => 170, 'activo' => true,  'descripcion' => 'Factor de forma compacto. 1 slot de expansión. Ideal para builds SFF.'],
            ['nombre' => 'E-ATX',      'ancho_mm' => 305, 'largo_mm' => 330, 'activo' => true,  'descripcion' => 'Extended ATX. Más espacio para VRMs y slots. Usado en placas HEDT y entusiastas.'],
        ];

        foreach ($factores as $data) {
            FactorForma::create($data);
        }
    }

    // ── Factores de forma de almacenamiento ───────────────────────────────────

    protected function seedFactoresFormaAlmacenamiento(): void
    {
        $factores = [
            ['nombre' => '2.5"',   'activo' => true,  'descripcion' => 'Factor de forma 2.5 pulgadas. Usado en SSDs SATA y HDDs de portátil.'],
            ['nombre' => '3.5"',   'activo' => true,  'descripcion' => 'Factor de forma 3.5 pulgadas. Estándar para HDDs de escritorio.'],
            ['nombre' => 'M.2 2280','activo' => true,  'descripcion' => 'Módulo M.2 de 22 × 80 mm. El tamaño más común para SSDs NVMe y SATA M.2.'],
            ['nombre' => 'M.2 2242','activo' => true,  'descripcion' => 'Módulo M.2 de 22 × 42 mm. Usado en portátiles y dispositivos compactos.'],
            ['nombre' => 'M.2 22110','activo' => true, 'descripcion' => 'Módulo M.2 de 22 × 110 mm. Formato extendido para SSDs de alta capacidad.'],
            ['nombre' => 'Add-in Card', 'activo' => true, 'descripcion' => 'SSD en formato tarjeta PCIe (AIC). Ocupa un slot de expansión estándar.'],
        ];

        foreach ($factores as $data) {
            FactorFormaAlmacenamiento::create($data);
        }
    }

    // ── Interfaces de almacenamiento ──────────────────────────────────────────

    protected function seedInterfacesAlmacenamiento(): void
    {
        $interfaces = [
            ['nombre' => 'SATA III',    'activo' => true,  'descripcion' => 'Serial ATA III. Ancho de banda máximo 6 Gbps (~550 MB/s). Estándar para SSDs 2.5" y HDDs.'],
            ['nombre' => 'NVMe PCIe 3.0','activo' => true, 'descripcion' => 'NVMe sobre PCIe 3.0 x4. Hasta ~3500 MB/s lectura. Interfaz M.2 o AIC.'],
            ['nombre' => 'NVMe PCIe 4.0','activo' => true, 'descripcion' => 'NVMe sobre PCIe 4.0 x4. Hasta ~7000 MB/s lectura. Doble ancho de banda vs PCIe 3.0.'],
            ['nombre' => 'NVMe PCIe 5.0','activo' => true, 'descripcion' => 'NVMe sobre PCIe 5.0 x4. Hasta ~14000 MB/s lectura. Requiere plataforma AM5 o LGA1851.'],
        ];

        foreach ($interfaces as $data) {
            InterfazAlmacenamiento::create($data);
        }
    }

    // ── Tipos de NAND ─────────────────────────────────────────────────────────

    protected function seedTiposNAND(): void
    {
        $tipos = [
            ['nombre' => 'TLC',  'activo' => true,  'descripcion' => 'Triple-Level Cell. 3 bits por celda. Balance entre densidad, velocidad y durabilidad. El más común en SSDs consumer.'],
            ['nombre' => 'QLC',  'activo' => true,  'descripcion' => 'Quad-Level Cell. 4 bits por celda. Mayor densidad y menor coste. Menor velocidad de escritura y durabilidad vs TLC.'],
            ['nombre' => 'MLC',  'activo' => true,  'descripcion' => 'Multi-Level Cell. 2 bits por celda. Alta velocidad y durabilidad. Usado en SSDs profesionales.'],
            ['nombre' => 'SLC',  'activo' => false, 'descripcion' => 'Single-Level Cell. 1 bit por celda. Máxima durabilidad y velocidad. Exclusivo de uso empresarial.'],
            ['nombre' => 'N/A',  'activo' => true,  'descripcion' => 'No aplica. Usado para HDDs u otros dispositivos de almacenamiento sin NAND Flash.'],
        ];

        foreach ($tipos as $data) {
            TipoNAND::create($data);
        }
    }

    // ── Versiones PCIe ────────────────────────────────────────────────────────

    protected function seedVersionesPCIe(): void
    {
        $versiones = [
            ['nombre' => 'PCIe 3.0', 'ancho_banda_gbs' => 15.75, 'activo' => true,  'descripcion' => 'PCIe 3.0 x16: 15.75 GB/s bidireccional. Presente en plataformas pre-2021.'],
            ['nombre' => 'PCIe 4.0', 'ancho_banda_gbs' => 31.51, 'activo' => true,  'descripcion' => 'PCIe 4.0 x16: 31.51 GB/s bidireccional. Estándar actual en Ryzen 5000+ e Intel 12ª+.'],
            ['nombre' => 'PCIe 5.0', 'ancho_banda_gbs' => 63.02, 'activo' => true,  'descripcion' => 'PCIe 5.0 x16: 63.02 GB/s bidireccional. Disponible en AM5 y LGA1851.'],
        ];

        foreach ($versiones as $data) {
            VersionPCIe::create($data);
        }
    }

    // ── Tipos de VRAM ─────────────────────────────────────────────────────────

    protected function seedTiposVRAM(): void
    {
        $tipos = [
            ['nombre' => 'GDDR6',  'activo' => true,  'descripcion' => 'GDDR6. Hasta ~16 Gbps por pin. Estándar en GPUs de gama media-alta desde 2019.'],
            ['nombre' => 'GDDR6X', 'activo' => true,  'descripcion' => 'GDDR6X. Hasta ~21 Gbps por pin con modulación PAM4. Exclusivo de NVIDIA GPUs de alta gama.'],
            ['nombre' => 'GDDR7',  'activo' => true,  'descripcion' => 'GDDR7. Hasta ~32 Gbps por pin. Presente en RTX 5000 y RX 9000 series.'],
        ];

        foreach ($tipos as $data) {
            TipoVRAM::create($data);
        }
    }

    // ── Certificaciones PSU ───────────────────────────────────────────────────

    protected function seedCertificacionesPSU(): void
    {
        $certs = [
            ['nombre' => '80 Plus',          'eficiencia_minima' => 80.00, 'activo' => true,  'descripcion' => '80 Plus estándar. Eficiencia mínima del 80% a 20%, 50% y 100% de carga.'],
            ['nombre' => '80 Plus Bronze',   'eficiencia_minima' => 82.00, 'activo' => true,  'descripcion' => '80 Plus Bronze. 82% a 20%/100% carga, 85% a 50% carga.'],
            ['nombre' => '80 Plus Silver',   'eficiencia_minima' => 85.00, 'activo' => true,  'descripcion' => '80 Plus Silver. 85% a 20%/100% carga, 88% a 50% carga.'],
            ['nombre' => '80 Plus Gold',     'eficiencia_minima' => 87.00, 'activo' => true,  'descripcion' => '80 Plus Gold. 87% a 20%/100% carga, 90% a 50% carga. El más común en gama media-alta.'],
            ['nombre' => '80 Plus Platinum', 'eficiencia_minima' => 90.00, 'activo' => true,  'descripcion' => '80 Plus Platinum. 90% a 20%/100% carga, 92% a 50% carga.'],
            ['nombre' => '80 Plus Titanium', 'eficiencia_minima' => 92.00, 'activo' => true,  'descripcion' => '80 Plus Titanium. 90% a 10% carga, 92% a 20%, 94% a 50%, 90% a 100%. Máxima eficiencia.'],
        ];

        foreach ($certs as $data) {
            CertificacionPSU::create($data);
        }
    }

    // ── Tipos de PSU ──────────────────────────────────────────────────────────

    protected function seedTiposPSU(): void
    {
        $tipos = [
            ['nombre' => 'ATX',       'largo_max_mm' => 150, 'activo' => true,  'descripcion' => 'PSU ATX estándar. Largo típico 140–150 mm. Compatible con la mayoría de gabinetes ATX, mATX e ITX.'],
            ['nombre' => 'ATX 3.0',   'largo_max_mm' => 150, 'activo' => true,  'descripcion' => 'PSU ATX 3.0. Conector PCIe 5.0 (12VHPWR) nativo. Mejor regulación para picos de GPU.'],
            ['nombre' => 'SFX',       'largo_max_mm' => 100, 'activo' => true,  'descripcion' => 'PSU SFX. 125 × 63.5 × 100 mm. Diseñado para builds SFF e ITX compactos.'],
            ['nombre' => 'SFX-L',     'largo_max_mm' => 130, 'activo' => true,  'descripcion' => 'PSU SFX-L. 125 × 63.5 × 130 mm. Más largo que SFX; permite ventilador de 120 mm.'],
        ];

        foreach ($tipos as $data) {
            TipoPSU::create($data);
        }
    }

    // ── Tipos de refrigeración ────────────────────────────────────────────────

    protected function seedTiposRefrigeracion(): void
    {
        $tipos = [
            ['nombre' => 'Aire',          'activo' => true,  'descripcion' => 'Refrigeración por aire. Disipador metálico con ventilador(es). Sin riesgo de fuga.'],
            ['nombre' => 'Líquida AIO',   'activo' => true,  'descripcion' => 'All-In-One. Circuito cerrado prerrelleno con bomba, tuberías, radiador y ventiladores.'],
            ['nombre' => 'Líquida Custom','activo' => true,  'descripcion' => 'Loop personalizado. Componentes separados: bomba, depósito, radiador, bloques y tubería rígida/blanda.'],
        ];

        foreach ($tipos as $data) {
            TipoRefrigeracion::create($data);
        }
    }

    // ── Tipos de ventilador ───────────────────────────────────────────────────

    protected function seedTiposVentilador(): void
    {
        $tipos = [
            ['nombre' => 'Normal',      'grosor_mm' => 25, 'activo' => true,  'descripcion' => 'Ventilador de grosor estándar 25 mm. El más común en 80 mm, 120 mm y 140 mm.'],
            ['nombre' => 'Low Profile', 'grosor_mm' => 15, 'activo' => true,  'descripcion' => 'Ventilador slim de 15 mm de grosor. Usado en refrigeraciones de bajo perfil y espacios reducidos.'],
            ['nombre' => 'Ultra Slim',  'grosor_mm' => 10, 'activo' => true,  'descripcion' => 'Ventilador ultra delgado de 10 mm. Casos extremos de poco espacio (NAS, SFF muy compactos).'],
        ];

        foreach ($tipos as $data) {
            TipoVentilador::create($data);
        }
    }

    // ── Tipos de gabinete ─────────────────────────────────────────────────────

    protected function seedTiposGabinete(): void
    {
        $tipos = [
            ['nombre' => 'Full Tower',  'activo' => true,  'descripcion' => 'Gabinete de máximo tamaño. Soporta E-ATX. Gran espacio para refrigeración y almacenamiento.'],
            ['nombre' => 'Mid Tower',   'activo' => true,  'descripcion' => 'Gabinete estándar. Soporta ATX/mATX/ITX. El formato más popular en equipos de escritorio.'],
            ['nombre' => 'Mini Tower',  'activo' => true,  'descripcion' => 'Gabinete compacto. Generalmente soporta Micro-ATX e ITX. Menos espacio para componentes.'],
            ['nombre' => 'SFF',         'activo' => true,  'descripcion' => 'Small Form Factor. Solo Mini-ITX. Diseño ultra compacto, ideal para HTPCs o setups minimalistas.'],
        ];

        foreach ($tipos as $data) {
            TipoGabinete::create($data);
        }
    }

    // ── Estructuras de gabinete ───────────────────────────────────────────────

    protected function seedEstructurasGabinete(): void
    {
        $estructuras = [
            ['nombre' => 'Tradicional',        'tiene_camara_secundaria' => false, 'particion_ajustable' => false, 'activo' => true,  'descripcion' => 'Diseño clásico de una sola cámara. Placa base, PSU y almacenamiento en el mismo espacio.'],
            ['nombre' => 'Doble cámara',       'tiene_camara_secundaria' => true,  'particion_ajustable' => false, 'activo' => true,  'descripcion' => 'Separación fija entre la cámara principal (placa base, GPU) y la secundaria (PSU, HDDs).'],
            ['nombre' => 'Doble cámara flex',  'tiene_camara_secundaria' => true,  'particion_ajustable' => true,  'activo' => true,  'descripcion' => 'Cámara secundaria con partición ajustable o extraíble para adaptarse a distintos componentes.'],
            ['nombre' => 'Open Frame',         'tiene_camara_secundaria' => false, 'particion_ajustable' => false, 'activo' => true,  'descripcion' => 'Chasis abierto sin panel lateral. Ideal para overclocking y refrigeración custom visible.'],
        ];

        foreach ($estructuras as $data) {
            EstructuraGabinete::create($data);
        }
    }
}
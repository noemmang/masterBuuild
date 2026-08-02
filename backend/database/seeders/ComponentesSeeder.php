<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Auxiliares\Marca;
use App\Models\Auxiliares\Socket;
use App\Models\Auxiliares\Arquitectura;
use App\Models\Auxiliares\TipoMemoria;
use App\Models\Componentes\Componente;
use App\Models\Componentes\CPU;
use App\Models\Negocio\Tienda;
use Carbon\Carbon;
use App\Models\Auxiliares\TipoGabinete;
use App\Models\Auxiliares\EstructuraGabinete;
use App\Models\Auxiliares\FactorForma;
use App\Models\Auxiliares\TipoPSU;
use App\Models\Componentes\Gabinete;
use App\Models\Auxiliares\CertificacionPSU;
use App\Models\Componentes\PSU;
use App\Models\Componentes\RefrigeracionAire;
use App\Models\Componentes\RefrigeracionLiquida;
use App\Models\Auxiliares\TipoRefrigeracion;

class ComponentesSeeder extends Seeder
{
    // ── Caché de modelos auxiliares ───────────────────────────────────────────
    protected array $marcas    = [];
    protected array $sockets   = [];
    protected array $arqs      = [];
    protected array $tiposRam  = [];
    protected array $tiendas   = [];
    protected array $tiposRefrig = [];

    // ── Entry point ───────────────────────────────────────────────────────────
    public function run(): void
    {
        $this->cargarAuxiliares();
        $this->seedCPUsAMD();
        $this->seedCPUsIntel();
        $this->seedPlacasBase();
        $this->seedRAMs();
        $this->seedGPUs();
        $this->seedAlmacenamientos();
        $this->seedGabinetes();
        $this->seedPSUs();
        $this->seedRefrigeracionesAire();
        $this->seedRefrigeracionesLiquidas();
        $this->seedVentiladores();
    }

    // ── Carga de auxiliares ───────────────────────────────────────────────────
    protected function cargarAuxiliares(): void
    {
        foreach (Marca::all() as $m) { $this->marcas[$m->nombre] = $m->id; }
        foreach (Socket::all() as $s) { $this->sockets[$s->nombre] = $s->id; }
        foreach (Arquitectura::all() as $a) { $this->arqs[$a->nombre] = $a->id; }
        foreach (TipoMemoria::all() as $t) { $this->tiposRam[$t->nombre] = $t->id; }
        foreach (Tienda::all() as $t) { $this->tiendas[$t->nombre] = $t->id; }
        foreach (TipoRefrigeracion::all() as $t) { $this->tiposRefrig[$t->nombre] = $t->id; }
    }

    // ── Helper: crear componente + CPU ─────────────────────────────────────────
    protected function crearCPU(array $comp, array $cpu): void
    {
        $marcaId = $this->marcas[$comp['marca']] ?? null;
        $fabId   = $this->marcas[$comp['fabricante']] ?? $marcaId;
        $componente = Componente::create([ 'nombre' => $comp['nombre'], 'marca_id' => $marcaId, 'fabricante_id' => $fabId, 'categoria' => 'cpu', 'modelo' => $comp['modelo'], 'imagen_url' => $comp['imagen_url'] ?? null, 'descripcion' => $comp['descripcion'] ?? null, 'activo' => true, ]);
        CPU::create([ 'componente_id' => $componente->id, 'socket_id' => $this->sockets[$cpu['socket']] ?? null, 'arquitectura_id' => $this->arqs[$cpu['arquitectura']] ?? null, 'tipo_memoria_id' => $this->tiposRam[$cpu['tipo_memoria']] ?? null, 'nucleos' => $cpu['nucleos'], 'hilos' => $cpu['hilos'], 'frecuencia_base_ghz' => $cpu['frecuencia_base_ghz'], 'frecuencia_boost_ghz' => $cpu['frecuencia_boost_ghz'] ?? null, 'tdp_watts' => $cpu['tdp_watts'], 'tdp_max_watts' => $cpu['tdp_max_watts'] ?? null, 'frecuencia_memoria_max_mhz'=> $cpu['frecuencia_memoria_max_mhz'], 'memoria_max_gb' => $cpu['memoria_max_gb'], 'grafica_integrada' => $cpu['grafica_integrada'], 'nombre_grafica_integrada' => $cpu['nombre_grafica_integrada'] ?? null, 'proceso_nm' => $cpu['proceso_nm'], 'incluye_cooler' => $cpu['incluye_cooler'], 'overclock' => $cpu['overclock'], ]);
    }

    protected function crearGPU(array $comp, array $gpu): void
    {
        $marcaId = $this->marcas[$comp['marca']] ?? null;
        $fabId   = $this->marcas[$comp['fabricante']] ?? $marcaId;
        $componente = Componente::create([
            'nombre'        => $comp['nombre'],
            'marca_id'      => $marcaId,
            'fabricante_id' => $fabId,
            'categoria'     => 'gpu',
            'modelo'        => $comp['modelo'],
            'imagen_url'    => $comp['imagen_url'] ?? null,
            'descripcion'   => $comp['descripcion'] ?? null,
            'activo'        => true,
        ]);
        \App\Models\Componentes\GPU::create([
            'componente_id'           => $componente->id,
            'arquitectura_id'         => $this->arqs[$gpu['arquitectura']] ?? null,
            'tipo_vram_id'            => \App\Models\Auxiliares\TipoVRAM::where('nombre', $gpu['tipo_vram'])->first()?->id,
            'version_pcie_id'         => \App\Models\Auxiliares\VersionPCIe::where('nombre', $gpu['version_pcie'])->first()?->id,
            'vram_gb'                 => $gpu['vram_gb'],
            'bus_bits'                => $gpu['bus_bits'],
            'frecuencia_base_mhz'     => $gpu['frecuencia_base_mhz'],
            'frecuencia_boost_mhz'    => $gpu['frecuencia_boost_mhz'],
            'tdp_watts'               => $gpu['tdp_watts'],
            'slots_pcie'              => $gpu['slots_pcie'],
            'longitud_mm'             => $gpu['longitud_mm'],
            'conectores_alimentacion' => $gpu['conectores_alimentacion'],
            'psu_minima_watts'        => $gpu['psu_minima_watts'],
            'salidas_video'           => $gpu['salidas_video'],
            'ray_tracing'             => $gpu['ray_tracing'],
            'dlss'                    => $gpu['dlss'],
            'fsr'                     => $gpu['fsr'],
        ]);
    }

    protected function crearAlmacenamiento(array $comp, array $alm): void
    {
        $marcaId = $this->marcas[$comp['marca']] ?? null;
        $fabId   = $this->marcas[$comp['fabricante']] ?? $marcaId;
        $componente = Componente::create([
            'nombre'        => $comp['nombre'],
            'marca_id'      => $marcaId,
            'fabricante_id' => $fabId,
            'categoria'     => 'almacenamiento',
            'modelo'        => $comp['modelo'],
            'imagen_url'    => $comp['imagen_url'] ?? null,
            'descripcion'   => $comp['descripcion'] ?? null,
            'activo'        => true,
        ]);
        \App\Models\Componentes\Almacenamiento::create([
            'componente_id'          => $componente->id,
            'interfaz_id'            => \App\Models\Auxiliares\InterfazAlmacenamiento::where('nombre', $alm['interfaz'])->first()?->id,
            'factor_forma_id'        => \App\Models\Auxiliares\FactorFormaAlmacenamiento::where('nombre', $alm['factor_forma'])->first()?->id,
            'tipo_nand_id'           => \App\Models\Auxiliares\TipoNAND::where('nombre', $alm['tipo_nand'])->first()?->id,
            'tipo'                   => $alm['tipo'],
            'capacidad_gb'           => $alm['capacidad_gb'],
            'velocidad_lectura_mbs'  => $alm['velocidad_lectura_mbs'],
            'velocidad_escritura_mbs'=> $alm['velocidad_escritura_mbs'],
            'rpm'                    => $alm['rpm'] ?? null,
            'cache_mb'               => $alm['cache_mb'] ?? null,
            'tbw'                    => $alm['tbw'] ?? null,
            'cifrado'                => $alm['cifrado'] ?? false,
            'dram'                   => $alm['dram'] ?? false,
        ]);
    }

    protected function crearVentilador(array $comp, array $vent): void
    {
        $marcaId = $this->marcas[$comp['marca']] ?? null;
        $componente = Componente::create([
            'nombre'        => $comp['nombre'],
            'marca_id'      => $marcaId,
            'fabricante_id' => $marcaId,
            'categoria'     => 'ventilador',
            'modelo'        => $comp['modelo'],
            'imagen_url'    => $comp['imagen_url'] ?? null,
            'descripcion'   => $comp['descripcion'] ?? null,
            'activo'        => true,
        ]);
        \App\Models\Componentes\Ventilador::create([
            'componente_id'        => $componente->id,
            'tipo_ventilador_id'   => \App\Models\Auxiliares\TipoVentilador::where('nombre', $vent['tipo'])->first()?->id,
            'rpm_min'              => $vent['rpm_min'],
            'rpm_max'              => $vent['rpm_max'],
            'ruido_db_min'         => $vent['ruido_db_min'],
            'ruido_db_max'         => $vent['ruido_db_max'],
            'flujo_aire_cfm'       => $vent['flujo_aire_cfm'],
            'static_pressure_mmh2o'=> $vent['static_pressure_mmh2o'],
            'num_ventiladores'     => $vent['num_ventiladores'],
            'tiene_rgb'            => $vent['tiene_rgb'],
            'pwm'                  => $vent['pwm'],
            'tam_mm'               => $vent['tam_mm'],
        ]);
    }

    protected function seedCPUsAMD(): void
    {
        $this->crearCPU(
            comp: ['nombre' => 'AMD Ryzen 5 5600X', 'marca' => 'AMD', 'fabricante' => 'AMD', 'modelo' => 'Ryzen 5 5600X', 'descripcion' => 'Procesador AMD Ryzen 5 5600X, 6 núcleos / 12 hilos, arquitectura Zen 3, socket AM4. Excelente relación rendimiento/precio para gaming.', 'imagen_url' => 'https://i.pcmag.com/imagery/reviews/046qr9QhBLR8FEd4zwKn68Z-1.jpg'],
            cpu: ['socket' => 'AM4', 'arquitectura' => 'Zen 3', 'tipo_memoria' => 'DDR4', 'nucleos' => 6, 'hilos' => 12, 'frecuencia_base_ghz' => 3.70, 'frecuencia_boost_ghz' => 4.60, 'tdp_watts' => 65, 'tdp_max_watts' => 76, 'frecuencia_memoria_max_mhz' => 3200, 'memoria_max_gb' => 128, 'grafica_integrada' => false, 'nombre_grafica_integrada' => null, 'proceso_nm' => 7, 'incluye_cooler' => true, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'AMD Ryzen 7 5800X', 'marca' => 'AMD', 'fabricante' => 'AMD', 'modelo' => 'Ryzen 7 5800X', 'descripcion' => 'Procesador AMD Ryzen 7 5800X, 8 núcleos / 16 hilos, arquitectura Zen 3, socket AM4. Referencia de alto rendimiento para creadores y gamers.', 'imagen_url' => 'https://www.neobyte.es/120310-large_default/amd-ryzen-7-5800xt-procesador-am4.jpg'],
            cpu: ['socket' => 'AM4', 'arquitectura' => 'Zen 3', 'tipo_memoria' => 'DDR4', 'nucleos' => 8, 'hilos' => 16, 'frecuencia_base_ghz' => 3.80, 'frecuencia_boost_ghz' => 4.70, 'tdp_watts' => 105, 'tdp_max_watts' => 142, 'frecuencia_memoria_max_mhz' => 3200, 'memoria_max_gb' => 128, 'grafica_integrada' => false, 'nombre_grafica_integrada' => null, 'proceso_nm' => 7, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'AMD Ryzen 9 5950X', 'marca' => 'AMD', 'fabricante' => 'AMD', 'modelo' => 'Ryzen 9 5950X', 'descripcion' => 'Procesador AMD Ryzen 9 5950X, 16 núcleos / 32 hilos, arquitectura Zen 3. El buque insignia de la plataforma AM4.', 'imagen_url' => 'https://www.notebookcheck.org/fileadmin/Notebooks/News/_nc3/AMD_Ryzen_9_5950X_Desktop_CPU.jpg'],
            cpu: ['socket' => 'AM4', 'arquitectura' => 'Zen 3', 'tipo_memoria' => 'DDR4', 'nucleos' => 16, 'hilos' => 32, 'frecuencia_base_ghz' => 3.40, 'frecuencia_boost_ghz' => 4.90, 'tdp_watts' => 105, 'tdp_max_watts' => 142, 'frecuencia_memoria_max_mhz' => 3200, 'memoria_max_gb' => 128, 'grafica_integrada' => false, 'nombre_grafica_integrada' => null, 'proceso_nm' => 7, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'AMD Ryzen 5 5600G', 'marca' => 'AMD', 'fabricante' => 'AMD', 'modelo' => 'Ryzen 5 5600G', 'descripcion' => 'Procesador AMD Ryzen 5 5600G, 6 núcleos / 12 hilos con gráfica integrada Radeon. Ideal para sistemas sin GPU dedicada.', 'imagen_url' => 'https://m.media-amazon.com/images/I/51iji7Gel-L.jpg'],
            cpu: ['socket' => 'AM4', 'arquitectura' => 'Zen 3', 'tipo_memoria' => 'DDR4', 'nucleos' => 6, 'hilos' => 12, 'frecuencia_base_ghz' => 3.90, 'frecuencia_boost_ghz' => 4.40, 'tdp_watts' => 65, 'tdp_max_watts' => 88, 'frecuencia_memoria_max_mhz' => 3200, 'memoria_max_gb' => 64, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Radeon Graphics (7 CUs)', 'proceso_nm' => 7, 'incluye_cooler' => true, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'AMD Ryzen 7 5800X3D', 'marca' => 'AMD', 'fabricante' => 'AMD', 'modelo' => 'Ryzen 7 5800X3D', 'descripcion' => 'Procesador AMD Ryzen 7 5800X3D, 8 núcleos / 16 hilos con 3D V-Cache. El mejor procesador gaming de la plataforma AM4, gracias a su caché L3 de 96 MB.', 'imagen_url' => 'https://www.amd.com/content/dam/amd/en/images/products/processors/ryzen/2505503-ryzen-7-5800x3d-og.jpg'],
            cpu: ['socket' => 'AM4', 'arquitectura' => 'Zen 3', 'tipo_memoria' => 'DDR4', 'nucleos' => 8, 'hilos' => 16, 'frecuencia_base_ghz' => 3.40, 'frecuencia_boost_ghz' => 4.50, 'tdp_watts' => 105, 'tdp_max_watts' => 142, 'frecuencia_memoria_max_mhz' => 3200, 'memoria_max_gb' => 128, 'grafica_integrada' => false, 'nombre_grafica_integrada' => null, 'proceso_nm' => 7, 'incluye_cooler' => false, 'overclock' => false]
        );
        $this->crearCPU(
            comp: ['nombre' => 'AMD Ryzen 5 7600X', 'marca' => 'AMD', 'fabricante' => 'AMD', 'modelo' => 'Ryzen 5 7600X', 'descripcion' => 'Procesador AMD Ryzen 5 7600X, 6 núcleos / 12 hilos, arquitectura Zen 4, socket AM5. Primera generación con soporte DDR5.', 'imagen_url' => 'https://www.amd.com/content/dam/amd/en/images/products/processors/ryzen/2505503-ryzen-5-7600x-og.jpg'],
            cpu: ['socket' => 'AM5', 'arquitectura' => 'Zen 4', 'tipo_memoria' => 'DDR5', 'nucleos' => 6, 'hilos' => 12, 'frecuencia_base_ghz' => 4.70, 'frecuencia_boost_ghz' => 5.30, 'tdp_watts' => 105, 'tdp_max_watts' => 142, 'frecuencia_memoria_max_mhz' => 5200, 'memoria_max_gb' => 128, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Radeon Graphics (2 CUs)', 'proceso_nm' => 5, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'AMD Ryzen 5 7600', 'marca' => 'AMD', 'fabricante' => 'AMD', 'modelo' => 'Ryzen 5 7600', 'descripcion' => 'Procesador AMD Ryzen 5 7600, 6 núcleos / 12 hilos, arquitectura Zen 4. Versión no-X con menor TDP y cooler incluido.', 'imagen_url' => 'https://www.ucc.com.bd/image/cache/catalog/processor/amd/ryzen-5-7600/amd-ryzen-5-7600-processor-1-550x550.jpg.webp'],
            cpu: ['socket' => 'AM5', 'arquitectura' => 'Zen 4', 'tipo_memoria' => 'DDR5', 'nucleos' => 6, 'hilos' => 12, 'frecuencia_base_ghz' => 3.80, 'frecuencia_boost_ghz' => 5.10, 'tdp_watts' => 65, 'tdp_max_watts' => 88, 'frecuencia_memoria_max_mhz' => 5200, 'memoria_max_gb' => 128, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Radeon Graphics (2 CUs)', 'proceso_nm' => 5, 'incluye_cooler' => true, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'AMD Ryzen 7 7700X', 'marca' => 'AMD', 'fabricante' => 'AMD', 'modelo' => 'Ryzen 7 7700X', 'descripcion' => 'Procesador AMD Ryzen 7 7700X, 8 núcleos / 16 hilos, arquitectura Zen 4. Alto rendimiento gaming y productividad en AM5.', 'imagen_url' => 'https://www.amd.com/content/dam/amd/en/images/products/processors/ryzen/2505503-ryzen-7-7700x-og.jpg'],
            cpu: ['socket' => 'AM5', 'arquitectura' => 'Zen 4', 'tipo_memoria' => 'DDR5', 'nucleos' => 8, 'hilos' => 16, 'frecuencia_base_ghz' => 4.50, 'frecuencia_boost_ghz' => 5.40, 'tdp_watts' => 105, 'tdp_max_watts' => 142, 'frecuencia_memoria_max_mhz' => 5200, 'memoria_max_gb' => 128, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Radeon Graphics (2 CUs)', 'proceso_nm' => 5, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'AMD Ryzen 7 7700', 'marca' => 'AMD', 'fabricante' => 'AMD', 'modelo' => 'Ryzen 7 7700', 'descripcion' => 'Procesador AMD Ryzen 7 7700, 8 núcleos / 16 hilos, arquitectura Zen 4. Versión non-X con 65W TDP y cooler Wraith Prism incluido.', 'imagen_url' => 'https://www.amd.com/content/dam/amd/en/images/products/processors/ryzen/2505503-ryzen-7-7700-og.jpg'],
            cpu: ['socket' => 'AM5', 'arquitectura' => 'Zen 4', 'tipo_memoria' => 'DDR5', 'nucleos' => 8, 'hilos' => 16, 'frecuencia_base_ghz' => 3.80, 'frecuencia_boost_ghz' => 5.30, 'tdp_watts' => 65, 'tdp_max_watts' => 88, 'frecuencia_memoria_max_mhz' => 5200, 'memoria_max_gb' => 128, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Radeon Graphics (2 CUs)', 'proceso_nm' => 5, 'incluye_cooler' => true, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'AMD Ryzen 9 7900X', 'marca' => 'AMD', 'fabricante' => 'AMD', 'modelo' => 'Ryzen 9 7900X', 'descripcion' => 'Procesador AMD Ryzen 9 7900X, 12 núcleos / 24 hilos, arquitectura Zen 4. Gran alternativa para workstations en la plataforma AM5.', 'imagen_url' => 'https://www.amd.com/content/dam/amd/en/images/products/processors/ryzen/2505503-ryzen-9-7900x-og.jpg'],
            cpu: ['socket' => 'AM5', 'arquitectura' => 'Zen 4', 'tipo_memoria' => 'DDR5', 'nucleos' => 12, 'hilos' => 24, 'frecuencia_base_ghz' => 4.70, 'frecuencia_boost_ghz' => 5.60, 'tdp_watts' => 170, 'tdp_max_watts' => 230, 'frecuencia_memoria_max_mhz' => 5200, 'memoria_max_gb' => 128, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Radeon Graphics (2 CUs)', 'proceso_nm' => 5, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'AMD Ryzen 7 7800X3D', 'marca' => 'AMD', 'fabricante' => 'AMD', 'modelo' => 'Ryzen 7 7800X3D', 'descripcion' => 'Procesador AMD Ryzen 7 7800X3D, 8 núcleos / 16 hilos con 3D V-Cache (96 MB L3). El mejor procesador gaming del mercado en su lanzamiento.', 'imagen_url' => 'https://cdn.videocardz.com/1/2026/05/RYZEN-7800X3D-HERO-1200x627.jpg'],
            cpu: ['socket' => 'AM5', 'arquitectura' => 'Zen 4', 'tipo_memoria' => 'DDR5', 'nucleos' => 8, 'hilos' => 16, 'frecuencia_base_ghz' => 4.20, 'frecuencia_boost_ghz' => 5.00, 'tdp_watts' => 120, 'tdp_max_watts' => 162, 'frecuencia_memoria_max_mhz' => 5200, 'memoria_max_gb' => 128, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Radeon Graphics (2 CUs)', 'proceso_nm' => 5, 'incluye_cooler' => false, 'overclock' => false]
        );
        $this->crearCPU(
            comp: ['nombre' => 'AMD Ryzen 5 8600G', 'marca' => 'AMD', 'fabricante' => 'AMD', 'modelo' => 'Ryzen 5 8600G', 'descripcion' => 'Procesador AMD Ryzen 5 8600G, 6 núcleos / 12 hilos con gráfica integrada RDNA 3 (Radeon 760M). La mejor opción sin GPU dedicada en AM5.', 'imagen_url' => 'https://sm.pcmag.com/t/pcmag_me/review/a/amd-ryzen-/amd-ryzen-5-8600g_yqwn.1920.jpg'],
            cpu: ['socket' => 'AM5', 'arquitectura' => 'Zen 4', 'tipo_memoria' => 'DDR5', 'nucleos' => 6, 'hilos' => 12, 'frecuencia_base_ghz' => 4.30, 'frecuencia_boost_ghz' => 5.00, 'tdp_watts' => 65, 'tdp_max_watts' => 88, 'frecuencia_memoria_max_mhz' => 5333, 'memoria_max_gb' => 96, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Radeon 760M (8 CUs RDNA 3)', 'proceso_nm' => 4, 'incluye_cooler' => true, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'AMD Ryzen 5 9600X', 'marca' => 'AMD', 'fabricante' => 'AMD', 'modelo' => 'Ryzen 5 9600X', 'descripcion' => 'Procesador AMD Ryzen 5 9600X, 6 núcleos / 12 hilos, arquitectura Zen 5. Mayor IPC y eficiencia mejorada respecto a la generación anterior.', 'imagen_url' => 'https://www.amd.com/content/dam/amd/en/images/products/processors/ryzen/2613900-ryzen-5-9600x-og.jpg'],
            cpu: ['socket' => 'AM5', 'arquitectura' => 'Zen 5', 'tipo_memoria' => 'DDR5', 'nucleos' => 6, 'hilos' => 12, 'frecuencia_base_ghz' => 3.90, 'frecuencia_boost_ghz' => 5.40, 'tdp_watts' => 65, 'tdp_max_watts' => 88, 'frecuencia_memoria_max_mhz' => 5600, 'memoria_max_gb' => 256, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Radeon Graphics (2 CUs)', 'proceso_nm' => 4, 'incluye_cooler' => true, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'AMD Ryzen 7 9700X', 'marca' => 'AMD', 'fabricante' => 'AMD', 'modelo' => 'Ryzen 7 9700X', 'descripcion' => 'Procesador AMD Ryzen 7 9700X, 8 núcleos / 16 hilos, arquitectura Zen 5. Eficiencia líder con 65 W TDP para uso gaming y productividad.', 'imagen_url' => 'https://sm.pcmag.com/t/pcmag_me/review/a/amd-ryzen-/amd-ryzen-7-9700x_qe8d.1920.jpg'],
            cpu: ['socket' => 'AM5', 'arquitectura' => 'Zen 5', 'tipo_memoria' => 'DDR5', 'nucleos' => 8, 'hilos' => 16, 'frecuencia_base_ghz' => 3.80, 'frecuencia_boost_ghz' => 5.50, 'tdp_watts' => 65, 'tdp_max_watts' => 88, 'frecuencia_memoria_max_mhz' => 5600, 'memoria_max_gb' => 256, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Radeon Graphics (2 CUs)', 'proceso_nm' => 4, 'incluye_cooler' => true, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'AMD Ryzen 9 9900X', 'marca' => 'AMD', 'fabricante' => 'AMD', 'modelo' => 'Ryzen 9 9900X', 'descripcion' => 'Procesador AMD Ryzen 9 9900X, 12 núcleos / 24 hilos, arquitectura Zen 5. Alto rendimiento multihilo con notable eficiencia energética.', 'imagen_url' => 'https://www.amd.com/content/dam/amd/en/images/products/processors/ryzen/2613900-ryzen-9-9950x-og.jpg'],
            cpu: ['socket' => 'AM5', 'arquitectura' => 'Zen 5', 'tipo_memoria' => 'DDR5', 'nucleos' => 12, 'hilos' => 24, 'frecuencia_base_ghz' => 4.40, 'frecuencia_boost_ghz' => 5.60, 'tdp_watts' => 120, 'tdp_max_watts' => 162, 'frecuencia_memoria_max_mhz' => 5600, 'memoria_max_gb' => 256, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Radeon Graphics (2 CUs)', 'proceso_nm' => 4, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'AMD Ryzen 9 9950X', 'marca' => 'AMD', 'fabricante' => 'AMD', 'modelo' => 'Ryzen 9 9950X', 'descripcion' => 'Procesador AMD Ryzen 9 9950X, 16 núcleos / 32 hilos, arquitectura Zen 5. El buque insignia de la plataforma AM5 con Zen 5.', 'imagen_url' => 'https://cdn.mos.cms.futurecdn.net/fiXaYnYWnHHAgpM8zj2sUZ.jpg'],
            cpu: ['socket' => 'AM5', 'arquitectura' => 'Zen 5', 'tipo_memoria' => 'DDR5', 'nucleos' => 16, 'hilos' => 32, 'frecuencia_base_ghz' => 4.30, 'frecuencia_boost_ghz' => 5.70, 'tdp_watts' => 170, 'tdp_max_watts' => 230, 'frecuencia_memoria_max_mhz' => 5600, 'memoria_max_gb' => 256, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Radeon Graphics (2 CUs)', 'proceso_nm' => 4, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'AMD Ryzen 7 9800X3D', 'marca' => 'AMD', 'fabricante' => 'AMD', 'modelo' => 'Ryzen 7 9800X3D', 'descripcion' => 'Procesador AMD Ryzen 7 9800X3D, 8 núcleos / 16 hilos con 3D V-Cache y Zen 5. El rey absoluto del gaming en CPU al momento de su lanzamiento.', 'imagen_url' => 'https://gaming-cdn.com/images/news/articles/9020/cover/1000x563/se-han-filtrado-las-especificaciones-del-amd-ryzen-7-9800x3d-cover67207aec774ec.jpg'],
            cpu: ['socket' => 'AM5', 'arquitectura' => 'Zen 5', 'tipo_memoria' => 'DDR5', 'nucleos' => 8, 'hilos' => 16, 'frecuencia_base_ghz' => 4.70, 'frecuencia_boost_ghz' => 5.20, 'tdp_watts' => 120, 'tdp_max_watts' => 162, 'frecuencia_memoria_max_mhz' => 5600, 'memoria_max_gb' => 256, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Radeon Graphics (2 CUs)', 'proceso_nm' => 4, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'AMD Ryzen 9 9900X3D', 'marca' => 'AMD', 'fabricante' => 'AMD', 'modelo' => 'Ryzen 9 9900X3D', 'descripcion' => 'Procesador AMD Ryzen 9 9900X3D, 12 núcleos / 24 hilos con 3D V-Cache y Zen 5. Productividad y gaming de élite en una sola CPU.', 'imagen_url' => 'https://imageio.forbes.com/specials-images/imageserve/674a3a4edec8057b5e79d6de/0x0.jpg?format=jpg&height=900&width=1600&fit=bounds'],
            cpu: ['socket' => 'AM5', 'arquitectura' => 'Zen 5', 'tipo_memoria' => 'DDR5', 'nucleos' => 12, 'hilos' => 24, 'frecuencia_base_ghz' => 4.40, 'frecuencia_boost_ghz' => 5.50, 'tdp_watts' => 120, 'tdp_max_watts' => 162, 'frecuencia_memoria_max_mhz' => 5600, 'memoria_max_gb' => 256, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Radeon Graphics (2 CUs)', 'proceso_nm' => 4, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'AMD Ryzen 9 9950X3D', 'marca' => 'AMD', 'fabricante' => 'AMD', 'modelo' => 'Ryzen 9 9950X3D', 'descripcion' => 'Procesador AMD Ryzen 9 9950X3D, 16 núcleos / 32 hilos con 3D V-Cache y Zen 5. El procesador de consumo más potente de AMD.', 'imagen_url' => 'https://media.ldlc.com/bo/images/fiches/Processeur/AMD/amd_ryzen_9000x3d_003.jpg'],
            cpu: ['socket' => 'AM5', 'arquitectura' => 'Zen 5', 'tipo_memoria' => 'DDR5', 'nucleos' => 16, 'hilos' => 32, 'frecuencia_base_ghz' => 4.30, 'frecuencia_boost_ghz' => 5.70, 'tdp_watts' => 170, 'tdp_max_watts' => 230, 'frecuencia_memoria_max_mhz' => 5600, 'memoria_max_gb' => 256, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Radeon Graphics (2 CUs)', 'proceso_nm' => 4, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'AMD Ryzen 5 5500', 'marca' => 'AMD', 'fabricante' => 'AMD', 'modelo' => 'Ryzen 5 5500', 'descripcion' => 'Procesador AMD Ryzen 5 5500, 6 núcleos / 12 hilos, arquitectura Zen 3. La opción más económica de la familia Ryzen 5000 con cooler incluido.', 'imagen_url' => 'https://www.muycomputer.com/wp-content/uploads/2026/03/amd-ryzen-5-5500.jpg'],
            cpu: ['socket' => 'AM4', 'arquitectura' => 'Zen 3', 'tipo_memoria' => 'DDR4', 'nucleos' => 6, 'hilos' => 12, 'frecuencia_base_ghz' => 3.60, 'frecuencia_boost_ghz' => 4.20, 'tdp_watts' => 65, 'tdp_max_watts' => 76, 'frecuencia_memoria_max_mhz' => 3200, 'memoria_max_gb' => 128, 'grafica_integrada' => false, 'nombre_grafica_integrada' => null, 'proceso_nm' => 7, 'incluye_cooler' => true, 'overclock' => false]
        );
    }

    protected function seedCPUsIntel(): void
    {
        $this->crearCPU(
            comp: ['nombre' => 'Intel Core i5-12600K', 'marca' => 'Intel', 'fabricante' => 'Intel', 'modelo' => 'Core i5-12600K', 'descripcion' => 'Procesador Intel Core i5-12600K, 10 núcleos (6P+4E) / 16 hilos, arquitectura Alder Lake. Introducción de la arquitectura híbrida P+E en Intel.', 'imagen_url' => 'https://assetsio.gnwcdn.com/intel-core-i5-12600K-df-deal.jpg?width=1600&height=900&fit=crop&quality=100&format=png&enable=upscale&auto=webp'],
            cpu: ['socket' => 'LGA1700', 'arquitectura' => 'Alder Lake', 'tipo_memoria' => 'DDR4', 'nucleos' => 10, 'hilos' => 16, 'frecuencia_base_ghz' => 3.70, 'frecuencia_boost_ghz' => 4.90, 'tdp_watts' => 125, 'tdp_max_watts' => 150, 'frecuencia_memoria_max_mhz' => 4800, 'memoria_max_gb' => 128, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Intel UHD Graphics 770', 'proceso_nm' => 10, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'Intel Core i7-12700K', 'marca' => 'Intel', 'fabricante' => 'Intel', 'modelo' => 'Core i7-12700K', 'descripcion' => 'Procesador Intel Core i7-12700K, 12 núcleos (8P+4E) / 20 hilos, arquitectura Alder Lake. Excelente rendimiento multihilo de la 12ª generación.', 'imagen_url' => 'https://m.media-amazon.com/images/I/51aLZnp1eoL._AC_UF350,350_QL80_.jpg'],
            cpu: ['socket' => 'LGA1700', 'arquitectura' => 'Alder Lake', 'tipo_memoria' => 'DDR4', 'nucleos' => 12, 'hilos' => 20, 'frecuencia_base_ghz' => 3.60, 'frecuencia_boost_ghz' => 5.00, 'tdp_watts' => 125, 'tdp_max_watts' => 190, 'frecuencia_memoria_max_mhz' => 4800, 'memoria_max_gb' => 128, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Intel UHD Graphics 770', 'proceso_nm' => 10, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'Intel Core i9-12900K', 'marca' => 'Intel', 'fabricante' => 'Intel', 'modelo' => 'Core i9-12900K', 'descripcion' => 'Procesador Intel Core i9-12900K, 16 núcleos (8P+8E) / 24 hilos, arquitectura Alder Lake. El flagship de la 12ª generación Intel.', 'imagen_url' => 'https://news.mcr.com.es/wp-content/uploads/2021/11/12thgen-promo.jpg.rendition.intel_.web_.720.405.jpg'],
            cpu: ['socket' => 'LGA1700', 'arquitectura' => 'Alder Lake', 'tipo_memoria' => 'DDR4', 'nucleos' => 16, 'hilos' => 24, 'frecuencia_base_ghz' => 3.20, 'frecuencia_boost_ghz' => 5.20, 'tdp_watts' => 125, 'tdp_max_watts' => 241, 'frecuencia_memoria_max_mhz' => 4800, 'memoria_max_gb' => 128, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Intel UHD Graphics 770', 'proceso_nm' => 10, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'Intel Core i5-13400F', 'marca' => 'Intel', 'fabricante' => 'Intel', 'modelo' => 'Core i5-13400F', 'descripcion' => 'Procesador Intel Core i5-13400F, 10 núcleos (6P+4E) / 16 hilos sin gráfica integrada. La opción gaming más popular de la 13ª generación Intel.', 'imagen_url' => 'https://coolboxpe.vtexassets.com/arquivos/ids/347294-800-800?v=638774832885400000&width=800&height=800&aspect=true'],
            cpu: ['socket' => 'LGA1700', 'arquitectura' => 'Raptor Lake', 'tipo_memoria' => 'DDR4', 'nucleos' => 10, 'hilos' => 16, 'frecuencia_base_ghz' => 2.50, 'frecuencia_boost_ghz' => 4.60, 'tdp_watts' => 65, 'tdp_max_watts' => 148, 'frecuencia_memoria_max_mhz' => 4800, 'memoria_max_gb' => 128, 'grafica_integrada' => false, 'nombre_grafica_integrada' => null, 'proceso_nm' => 10, 'incluye_cooler' => true, 'overclock' => false]
        );
        $this->crearCPU(
            comp: ['nombre' => 'Intel Core i9-13900KS', 'marca' => 'Intel', 'fabricante' => 'Intel', 'modelo' => 'Core i9-13900KS', 'descripcion' => 'Procesador Intel Core i9-13900KS, 24 núcleos / 32 hilos con boost hasta 6.0 GHz. El primer procesador consumer en superar los 6 GHz de serie.', 'imagen_url' => 'https://elchapuzasinformatico.com/wp-content/uploads/2023/01/Intel-Core-i9-13900KS-en-caja.jpg'],
            cpu: ['socket' => 'LGA1700', 'arquitectura' => 'Raptor Lake', 'tipo_memoria' => 'DDR4', 'nucleos' => 24, 'hilos' => 32, 'frecuencia_base_ghz' => 3.20, 'frecuencia_boost_ghz' => 6.00, 'tdp_watts' => 150, 'tdp_max_watts' => 253, 'frecuencia_memoria_max_mhz' => 5600, 'memoria_max_gb' => 128, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Intel UHD Graphics 770', 'proceso_nm' => 10, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'Intel Core i5-14600K', 'marca' => 'Intel', 'fabricante' => 'Intel', 'modelo' => 'Core i5-14600K', 'descripcion' => 'Procesador Intel Core i5-14600K, 14 núcleos (6P+8E) / 20 hilos, Raptor Lake Refresh. Frecuencias mejoradas sobre la generación anterior.', 'imagen_url' => 'https://m.media-amazon.com/images/I/61YGTkihxxL._AC_UF894,1000_QL80_.jpg'],
            cpu: ['socket' => 'LGA1700', 'arquitectura' => 'Raptor Lake Refresh', 'tipo_memoria' => 'DDR4', 'nucleos' => 14, 'hilos' => 20, 'frecuencia_base_ghz' => 3.50, 'frecuencia_boost_ghz' => 5.30, 'tdp_watts' => 125, 'tdp_max_watts' => 181, 'frecuencia_memoria_max_mhz' => 5600, 'memoria_max_gb' => 192, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Intel UHD Graphics 770', 'proceso_nm' => 10, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'Intel Core i7-14700K', 'marca' => 'Intel', 'fabricante' => 'Intel', 'modelo' => 'Core i7-14700K', 'descripcion' => 'Procesador Intel Core i7-14700K, 20 núcleos (8P+12E) / 28 hilos, Raptor Lake Refresh. Añade 4 núcleos E extra respecto al i7-13700K.', 'imagen_url' => 'https://m.media-amazon.com/images/I/61C1DOLRK4L._AC_UF894,1000_QL80_.jpg'],
            cpu: ['socket' => 'LGA1700', 'arquitectura' => 'Raptor Lake Refresh', 'tipo_memoria' => 'DDR4', 'nucleos' => 20, 'hilos' => 28, 'frecuencia_base_ghz' => 3.40, 'frecuencia_boost_ghz' => 5.60, 'tdp_watts' => 125, 'tdp_max_watts' => 253, 'frecuencia_memoria_max_mhz' => 5600, 'memoria_max_gb' => 192, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Intel UHD Graphics 770', 'proceso_nm' => 10, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'Intel Core i9-14900K', 'marca' => 'Intel', 'fabricante' => 'Intel', 'modelo' => 'Core i9-14900K', 'descripcion' => 'Procesador Intel Core i9-14900K, 24 núcleos (8P+16E) / 32 hilos, Raptor Lake Refresh. Velocidades boost hasta 6.0 GHz.', 'imagen_url' => 'https://media.ldlc.com/bo/images/fiches/Processeur/Intel/intel_core_14th_i9k_001.jpg'],
            cpu: ['socket' => 'LGA1700', 'arquitectura' => 'Raptor Lake Refresh', 'tipo_memoria' => 'DDR4', 'nucleos' => 24, 'hilos' => 32, 'frecuencia_base_ghz' => 3.20, 'frecuencia_boost_ghz' => 6.00, 'tdp_watts' => 125, 'tdp_max_watts' => 253, 'frecuencia_memoria_max_mhz' => 5600, 'memoria_max_gb' => 192, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Intel UHD Graphics 770', 'proceso_nm' => 10, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'Intel Core i5-14400F', 'marca' => 'Intel', 'fabricante' => 'Intel', 'modelo' => 'Core i5-14400F', 'descripcion' => 'Procesador Intel Core i5-14400F, 10 núcleos / 16 hilos sin iGPU. Relación precio-rendimiento imbatible para gaming en la 14ª gen.', 'imagen_url' => 'https://arcintech.mx/cdn/shop/files/A-318-1-BX8071514400F_large.jpg?v=1752945501'],
            cpu: ['socket' => 'LGA1700', 'arquitectura' => 'Raptor Lake Refresh', 'tipo_memoria' => 'DDR4', 'nucleos' => 10, 'hilos' => 16, 'frecuencia_base_ghz' => 2.50, 'frecuencia_boost_ghz' => 4.70, 'tdp_watts' => 65, 'tdp_max_watts' => 148, 'frecuencia_memoria_max_mhz' => 4800, 'memoria_max_gb' => 192, 'grafica_integrada' => false, 'nombre_grafica_integrada' => null, 'proceso_nm' => 10, 'incluye_cooler' => true, 'overclock' => false]
        );
        $this->crearCPU(
            comp: ['nombre' => 'Intel Core i9-14900KS', 'marca' => 'Intel', 'fabricante' => 'Intel', 'modelo' => 'Core i9-14900KS', 'descripcion' => 'Procesador Intel Core i9-14900KS, 24 núcleos / 32 hilos con boost hasta 6.2 GHz. El procesador de consumo más rápido de Intel en LGA1700.', 'imagen_url' => 'https://static0.xdaimages.com/wordpress/wp-content/uploads/2024/03/intel-core-i9-14900ks-custom-press-render.jpg?w=1200&h=675&fit=crop'],
            cpu: ['socket' => 'LGA1700', 'arquitectura' => 'Raptor Lake Refresh', 'tipo_memoria' => 'DDR4', 'nucleos' => 24, 'hilos' => 32, 'frecuencia_base_ghz' => 3.20, 'frecuencia_boost_ghz' => 6.20, 'tdp_watts' => 150, 'tdp_max_watts' => 253, 'frecuencia_memoria_max_mhz' => 5600, 'memoria_max_gb' => 192, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Intel UHD Graphics 770', 'proceso_nm' => 10, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'Intel Core Ultra 5 245K', 'marca' => 'Intel', 'fabricante' => 'Intel', 'modelo' => 'Core Ultra 5 245K', 'descripcion' => 'Procesador Intel Core Ultra 5 245K, 14 núcleos (6P+8E) / 14 hilos, arquitectura Arrow Lake (LGA1851). Primera generación de Core Ultra desktop.', 'imagen_url' => 'https://m.media-amazon.com/images/I/51GbMCaIHWL.jpg'],
            cpu: ['socket' => 'LGA1851', 'arquitectura' => 'Arrow Lake', 'tipo_memoria' => 'DDR5', 'nucleos' => 14, 'hilos' => 14, 'frecuencia_base_ghz' => 3.60, 'frecuencia_boost_ghz' => 5.20, 'tdp_watts' => 125, 'tdp_max_watts' => 159, 'frecuencia_memoria_max_mhz' => 6400, 'memoria_max_gb' => 192, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Intel Arc Graphics (4 Xe-cores)', 'proceso_nm' => 3, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'Intel Core Ultra 7 265K', 'marca' => 'Intel', 'fabricante' => 'Intel', 'modelo' => 'Core Ultra 7 265K', 'descripcion' => 'Procesador Intel Core Ultra 7 265K, 20 núcleos (8P+12E) / 20 hilos, arquitectura Arrow Lake. Arquitectura híbrida de nueva generación con gráfica Arc integrada.', 'imagen_url' => 'https://m.media-amazon.com/images/I/51CzdMdSowL._AC_UF350,350_QL80_.jpg'],
            cpu: ['socket' => 'LGA1851', 'arquitectura' => 'Arrow Lake', 'tipo_memoria' => 'DDR5', 'nucleos' => 20, 'hilos' => 20, 'frecuencia_base_ghz' => 3.90, 'frecuencia_boost_ghz' => 5.50, 'tdp_watts' => 125, 'tdp_max_watts' => 159, 'frecuencia_memoria_max_mhz' => 6400, 'memoria_max_gb' => 192, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Intel Arc Graphics (4 Xe-cores)', 'proceso_nm' => 3, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'Intel Core Ultra 9 285K', 'marca' => 'Intel', 'fabricante' => 'Intel', 'modelo' => 'Core Ultra 9 285K', 'descripcion' => 'Procesador Intel Core Ultra 9 285K, 24 núcleos (8P+16E) / 24 hilos, arquitectura Arrow Lake. El flagship desktop de Intel en la plataforma LGA1851.', 'imagen_url' => 'https://med.greatecno.com/1301925/intel-core-ultra-9-285k-5-7ghz-socket-1851-boxed.jpg'],
            cpu: ['socket' => 'LGA1851', 'arquitectura' => 'Arrow Lake', 'tipo_memoria' => 'DDR5', 'nucleos' => 24, 'hilos' => 24, 'frecuencia_base_ghz' => 3.70, 'frecuencia_boost_ghz' => 5.70, 'tdp_watts' => 125, 'tdp_max_watts' => 159, 'frecuencia_memoria_max_mhz' => 6400, 'memoria_max_gb' => 192, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Intel Arc Graphics (4 Xe-cores)', 'proceso_nm' => 3, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'Intel Core i5-12400F', 'marca' => 'Intel', 'fabricante' => 'Intel', 'modelo' => 'Core i5-12400F', 'descripcion' => 'Procesador Intel Core i5-12400F, 6 núcleos / 12 hilos sin iGPU, arquitectura Alder Lake. La revelación de la 12ª gen por su precio y rendimiento.', 'imagen_url' => 'https://t.ctcdn.com.br/F438ZEnaK3q3DQuB4lrRTmm-GGo=/320x180/smart/i987987.png'],
            cpu: ['socket' => 'LGA1700', 'arquitectura' => 'Alder Lake', 'tipo_memoria' => 'DDR4', 'nucleos' => 6, 'hilos' => 12, 'frecuencia_base_ghz' => 2.50, 'frecuencia_boost_ghz' => 4.40, 'tdp_watts' => 65, 'tdp_max_watts' => 117, 'frecuencia_memoria_max_mhz' => 4800, 'memoria_max_gb' => 128, 'grafica_integrada' => false, 'nombre_grafica_integrada' => null, 'proceso_nm' => 10, 'incluye_cooler' => true, 'overclock' => false]
        );
        $this->crearCPU(
            comp: ['nombre' => 'Intel Core i7-12700', 'marca' => 'Intel', 'fabricante' => 'Intel', 'modelo' => 'Core i7-12700', 'descripcion' => 'Procesador Intel Core i7-12700, 12 núcleos (8P+4E) / 20 hilos, Alder Lake no-K. Incluye cooler y eficiencia mejorada con 65 W PBP.', 'imagen_url' => 'https://img.danuri.io/catalog-image/001/038/032/89f3a4b784fc4ffa88e8ab3425f890b9.jpg?shrink=360:360&_v=20260506154318'],
            cpu: ['socket' => 'LGA1700', 'arquitectura' => 'Alder Lake', 'tipo_memoria' => 'DDR4', 'nucleos' => 12, 'hilos' => 20, 'frecuencia_base_ghz' => 2.10, 'frecuencia_boost_ghz' => 4.90, 'tdp_watts' => 65, 'tdp_max_watts' => 180, 'frecuencia_memoria_max_mhz' => 4800, 'memoria_max_gb' => 128, 'grafica_integrada' => true, 'nombre_grafica_integrada' => 'Intel UHD Graphics 770', 'proceso_nm' => 10, 'incluye_cooler' => true, 'overclock' => false]
        );
        $this->crearCPU(
            comp: ['nombre' => 'Intel Core i7-13700F', 'marca' => 'Intel', 'fabricante' => 'Intel', 'modelo' => 'Core i7-13700F', 'descripcion' => 'Procesador Intel Core i7-13700F, 16 núcleos / 24 hilos sin iGPU, Raptor Lake non-K. Versión con 65 W PBP y cooler incluido.', 'imagen_url' => 'https://www.profesionalreview.com/wp-content/uploads/2023/01/Intel-Core-Raptor-Lake-Lanzan-16-nuevos-procesadores-de-65W-y-35W_4.jpg'],
            cpu: ['socket' => 'LGA1700', 'arquitectura' => 'Raptor Lake', 'tipo_memoria' => 'DDR4', 'nucleos' => 16, 'hilos' => 24, 'frecuencia_base_ghz' => 2.20, 'frecuencia_boost_ghz' => 5.20, 'tdp_watts' => 65, 'tdp_max_watts' => 219, 'frecuencia_memoria_max_mhz' => 5200, 'memoria_max_gb' => 128, 'grafica_integrada' => false, 'nombre_grafica_integrada' => null, 'proceso_nm' => 10, 'incluye_cooler' => true, 'overclock' => false]
        );
        $this->crearCPU(
            comp: ['nombre' => 'Intel Core i5-14600KF', 'marca' => 'Intel', 'fabricante' => 'Intel', 'modelo' => 'Core i5-14600KF', 'descripcion' => 'Procesador Intel Core i5-14600KF, 14 núcleos / 20 hilos sin iGPU, Raptor Lake Refresh. La versión desbloqueada sin gráfica del i5-14600K.', 'imagen_url' => 'https://technoidinc.com/cdn/shop/articles/Intel_Core_i5-14600KF_-_Perfect_Balance_of_Power_and_Affordability.webp?v=1742290481'],
            cpu: ['socket' => 'LGA1700', 'arquitectura' => 'Raptor Lake Refresh', 'tipo_memoria' => 'DDR4', 'nucleos' => 14, 'hilos' => 20, 'frecuencia_base_ghz' => 3.50, 'frecuencia_boost_ghz' => 5.30, 'tdp_watts' => 125, 'tdp_max_watts' => 181, 'frecuencia_memoria_max_mhz' => 5600, 'memoria_max_gb' => 192, 'grafica_integrada' => false, 'nombre_grafica_integrada' => null, 'proceso_nm' => 10, 'incluye_cooler' => false, 'overclock' => true]
        );
        $this->crearCPU(
            comp: ['nombre' => 'Intel Core i7-14700F', 'marca' => 'Intel', 'fabricante' => 'Intel', 'modelo' => 'Core i7-14700F', 'descripcion' => 'Procesador Intel Core i7-14700F, 20 núcleos / 28 hilos sin iGPU, Raptor Lake Refresh non-K. Precio competitivo con cooler incluido.', 'imagen_url' => 'https://cdn.mos.cms.futurecdn.net/fzz8dhAFBfw8U5Qn5bdJGD.jpg'],
            cpu: ['socket' => 'LGA1700', 'arquitectura' => 'Raptor Lake Refresh', 'tipo_memoria' => 'DDR4', 'nucleos' => 20, 'hilos' => 28, 'frecuencia_base_ghz' => 2.10, 'frecuencia_boost_ghz' => 5.40, 'tdp_watts' => 65, 'tdp_max_watts' => 219, 'frecuencia_memoria_max_mhz' => 5600, 'memoria_max_gb' => 192, 'grafica_integrada' => false, 'nombre_grafica_integrada' => null, 'proceso_nm' => 10, 'incluye_cooler' => true, 'overclock' => false]
        );
        $this->crearCPU(
            comp: ['nombre' => 'Intel Core Ultra 5 245KF', 'marca' => 'Intel', 'fabricante' => 'Intel', 'modelo' => 'Core Ultra 5 245KF', 'descripcion' => 'Procesador Intel Core Ultra 5 245KF, 14 núcleos / 14 hilos sin iGPU, arquitectura Arrow Lake. La opción más asequible desbloqueada de la plataforma LGA1851.', 'imagen_url' => 'https://m.media-amazon.com/images/I/61aH6Abr0zL.jpg'],
            cpu: ['socket' => 'LGA1851', 'arquitectura' => 'Arrow Lake', 'tipo_memoria' => 'DDR5', 'nucleos' => 14, 'hilos' => 14, 'frecuencia_base_ghz' => 3.60, 'frecuencia_boost_ghz' => 5.20, 'tdp_watts' => 125, 'tdp_max_watts' => 159, 'frecuencia_memoria_max_mhz' => 6400, 'memoria_max_gb' => 192, 'grafica_integrada' => false, 'nombre_grafica_integrada' => null, 'proceso_nm' => 3, 'incluye_cooler' => false, 'overclock' => true]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PLACAS BASE
    // ─────────────────────────────────────────────────────────────────────────

    protected array $chipsets  = [];
    protected array $factores  = [];
    protected array $versionesPcie = [];

    protected function crearPlacaBase(array $comp, array $pb): void
    {
        $marcaId = $this->marcas[$comp['marca']] ?? null;
        $fabId   = $this->marcas[$comp['fabricante']] ?? $marcaId;
        $componente = \App\Models\Componentes\Componente::create(['nombre' => $comp['nombre'], 'marca_id' => $marcaId, 'fabricante_id' => $fabId, 'categoria' => 'placa_base', 'modelo' => $comp['modelo'], 'imagen_url' => $comp['imagen_url'] ?? null, 'descripcion' => $comp['descripcion'] ?? null, 'activo' => true]);
        \App\Models\Componentes\PlacaBase::create(['componente_id' => $componente->id, 'socket_id' => $this->sockets[$pb['socket']] ?? null, 'chipset_id' => $this->chipsets[$pb['chipset']] ?? null, 'factor_forma_id' => $this->factores[$pb['factor_forma']] ?? null, 'tipo_memoria_id' => $this->tiposRam[$pb['tipo_memoria']] ?? null, 'version_pcie_id' => $this->versionesPcie[$pb['version_pcie']] ?? null, 'slots_memoria' => $pb['slots_memoria'], 'memoria_max_gb' => $pb['memoria_max_gb'], 'frecuencia_memoria_max_mhz' => $pb['frecuencia_memoria_max_mhz'], 'slots_pcie_x16' => $pb['slots_pcie_x16'], 'slots_pcie_x4' => $pb['slots_pcie_x4'] ?? 0, 'slots_pcie_x1' => $pb['slots_pcie_x1'] ?? 0, 'slots_m2' => $pb['slots_m2'], 'puertos_sata' => $pb['puertos_sata'], 'puertos_usb_traseros' => $pb['puertos_usb_traseros'], 'conector_atx' => $pb['conector_atx'], 'conector_cpu' => $pb['conector_cpu'], 'wifi' => $pb['wifi'], 'bluetooth' => $pb['bluetooth'], 'thunderbolt' => $pb['thunderbolt'] ?? false, 'audio_chipset' => $pb['audio_chipset'], 'lan_chipset' => $pb['lan_chipset'], 'lan_velocidad_gbps' => $pb['lan_velocidad_gbps']]);
    }

    protected function cargarAuxiliaresPlacasBase(): void
    {
        foreach (\App\Models\Auxiliares\Chipset::all() as $c)      { $this->chipsets[$c->nombre]       = $c->id; }
        foreach (\App\Models\Auxiliares\FactorForma::all() as $f)  { $this->factores[$f->nombre]        = $f->id; }
        foreach (\App\Models\Auxiliares\VersionPCIe::all() as $v)  { $this->versionesPcie[$v->nombre]  = $v->id; }
    }

    public function seedPlacasBase(): void
    {
        $this->cargarAuxiliaresPlacasBase();
        $this->seedPBAM4();
        $this->seedPBAM5();
        $this->seedPBLGA1700();
        $this->seedPBLGA1851();
    }
    // ── AM4 ── 12 placas (3 ATX B550, 3 ATX X570, 3 mATX B550, 3 ITX B550/X570) + extras ──
    protected function seedPBAM4(): void
    {
        $this->crearPlacaBase(
            comp: ['nombre' => 'ASUS ROG Strix B550-F Gaming', 'marca' => 'ASUS', 'fabricante' => 'ASUS', 'modelo' => 'ROG Strix B550-F Gaming', 'descripcion' => 'Placa base ATX AM4 con chipset B550. Excelente VRM, Wi-Fi 6 integrado, 2.5G LAN y soporte PCIe 4.0 desde CPU. Ideal para Ryzen 5000.', 'imagen_url' => 'https://www.aussar.es/81951-large_default/asus-am4-rog-strix-b550-f-gaming-wifi-ii.jpg'],
            pb: ['socket' => 'AM4', 'chipset' => 'B550', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR4', 'version_pcie' => 'PCIe 4.0', 'slots_memoria' => 4, 'memoria_max_gb' => 128, 'frecuencia_memoria_max_mhz' => 4400, 'slots_pcie_x16' => 2, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 2, 'slots_m2' => 2, 'puertos_sata' => 6, 'puertos_usb_traseros' => ['USB 3.2 Gen2 Type-A x4', 'USB 3.2 Gen2 Type-C x1', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC1220', 'lan_chipset' => 'Intel I225-V', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'Gigabyte X570 Aorus Master', 'marca' => 'Gigabyte', 'fabricante' => 'Gigabyte', 'modelo' => 'X570 Aorus Master', 'descripcion' => 'Placa base ATX AM4 X570 flagship de Gigabyte. VRM de 14+2 fases con thermal armor, Wi-Fi 6 y ALC1220-VB. Referencia para Ryzen 9 5950X.', 'imagen_url' => 'https://cdn.mos.cms.futurecdn.net/wgYZfV3o2YvJnKPqose95E-1200-80.png'],
            pb: ['socket' => 'AM4', 'chipset' => 'X570', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR4', 'version_pcie' => 'PCIe 4.0', 'slots_memoria' => 4, 'memoria_max_gb' => 128, 'frecuencia_memoria_max_mhz' => 5400, 'slots_pcie_x16' => 3, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 3, 'puertos_sata' => 6, 'puertos_usb_traseros' => ['USB 3.2 Gen2 Type-A x4', 'USB 3.2 Gen2x2 Type-C x1', 'USB 3.2 Gen1 Type-A x2', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC1220-VB', 'lan_chipset' => 'Intel I225-V', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'MSI B550M Pro-VDH WiFi', 'marca' => 'MSI', 'fabricante' => 'MSI', 'modelo' => 'B550M Pro-VDH WiFi', 'descripcion' => 'Placa base Micro-ATX AM4 B550 económica con Wi-Fi y 1G LAN. Ideal para builds compactos con Ryzen 5000 de bajo presupuesto.', 'imagen_url' => 'https://http2.mlstatic.com/D_NQ_NP_752151-MLA105019285590_012026-O.webp'],
            pb: ['socket' => 'AM4', 'chipset' => 'B550', 'factor_forma' => 'Micro-ATX', 'tipo_memoria' => 'DDR4', 'version_pcie' => 'PCIe 4.0', 'slots_memoria' => 4, 'memoria_max_gb' => 128, 'frecuencia_memoria_max_mhz' => 4800, 'slots_pcie_x16' => 1, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 2, 'slots_m2' => 2, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB 3.2 Gen1 Type-A x4', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC892', 'lan_chipset' => 'Realtek RTL8111H', 'lan_velocidad_gbps' => 1.0]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'MSI MPG B550I Gaming Edge WiFi', 'marca' => 'MSI', 'fabricante' => 'MSI', 'modelo' => 'MPG B550I Gaming Edge WiFi', 'descripcion' => 'Placa base Mini-ITX AM4 B550 con Wi-Fi 6 y 2.5G LAN. VRM de 8+2 fases y doble M.2 en formato ultracompacto para builds SFF gaming.', 'imagen_url' => 'https://storage-asset.msi.com/global/picture/image/feature/mb/B550I/edge-wifi/audio-hero.jpg'],
            pb: ['socket' => 'AM4', 'chipset' => 'B550', 'factor_forma' => 'Mini-ITX', 'tipo_memoria' => 'DDR4', 'version_pcie' => 'PCIe 4.0', 'slots_memoria' => 2, 'memoria_max_gb' => 64, 'frecuencia_memoria_max_mhz' => 5000, 'slots_pcie_x16' => 1, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 0, 'slots_m2' => 2, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB 3.2 Gen2 Type-A x2', 'USB 3.2 Gen2 Type-C x1', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC1220', 'lan_chipset' => 'Realtek RTL8125BG', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'ASRock B550 Phantom Gaming 4', 'marca' => 'ASRock', 'fabricante' => 'ASRock', 'modelo' => 'B550 Phantom Gaming 4', 'descripcion' => 'Placa base ATX AM4 B550 de entrada con 8+2 fases VRM y 1G LAN. La opción más económica ATX con soporte PCIe 4.0 para builds con Ryzen 5 5600.', 'imagen_url' => 'https://img.pccomponentes.com/articles/30/306715/145-asrock-b550-phantom-gaming-4-opiniones.jpg'],
            pb: ['socket' => 'AM4', 'chipset' => 'B550', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR4', 'version_pcie' => 'PCIe 4.0', 'slots_memoria' => 4, 'memoria_max_gb' => 128, 'frecuencia_memoria_max_mhz' => 4800, 'slots_pcie_x16' => 2, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 2, 'puertos_sata' => 6, 'puertos_usb_traseros' => ['USB 3.2 Gen1 Type-A x4', 'USB 2.0 x4'], 'conector_atx' => '24-pin', 'conector_cpu' => '8-pin', 'wifi' => false, 'bluetooth' => false, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC887', 'lan_chipset' => 'Realtek RTL8111H', 'lan_velocidad_gbps' => 1.0]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'ASUS TUF Gaming B550M-Plus WiFi II', 'marca' => 'ASUS', 'fabricante' => 'ASUS', 'modelo' => 'TUF Gaming B550M-Plus WiFi II', 'descripcion' => 'Placa base Micro-ATX AM4 B550 con Wi-Fi 6, 2.5G LAN y VRM de 10 fases. La gama TUF ofrece durabilidad militar y diseño gaming sin excesos.', 'imagen_url' => 'https://thumb.pccomponentes.com/w-530-530/articles/82/825627/1274-asus-tuf-gaming-b550m-plus-wifi-ii.jpg'],
            pb: ['socket' => 'AM4', 'chipset' => 'B550', 'factor_forma' => 'Micro-ATX', 'tipo_memoria' => 'DDR4', 'version_pcie' => 'PCIe 4.0', 'slots_memoria' => 4, 'memoria_max_gb' => 128, 'frecuencia_memoria_max_mhz' => 4800, 'slots_pcie_x16' => 2, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 2, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB 3.2 Gen2 Type-A x2', 'USB 3.2 Gen1 Type-A x2', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC1200', 'lan_chipset' => 'Intel I225-V', 'lan_velocidad_gbps' => 2.5]
        );
    }
    // ── AM5 ── 12 placas (3 ATX B650, 3 ATX X670E, 3 mATX B650, 3 ITX B650) + extras ──
    protected function seedPBAM5(): void
    {
        $this->crearPlacaBase(
            comp: ['nombre' => 'ASUS TUF Gaming B650-Plus WiFi', 'marca' => 'ASUS', 'fabricante' => 'ASUS', 'modelo' => 'TUF Gaming B650-Plus WiFi', 'descripcion' => 'Placa base ATX AM5 B650 con Wi-Fi 6E y 2.5G LAN. VRM de 12+2 fases y DDR5. La entrada equilibrada a la plataforma AM5 con soporte Ryzen 7000/9000.', 'imagen_url' => 'https://centergamingespana.com/wp-content/uploads/2022/10/asus-tuf-gaming-b650-plus-wifi-1.jpg'],
            pb: ['socket' => 'AM5', 'chipset' => 'B650', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 192, 'frecuencia_memoria_max_mhz' => 7200, 'slots_pcie_x16' => 2, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 3, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB 3.2 Gen2 Type-A x4', 'USB 3.2 Gen2 Type-C x1', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC897', 'lan_chipset' => 'Realtek RTL8125BG', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'Gigabyte B650 Aorus Elite AX', 'marca' => 'Gigabyte', 'fabricante' => 'Gigabyte', 'modelo' => 'B650 Aorus Elite AX', 'descripcion' => 'Placa base ATX AM5 B650 con Wi-Fi 6E, USB4 Type-C y 2.5G LAN. Diseño Aorus con 16+2+2 fases VRM para AM5 a precio ajustado.', 'imagen_url' => 'https://www.gigabyte.com/FileUpload/Global/KeyFeature/2192/innergigabyteimages/AESTHETICS.jpg'],
            pb: ['socket' => 'AM5', 'chipset' => 'B650', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 192, 'frecuencia_memoria_max_mhz' => 8000, 'slots_pcie_x16' => 2, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 4, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB 3.2 Gen2 Type-A x4', 'USB4 Type-C 40Gbps x1', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC1220', 'lan_chipset' => 'Realtek RTL8125BG', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'ASUS ROG Crosshair X670E Hero', 'marca' => 'ASUS', 'fabricante' => 'ASUS', 'modelo' => 'ROG Crosshair X670E Hero', 'descripcion' => 'Placa base ATX AM5 X670E flagship de ASUS. VRM de 18+2 fases, PCIe 5.0 completo, Wi-Fi 6E, USB4 y 10G LAN. Para los Ryzen 9000X3D de gama alta.', 'imagen_url' => 'https://i.ytimg.com/vi/6DOEPAEN0bY/hq720.jpg?sqp=-oaymwEhCK4FEIIDSFryq4qpAxMIARUAAAAAGAElAADIQj0AgKJD&rs=AOn4CLDsmAx-k17zP4RkKkWEPht4OKgyEQ'],
            pb: ['socket' => 'AM5', 'chipset' => 'X670E', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 256, 'frecuencia_memoria_max_mhz' => 8000, 'slots_pcie_x16' => 3, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 5, 'puertos_sata' => 6, 'puertos_usb_traseros' => ['USB4 Type-C 40Gbps x2', 'USB 3.2 Gen2 Type-A x4', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC4082', 'lan_chipset' => 'Intel I226-V + Marvell AQtion', 'lan_velocidad_gbps' => 10.0]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'MSI MEG X670E Ace', 'marca' => 'MSI', 'fabricante' => 'MSI', 'modelo' => 'MEG X670E Ace', 'descripcion' => 'Placa base ATX AM5 X670E con VRM de 24+2 fases, Wi-Fi 6E, USB4, 10G LAN y PCIe 5.0 en CPU y M.2. El flagship de MSI para AM5.', 'imagen_url' => 'https://tpucdn.com/review/msi-meg-x670e-ace/images/title.jpg'],
            pb: ['socket' => 'AM5', 'chipset' => 'X670E', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 256, 'frecuencia_memoria_max_mhz' => 8000, 'slots_pcie_x16' => 3, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 5, 'puertos_sata' => 6, 'puertos_usb_traseros' => ['USB4 Type-C 40Gbps x1', 'USB 3.2 Gen2x2 Type-C x1', 'USB 3.2 Gen2 Type-A x4', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC4082', 'lan_chipset' => 'Marvell AQtion AQC113CS', 'lan_velocidad_gbps' => 10.0]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'Gigabyte X670E Aorus Master', 'marca' => 'Gigabyte', 'fabricante' => 'Gigabyte', 'modelo' => 'X670E Aorus Master', 'descripcion' => 'Placa base ATX AM5 X670E con VRM de 18+2 fases, Wi-Fi 6E y PCIe 5.0 completo. La referencia de Gigabyte para Ryzen 9000X3D con overclocking extremo.', 'imagen_url' => 'https://tpucdn.com/review/gigabyte-x670e-aorus-master/images/title.jpg'],
            pb: ['socket' => 'AM5', 'chipset' => 'X670E', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 256, 'frecuencia_memoria_max_mhz' => 8000, 'slots_pcie_x16' => 3, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 4, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB4 Type-C 40Gbps x1', 'USB 3.2 Gen2 Type-A x4', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC1220-VB', 'lan_chipset' => 'Intel I225-V', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'MSI PRO B650M-A WiFi', 'marca' => 'MSI', 'fabricante' => 'MSI', 'modelo' => 'PRO B650M-A WiFi', 'descripcion' => 'Placa base Micro-ATX AM5 B650 orientada a profesionales. Wi-Fi 6E, 2.5G LAN y diseño sin RGB. Fiabilidad por encima del rendimiento extremo.', 'imagen_url' => 'https://storage-asset.msi.com/global/picture/image/feature/mb/B650M/PRO-B650M-A-WIFI/pcb.jpg'],
            pb: ['socket' => 'AM5', 'chipset' => 'B650', 'factor_forma' => 'Micro-ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 192, 'frecuencia_memoria_max_mhz' => 7200, 'slots_pcie_x16' => 2, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 2, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB 3.2 Gen2 Type-A x2', 'USB 3.2 Gen1 Type-A x4', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC897', 'lan_chipset' => 'Realtek RTL8125BG', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'Gigabyte B650M Aorus Elite AX', 'marca' => 'Gigabyte', 'fabricante' => 'Gigabyte', 'modelo' => 'B650M Aorus Elite AX', 'descripcion' => 'Placa base Micro-ATX AM5 B650 con Wi-Fi 6E, 2.5G LAN y VRM de 12+2+2 fases. Tres ranuras M.2 con PCIe 4.0 en un factor de forma compacto.', 'imagen_url' => 'https://www.gigabyte.com/FileUpload/Global/KeyFeature/2196/innergigabyteimages/AESTHETICS.jpg'],
            pb: ['socket' => 'AM5', 'chipset' => 'B650', 'factor_forma' => 'Micro-ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 192, 'frecuencia_memoria_max_mhz' => 8000, 'slots_pcie_x16' => 2, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 3, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB 3.2 Gen2 Type-A x2', 'USB 3.2 Gen2 Type-C x1', 'USB 3.2 Gen1 Type-A x2', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC1220', 'lan_chipset' => 'Realtek RTL8125BG', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'ASUS ROG Strix B650E-I Gaming WiFi', 'marca' => 'ASUS', 'fabricante' => 'ASUS', 'modelo' => 'ROG Strix B650E-I Gaming WiFi', 'descripcion' => 'Placa base Mini-ITX AM5 B650E con Wi-Fi 6E y PCIe 5.0 en slot GPU. La mejor opción ITX para AM5, soportando los procesadores Ryzen 9000 de forma compacta.', 'imagen_url' => 'https://m.media-amazon.com/images/I/71VNHwKpzcL.jpg'],
            pb: ['socket' => 'AM5', 'chipset' => 'B650E', 'factor_forma' => 'Mini-ITX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 2, 'memoria_max_gb' => 96, 'frecuencia_memoria_max_mhz' => 8000, 'slots_pcie_x16' => 1, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 0, 'slots_m2' => 2, 'puertos_sata' => 2, 'puertos_usb_traseros' => ['USB4 Type-C 40Gbps x1', 'USB 3.2 Gen2 Type-A x2', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC4082', 'lan_chipset' => 'Intel I226-V', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'ASUS ProArt X670E-Creator WiFi', 'marca' => 'ASUS', 'fabricante' => 'ASUS', 'modelo' => 'ProArt X670E-Creator WiFi', 'descripcion' => 'Placa base ATX AM5 X670E orientada a creadores con Thunderbolt 4, Wi-Fi 6E, 10G LAN y cinco M.2. VRM de 16 fases para workstations Ryzen 9.', 'imagen_url' => 'https://dlcdnwebimgs.asus.com/files/media/25ea779c-331a-4b78-9c95-35ad9d46e3ec/v1/img/kv/kv.jpg'],
            pb: ['socket' => 'AM5', 'chipset' => 'X670E', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 256, 'frecuencia_memoria_max_mhz' => 8000, 'slots_pcie_x16' => 3, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 5, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['Thunderbolt 4 x2', 'USB4 Type-C 40Gbps x1', 'USB 3.2 Gen2 Type-A x4', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => true, 'audio_chipset' => 'Realtek ALC4082', 'lan_chipset' => 'Marvell AQtion AQC113CS', 'lan_velocidad_gbps' => 10.0]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'MSI MAG X870 Tomahawk WiFi', 'marca' => 'MSI', 'fabricante' => 'MSI', 'modelo' => 'MAG X870 Tomahawk WiFi', 'descripcion' => 'Placa base ATX AM5 X870 con Wi-Fi 7, USB4 y 2.5G LAN. La evolución del Tomahawk con chipset X870, USB obligatorio y mayor ancho de banda.', 'imagen_url' => 'https://hyperpc.ae/images/catalog/hardware/motherboards/am5/msi/mag-x870-tomahawk-wifi/msi-mag-x870-tomahawk-wifi.jpg'],
            pb: ['socket' => 'AM5', 'chipset' => 'X870', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 256, 'frecuencia_memoria_max_mhz' => 8400, 'slots_pcie_x16' => 2, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 4, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB4 Type-C 40Gbps x2', 'USB 3.2 Gen2 Type-A x4', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC4080', 'lan_chipset' => 'Realtek RTL8125BG', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'Gigabyte X870E Aorus Master', 'marca' => 'Gigabyte', 'fabricante' => 'Gigabyte', 'modelo' => 'X870E Aorus Master', 'descripcion' => 'Placa base ATX AM5 X870E flagship de Gigabyte con Wi-Fi 7, USB4 80Gbps y PCIe 5.0. La placa definitiva para Ryzen 9950X3D en 2025.', 'imagen_url' => 'https://www.profesionalreview.com/wp-content/uploads/2024/08/X870E-AORUS-Master_3.jpg'],
            pb: ['socket' => 'AM5', 'chipset' => 'X870E', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 256, 'frecuencia_memoria_max_mhz' => 9200, 'slots_pcie_x16' => 3, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 5, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB4 80Gbps Type-C x1', 'USB4 40Gbps Type-C x1', 'USB 3.2 Gen2 Type-A x4', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC1220-VB', 'lan_chipset' => 'Intel I226-V', 'lan_velocidad_gbps' => 2.5]
        );
    }
    // ── LGA1700 ── 13 placas ──────────────────────────────────────────────────
    protected function seedPBLGA1700(): void
    {
        $this->crearPlacaBase(
            comp: ['nombre' => 'ASUS ROG Maximus Z690 Hero', 'marca' => 'ASUS', 'fabricante' => 'ASUS', 'modelo' => 'ROG Maximus Z690 Hero', 'descripcion' => 'Placa base ATX LGA1700 Z690 de máxima gama con VRM de 20 fases, Thunderbolt 4, Wi-Fi 6E, 10G LAN y DDR5. Para los i9-12900K y i9-13900K más exigentes.', 'imagen_url' => 'https://tpucdn.com/review/asus-rog-maximus-z690-hero/images/title.jpg'],
            pb: ['socket' => 'LGA1700', 'chipset' => 'Z690', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 192, 'frecuencia_memoria_max_mhz' => 6400, 'slots_pcie_x16' => 3, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 6, 'puertos_sata' => 6, 'puertos_usb_traseros' => ['Thunderbolt 4 x2', 'USB 3.2 Gen2x2 Type-C x1', 'USB 3.2 Gen2 Type-A x4', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => true, 'audio_chipset' => 'Realtek ALC4082', 'lan_chipset' => 'Marvell AQtion AQC113CS', 'lan_velocidad_gbps' => 10.0]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'Gigabyte Z690 Aorus Pro DDR4', 'marca' => 'Gigabyte', 'fabricante' => 'Gigabyte', 'modelo' => 'Z690 Aorus Pro DDR4', 'descripcion' => 'Placa base ATX LGA1700 Z690 con DDR4, Wi-Fi 6E y 2.5G LAN. Permite aprovechar la RAM DDR4 existente en la nueva plataforma Intel 12ª gen con VRM de 16+1+2 fases.', 'imagen_url' => 'https://alolaptop.com.vn/wp-content/uploads/2023/10/s-l1200.jpg'],
            pb: ['socket' => 'LGA1700', 'chipset' => 'Z690', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR4', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 128, 'frecuencia_memoria_max_mhz' => 5333, 'slots_pcie_x16' => 3, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 4, 'puertos_sata' => 6, 'puertos_usb_traseros' => ['USB 3.2 Gen2 Type-A x3', 'USB 3.2 Gen2 Type-C x1', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC1220-VB', 'lan_chipset' => 'Intel I225-V', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'ASUS ROG Strix Z790-E Gaming WiFi II', 'marca' => 'ASUS', 'fabricante' => 'ASUS', 'modelo' => 'ROG Strix Z790-E Gaming WiFi II', 'descripcion' => 'Placa base ATX LGA1700 Z790 con Wi-Fi 7, DDR5 y VRM de 20+1 fases. Compatible con Intel 12ª, 13ª y 14ª gen. Múltiples puertos USB4 y PCIe 5.0 para M.2.', 'imagen_url' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRx4kGcM6jlLmciqZA0_L5Z90NSlzPaMLd4jQ&s'],
            pb: ['socket' => 'LGA1700', 'chipset' => 'Z790', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 192, 'frecuencia_memoria_max_mhz' => 7800, 'slots_pcie_x16' => 3, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 5, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB4 Type-C 40Gbps x2', 'USB 3.2 Gen2 Type-A x4', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC4082', 'lan_chipset' => 'Intel I226-V', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'MSI MAG Z790 Tomahawk WiFi DDR4', 'marca' => 'MSI', 'fabricante' => 'MSI', 'modelo' => 'MAG Z790 Tomahawk WiFi DDR4', 'descripcion' => 'Placa base ATX LGA1700 Z790 con DDR4, Wi-Fi 6E y 2.5G LAN. Permite usar RAM DDR4 en la plataforma 13ª/14ª gen ahorrando en memoria con un VRM de 16+1+1 fases.', 'imagen_url' => 'https://media.ldlc.com/bo/images/fiches/carte-mere/MSI/z690/msi_mag_z690_tomahawk_wifi%281%29.jpg'],
            pb: ['socket' => 'LGA1700', 'chipset' => 'Z790', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR4', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 192, 'frecuencia_memoria_max_mhz' => 6000, 'slots_pcie_x16' => 3, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 5, 'puertos_sata' => 6, 'puertos_usb_traseros' => ['USB 3.2 Gen2x2 Type-C x1', 'USB 3.2 Gen2 Type-A x4', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC4080', 'lan_chipset' => 'Realtek RTL8125BG', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'Gigabyte Z790 Aorus Elite AX', 'marca' => 'Gigabyte', 'fabricante' => 'Gigabyte', 'modelo' => 'Z790 Aorus Elite AX', 'descripcion' => 'Placa base ATX LGA1700 Z790 DDR5 con Wi-Fi 6E, 2.5G LAN y USB 3.2 Gen2x2. VRM de 16+1+2 fases para i5/i7/i9 13ª y 14ª gen con buena relación calidad-precio.', 'imagen_url' => 'https://www.gigabyte.com/FileUpload/Global/KeyFeature/2181/innergigabyteimages/AESTHETICS.jpg'],
            pb: ['socket' => 'LGA1700', 'chipset' => 'Z790', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 192, 'frecuencia_memoria_max_mhz' => 7600, 'slots_pcie_x16' => 3, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 5, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB 3.2 Gen2x2 Type-C x1', 'USB 3.2 Gen2 Type-A x3', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC1220-VB', 'lan_chipset' => 'Intel I226-V', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'ASRock B760M Pro RS', 'marca' => 'ASRock', 'fabricante' => 'ASRock', 'modelo' => 'B760M Pro RS WiFi', 'descripcion' => 'Placa base Micro-ATX LGA1700 B760, DDR5 y 2.5G LAN. Compatible con i5-14400F e i7-14700F con buena conectividad en formato compacto.', 'imagen_url' => 'https://www.asrock.com/mb/features/POLYRGBLED-B760M%20Pro%20RSD4%20WiFi_mobile.png'],
            pb: ['socket' => 'LGA1700', 'chipset' => 'B760', 'factor_forma' => 'Micro-ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 4.0', 'slots_memoria' => 4, 'memoria_max_gb' => 192, 'frecuencia_memoria_max_mhz' => 7200, 'slots_pcie_x16' => 2, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 3, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB 3.2 Gen2 Type-A x2', 'USB 3.2 Gen1 Type-A x2', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8-pin', 'wifi' => false, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC897', 'lan_chipset' => 'Realtek RTL8125BG', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'ASUS ROG Strix Z690-I Gaming WiFi', 'marca' => 'ASUS', 'fabricante' => 'ASUS', 'modelo' => 'ROG Strix Z690-I Gaming WiFi', 'descripcion' => 'Placa base Mini-ITX LGA1700 Z690 con Wi-Fi 6E, DDR5 y Thunderbolt 4. La mejor ITX para i9-12900K/13900K en builds SFF de alta gama.', 'imagen_url' => 'https://www.neobyte.es/58534-large_default/asus-rog-strix-z690-i-gaming-wifi-placa-base-1700-mini-itx.jpg'],
            pb: ['socket' => 'LGA1700', 'chipset' => 'Z690', 'factor_forma' => 'Mini-ITX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 2, 'memoria_max_gb' => 64, 'frecuencia_memoria_max_mhz' => 6400, 'slots_pcie_x16' => 1, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 0, 'slots_m2' => 2, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['Thunderbolt 4 x2', 'USB 3.2 Gen2 Type-A x2', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => true, 'audio_chipset' => 'Realtek ALC4082', 'lan_chipset' => 'Intel I225-V', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'MSI MPG Z790I Edge WiFi', 'marca' => 'MSI', 'fabricante' => 'MSI', 'modelo' => 'MPG Z790I Edge WiFi', 'descripcion' => 'Placa base Mini-ITX LGA1700 Z790 con Wi-Fi 6E, DDR5 y USB4. Compatible con i5/i7/i9 12ª-14ª gen en un diseño compacto de 14+2 fases VRM.', 'imagen_url' => 'https://megaobzor.com/uploads/stories/188075/tpgf.webp'],
            pb: ['socket' => 'LGA1700', 'chipset' => 'Z790', 'factor_forma' => 'Mini-ITX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 2, 'memoria_max_gb' => 96, 'frecuencia_memoria_max_mhz' => 7200, 'slots_pcie_x16' => 1, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 0, 'slots_m2' => 2, 'puertos_sata' => 2, 'puertos_usb_traseros' => ['USB4 Type-C 40Gbps x1', 'USB 3.2 Gen2 Type-A x2', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC4080', 'lan_chipset' => 'Intel I226-V', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'ASUS TUF Gaming Z790-Plus WiFi', 'marca' => 'ASUS', 'fabricante' => 'ASUS', 'modelo' => 'TUF Gaming Z790-Plus WiFi', 'descripcion' => 'Placa base ATX LGA1700 Z790 DDR5 con Wi-Fi 6E, 2.5G LAN y VRM de 16+1 fases. Equilibrio entre precio y prestaciones para i5-14600K e i7-14700K.', 'imagen_url' => 'https://hyperpc.ae/images/catalog/hardware/motherboards/1700/asus/tuf-z790-plus-gaming/asus-tuf-gaming-z790-plus-wifi.jpg'],
            pb: ['socket' => 'LGA1700', 'chipset' => 'Z790', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 192, 'frecuencia_memoria_max_mhz' => 7600, 'slots_pcie_x16' => 3, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 4, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB 3.2 Gen2x2 Type-C x1', 'USB 3.2 Gen2 Type-A x3', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC4082', 'lan_chipset' => 'Intel I226-V', 'lan_velocidad_gbps' => 2.5]
        );
    }
    // ── LGA1851 ── 13 placas ──────────────────────────────────────────────────
    protected function seedPBLGA1851(): void
    {
        $this->crearPlacaBase(
            comp: ['nombre' => 'ASUS ROG Maximus Z890 Apex', 'marca' => 'ASUS', 'fabricante' => 'ASUS', 'modelo' => 'ROG Maximus Z890 Apex', 'descripcion' => 'Placa base ATX LGA1851 Z890 de overclocking extremo con VRM de 24+1 fases, Wi-Fi 7, Thunderbolt 4 y DDR5. La máxima expresión para Core Ultra 9 285K.', 'imagen_url' => 'https://hyperpc.ae/images/catalog/hardware/motherboards/1851/asus/rog-maximus-z890-apex/asus-rog-maximus-z890-apex.jpg'],
            pb: ['socket' => 'LGA1851', 'chipset' => 'Z890', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 256, 'frecuencia_memoria_max_mhz' => 9200, 'slots_pcie_x16' => 3, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 6, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['Thunderbolt 4 x2', 'USB4 Type-C 40Gbps x2', 'USB 3.2 Gen2 Type-A x4', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => true, 'audio_chipset' => 'Realtek ALC4082', 'lan_chipset' => 'Marvell AQtion AQC113CS', 'lan_velocidad_gbps' => 10.0]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'MSI MEG Z890 Ace', 'marca' => 'MSI', 'fabricante' => 'MSI', 'modelo' => 'MEG Z890 Ace', 'descripcion' => 'Placa base ATX LGA1851 Z890 flagship de MSI con VRM de 25+1+2 fases, Wi-Fi 7, USB4 80Gbps y 10G LAN. La referencia MSI para Arrow Lake de gama alta.', 'imagen_url' => 'https://external-preview.redd.it/msi-z890-motherboards-leak-out-ahead-of-launch-first-look-v0-jFZmCcW-fBIBrAYAcOXvIx6wpfGGe2xne2gM-NkKxBg.jpg?auto=webp&s=34ae47091c6e1fd73b4a45108a691eb1d7e5f7dd'],
            pb: ['socket' => 'LGA1851', 'chipset' => 'Z890', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 256, 'frecuencia_memoria_max_mhz' => 9200, 'slots_pcie_x16' => 3, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 6, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB4 80Gbps Type-C x1', 'USB4 40Gbps Type-C x1', 'USB 3.2 Gen2 Type-A x4', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC4082', 'lan_chipset' => 'Marvell AQtion AQC113CS', 'lan_velocidad_gbps' => 10.0]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'Gigabyte Z890 Aorus Master', 'marca' => 'Gigabyte', 'fabricante' => 'Gigabyte', 'modelo' => 'Z890 Aorus Master', 'descripcion' => 'Placa base ATX LGA1851 Z890 con VRM de 20+1+2 fases, Wi-Fi 7 y USB4. El flagship de Gigabyte para la plataforma Arrow Lake con diseño Aorus característico.', 'imagen_url' => 'https://thumb.pccomponentes.com/w-530-530/articles/1086/10864541/1361-gigabyte-z890-aorus-master.jpg'],
            pb: ['socket' => 'LGA1851', 'chipset' => 'Z890', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 256, 'frecuencia_memoria_max_mhz' => 9200, 'slots_pcie_x16' => 3, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 5, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB4 40Gbps Type-C x2', 'USB 3.2 Gen2 Type-A x4', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC1220-VB', 'lan_chipset' => 'Intel I226-V', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'ASUS TUF Gaming Z890-Plus WiFi', 'marca' => 'ASUS', 'fabricante' => 'ASUS', 'modelo' => 'TUF Gaming Z890-Plus WiFi', 'descripcion' => 'Placa base ATX LGA1851 Z890 mid-range con Wi-Fi 7, 2.5G LAN y VRM de 16+1+2 fases. La opción equilibrada para Core Ultra 5 245K y Core Ultra 7 265K.', 'imagen_url' => 'https://i0.wp.com/www.yalmangaming.com/wp-content/uploads/2025/02/6.TUF-GAMING-Z890-PLUS-WIFI-30-1.webp?fit=1080%2C1080&ssl=1'],
            pb: ['socket' => 'LGA1851', 'chipset' => 'Z890', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 256, 'frecuencia_memoria_max_mhz' => 8800, 'slots_pcie_x16' => 3, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 4, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB4 40Gbps Type-C x1', 'USB 3.2 Gen2 Type-A x4', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC4082', 'lan_chipset' => 'Intel I226-V', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'MSI MAG Z890 Tomahawk WiFi', 'marca' => 'MSI', 'fabricante' => 'MSI', 'modelo' => 'MAG Z890 Tomahawk WiFi', 'descripcion' => 'Placa base ATX LGA1851 Z890 con Wi-Fi 7, USB4 y 2.5G LAN. El Tomahawk de la plataforma Arrow Lake, con 16+2 fases VRM y cuatro ranuras M.2.', 'imagen_url' => 'https://storage-asset.msi.com/global/picture/article/article_17424537385e644fa1eb1fbe3c9fdce2d8bc3cd234.jpeg'],
            pb: ['socket' => 'LGA1851', 'chipset' => 'Z890', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 256, 'frecuencia_memoria_max_mhz' => 8800, 'slots_pcie_x16' => 3, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 4, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB4 40Gbps Type-C x1', 'USB 3.2 Gen2 Type-A x4', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC4080', 'lan_chipset' => 'Realtek RTL8125BG', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'ASRock Z890 Taichi', 'marca' => 'ASRock', 'fabricante' => 'ASRock', 'modelo' => 'Z890 Taichi', 'descripcion' => 'Placa base ATX LGA1851 Z890 con diseño Taichi característico, VRM de 20+2+1 fases, Wi-Fi 7, 10G LAN y Thunderbolt 4. La opción ASRock premium para Arrow Lake.', 'imagen_url' => 'https://cdn.thefpsreview.com/wp-content/uploads/2024/12/asrock_z890_taichi_banner.png.webp'],
            pb: ['socket' => 'LGA1851', 'chipset' => 'Z890', 'factor_forma' => 'ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 256, 'frecuencia_memoria_max_mhz' => 9200, 'slots_pcie_x16' => 3, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 5, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['Thunderbolt 4 x2', 'USB4 40Gbps Type-C x1', 'USB 3.2 Gen2 Type-A x4', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8+4-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => true, 'audio_chipset' => 'Realtek ALC4082', 'lan_chipset' => 'Marvell AQtion AQC113CS', 'lan_velocidad_gbps' => 10.0]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'MSI PRO B860M-A WiFi', 'marca' => 'MSI', 'fabricante' => 'MSI', 'modelo' => 'PRO B860M-A WiFi', 'descripcion' => 'Placa base Micro-ATX LGA1851 B860 con Wi-Fi 6E y 2.5G LAN. Opción asequible para Core Ultra 5 245K(F) en formato compacto con DDR5.', 'imagen_url' => 'https://www.alternate.es/p/1200x630/5/7/MSI_PRO_B860M_A_WIFI_placa_base_Intel_B860_LGA_1851__Socket_V1__micro_ATX@@100102375_30.jpg'],
            pb: ['socket' => 'LGA1851', 'chipset' => 'B860', 'factor_forma' => 'Micro-ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 192, 'frecuencia_memoria_max_mhz' => 7200, 'slots_pcie_x16' => 2, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 3, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB 3.2 Gen2 Type-A x2', 'USB 3.2 Gen1 Type-A x2', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC897', 'lan_chipset' => 'Realtek RTL8125BG', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'Gigabyte B860M Aorus Elite WiFi', 'marca' => 'Gigabyte', 'fabricante' => 'Gigabyte', 'modelo' => 'B860M Aorus Elite WiFi', 'descripcion' => 'Placa base Micro-ATX LGA1851 B860 con Wi-Fi 6E, 2.5G LAN y 14+2+2 fases VRM. Tres M.2 y diseño Aorus compacto para Core Ultra 7 265K.', 'imagen_url' => 'https://thumb.pccomponentes.com/w-530-530/articles/1086/10867354/1888-gigabyte-b860m-aorus-elite-wifi6e.jpg'],
            pb: ['socket' => 'LGA1851', 'chipset' => 'B860', 'factor_forma' => 'Micro-ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 192, 'frecuencia_memoria_max_mhz' => 8000, 'slots_pcie_x16' => 2, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 3, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB 3.2 Gen2 Type-A x2', 'USB 3.2 Gen2 Type-C x1', 'USB 3.2 Gen1 Type-A x2', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC1220', 'lan_chipset' => 'Realtek RTL8125BG', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'ASRock B860M Pro RS WiFi', 'marca' => 'ASRock', 'fabricante' => 'ASRock', 'modelo' => 'B860M Pro RS WiFi', 'descripcion' => 'Placa base Micro-ATX LGA1851 B860 de gama entrada con Wi-Fi 6E y 2.5G LAN. La opción más asequible en mATX para la plataforma Arrow Lake.', 'imagen_url' => 'https://www.tnc.com.vn/uploads/newp/b2025/mainboard-asrock-b860m-pro-rs-wifi-8940.webp'],
            pb: ['socket' => 'LGA1851', 'chipset' => 'B860', 'factor_forma' => 'Micro-ATX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 4, 'memoria_max_gb' => 192, 'frecuencia_memoria_max_mhz' => 7200, 'slots_pcie_x16' => 1, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 1, 'slots_m2' => 2, 'puertos_sata' => 4, 'puertos_usb_traseros' => ['USB 3.2 Gen2 Type-A x2', 'USB 3.2 Gen1 Type-A x2', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC897', 'lan_chipset' => 'Realtek RTL8125BG', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'MSI MPG Z890I Edge WiFi', 'marca' => 'MSI', 'fabricante' => 'MSI', 'modelo' => 'MPG Z890I Edge WiFi', 'descripcion' => 'Placa base Mini-ITX LGA1851 Z890 con Wi-Fi 7, USB4 y 2.5G LAN. Diseño compacto de 14+2 fases para Core Ultra 5/7 en SFF Arrow Lake.', 'imagen_url' => 'https://c1.neweggimages.com/BizIntell/item/MB/Motherboards%20-%20Intel/13-144-681/1b1.jpg'],
            pb: ['socket' => 'LGA1851', 'chipset' => 'Z890', 'factor_forma' => 'Mini-ITX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 2, 'memoria_max_gb' => 96, 'frecuencia_memoria_max_mhz' => 8800, 'slots_pcie_x16' => 1, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 0, 'slots_m2' => 2, 'puertos_sata' => 2, 'puertos_usb_traseros' => ['USB4 40Gbps Type-C x1', 'USB 3.2 Gen2 Type-A x2', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC4080', 'lan_chipset' => 'Realtek RTL8125BG', 'lan_velocidad_gbps' => 2.5]
        );
        $this->crearPlacaBase(
            comp: ['nombre' => 'Gigabyte Z890I Aorus Ultra WiFi7', 'marca' => 'Gigabyte', 'fabricante' => 'Gigabyte', 'modelo' => 'Z890I Aorus Ultra WiFi7', 'descripcion' => 'Placa base Mini-ITX LGA1851 Z890 con Wi-Fi 7, USB4 y diseño Aorus. VRM de 14+1+2 fases para la plataforma Arrow Lake en el factor de forma más compacto de Gigabyte.', 'imagen_url' => 'https://img.pccomponentes.com/articles/1088/10888364/1199-placa-base-gigabyte-z890i-aorus-ultra-lga-1851-mini-itx-thunderbolt-4-wi-fi-7-25gbe-ddr5.jpg'],
            pb: ['socket' => 'LGA1851', 'chipset' => 'Z890', 'factor_forma' => 'Mini-ITX', 'tipo_memoria' => 'DDR5', 'version_pcie' => 'PCIe 5.0', 'slots_memoria' => 2, 'memoria_max_gb' => 96, 'frecuencia_memoria_max_mhz' => 8800, 'slots_pcie_x16' => 1, 'slots_pcie_x4' => 0, 'slots_pcie_x1' => 0, 'slots_m2' => 2, 'puertos_sata' => 2, 'puertos_usb_traseros' => ['USB4 40Gbps Type-C x1', 'USB 3.2 Gen2 Type-A x2', 'USB 2.0 x2'], 'conector_atx' => '24-pin', 'conector_cpu' => '8-pin', 'wifi' => true, 'bluetooth' => true, 'thunderbolt' => false, 'audio_chipset' => 'Realtek ALC1220', 'lan_chipset' => 'Intel I226-V', 'lan_velocidad_gbps' => 2.5]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RAM  
    // ─────────────────────────────────────────────────────────────────────────

    protected function crearRAM(array $comp, array $ram): void
    {
        $marcaId = $this->marcas[$comp['marca']] ?? null;
        $fabId   = $this->marcas[$comp['fabricante']] ?? $marcaId;
        $componente = \App\Models\Componentes\Componente::create(['nombre' => $comp['nombre'], 'marca_id' => $marcaId, 'fabricante_id' => $fabId, 'categoria' => 'ram', 'modelo' => $comp['modelo'], 'imagen_url' => $comp['imagen_url'] ?? null, 'descripcion' => $comp['descripcion'] ?? null, 'activo' => true]);
        \App\Models\Componentes\RAM::create(['componente_id' => $componente->id, 'tipo_memoria_id' => $this->tiposRam[$ram['tipo_memoria']] ?? null, 'capacidad_gb' => $ram['capacidad_gb'], 'modulos' => $ram['modulos'], 'capacidad_total_gb' => $ram['capacidad_total_gb'], 'velocidad_mhz' => $ram['velocidad_mhz'], 'latencia_cas' => $ram['latencia_cas'], 'voltaje' => $ram['voltaje'], 'factor_forma' => $ram['factor_forma'], 'altura_mm' => $ram['altura_mm'], 'tiene_rgb' => $ram['tiene_rgb'], 'ecc' => $ram['ecc'], 'xmp' => $ram['xmp'], 'expo' => $ram['expo']]);
    }

    public function seedRAMs(): void
    {
        $this->cargarAuxiliares();
        $this->seedRAMsDDR4();
        $this->seedRAMsDDR5();
    }

    protected function seedRAMsDDR4(): void
    {
        $this->crearRAM(
            comp: ['nombre' => 'Corsair Vengeance LPX 16GB DDR4-3200 CL16', 'marca' => 'Corsair', 'fabricante' => 'Corsair', 'modelo' => 'CMK16GX4M2E3200C16', 'descripcion' => 'Kit 2×8 GB DDR4-3200 CL16 de perfil bajo (31 mm). Compatible con la gran mayoría de sistemas y coolers de torre. Sin RGB, máxima compatibilidad.', 'imagen_url' => 'https://img.pccomponentes.com/articles/26/262822/corsair-vengeance-lpx-ddr4-3200-pc4-25600-16gb-2x8gb-cl16-negro.jpg'],
            ram: ['tipo_memoria' => 'DDR4', 'capacidad_gb' => 8, 'modulos' => 2, 'capacidad_total_gb' => 16, 'velocidad_mhz' => 3200, 'latencia_cas' => 'CL16-18-18-36', 'voltaje' => 1.35, 'factor_forma' => 'DIMM', 'altura_mm' => 31, 'tiene_rgb' => false, 'ecc' => false, 'xmp' => true, 'expo' => false]
        );
        $this->crearRAM(
            comp: ['nombre' => 'Corsair Vengeance LPX 128GB DDR4-3200 CL16', 'marca' => 'Corsair', 'fabricante' => 'Corsair', 'modelo' => 'CMK32GX4M2E3200C16', 'descripcion' => 'Kit 4×32 GB DDR4-3200 CL16 de perfil bajo. El estándar para builds de creadores y gaming de alta gama en AM4 y LGA1700.', 'imagen_url' => 'https://m.media-amazon.com/images/I/51CAWQYmm8S.jpg'],
            ram: ['tipo_memoria' => 'DDR4', 'capacidad_gb' => 32, 'modulos' => 4, 'capacidad_total_gb' => 128, 'velocidad_mhz' => 3200, 'latencia_cas' => 'CL16-18-18-36', 'voltaje' => 1.35, 'factor_forma' => 'DIMM', 'altura_mm' => 31, 'tiene_rgb' => false, 'ecc' => false, 'xmp' => true, 'expo' => false]
        );
        $this->crearRAM(
            comp: ['nombre' => 'G.Skill Trident Z RGB 16GB DDR4-3600 CL16', 'marca' => 'G.Skill', 'fabricante' => 'G.Skill', 'modelo' => 'F4-3600C16D-16GTZRC', 'descripcion' => 'Kit 2×8 GB DDR4-3600 CL16 con iluminación RGB en la barra superior. Referencia para gaming en AM4 con overclock a frecuencias altas. Altura 44 mm.', 'imagen_url' => 'https://m.media-amazon.com/images/I/51lIfhQ8SeL.jpg'],
            ram: ['tipo_memoria' => 'DDR4', 'capacidad_gb' => 8, 'modulos' => 2, 'capacidad_total_gb' => 16, 'velocidad_mhz' => 3600, 'latencia_cas' => 'CL16-19-19-39', 'voltaje' => 1.35, 'factor_forma' => 'DIMM', 'altura_mm' => 44, 'tiene_rgb' => true, 'ecc' => false, 'xmp' => true, 'expo' => false]
        );
        $this->crearRAM(
            comp: ['nombre' => 'G.Skill Trident Z RGB 32GB DDR4-3600 CL16', 'marca' => 'G.Skill', 'fabricante' => 'G.Skill', 'modelo' => 'F4-3600C16D-32GTZRC', 'descripcion' => 'Kit 2×16 GB DDR4-3600 CL16 RGB. Excelente relación latencia-frecuencia para Ryzen 5000 y builds de producción de contenido.', 'imagen_url' => 'https://img-cdn.heureka.group/v1/f7a2eb1a-4398-5ab6-9a59-e6bc4aae1f0d.jpg?width=400&height=400'],
            ram: ['tipo_memoria' => 'DDR4', 'capacidad_gb' => 16, 'modulos' => 2, 'capacidad_total_gb' => 32, 'velocidad_mhz' => 3600, 'latencia_cas' => 'CL16-19-19-39', 'voltaje' => 1.35, 'factor_forma' => 'DIMM', 'altura_mm' => 44, 'tiene_rgb' => true, 'ecc' => false, 'xmp' => true, 'expo' => false]
        );
        $this->crearRAM(
            comp: ['nombre' => 'Kingston Fury Beast 16GB DDR4-3200 CL16', 'marca' => 'Kingston', 'fabricante' => 'Kingston', 'modelo' => 'KF432C16BBK2/16', 'descripcion' => 'Kit 2×8 GB DDR4-3200 CL16 sin RGB. Disipador bajo perfil con acabado negro mate. Buena alternativa económica al Vengeance LPX con XMP 2.0.', 'https://img.pccomponentes.com/articles/43/432664/1392-kingston-fury-beast-ddr4-3200-mhz-16gb-2x8gb-cl16.jpg'],
            ram: ['tipo_memoria' => 'DDR4', 'capacidad_gb' => 8, 'modulos' => 2, 'capacidad_total_gb' => 16, 'velocidad_mhz' => 3200, 'latencia_cas' => 'CL16-18-18-36', 'voltaje' => 1.35, 'factor_forma' => 'DIMM', 'altura_mm' => 34, 'tiene_rgb' => false, 'ecc' => false, 'xmp' => true, 'expo' => false]
        );
        $this->crearRAM(
            comp: ['nombre' => 'Kingston Fury Beast RGB 32GB DDR4-3600 CL18', 'marca' => 'Kingston', 'fabricante' => 'Kingston', 'modelo' => 'KF436C18BBAK2/32', 'descripcion' => 'Kit 2×16 GB DDR4-3600 CL18 con RGB en la tira superior. Compatible con XMP 2.0 e Intel XMP. Buen equilibrio precio-rendimiento para gaming y multitarea.', 'imagen_url' => 'https://www.neobyte.es/107071-large_default/kingston-fury-beast-rgb-32gb-2x16gb-ddr4-3600mhz-cl18-memoria-ram.jpg'],
            ram: ['tipo_memoria' => 'DDR4', 'capacidad_gb' => 16, 'modulos' => 2, 'capacidad_total_gb' => 32, 'velocidad_mhz' => 3600, 'latencia_cas' => 'CL18-22-22-42', 'voltaje' => 1.35, 'factor_forma' => 'DIMM', 'altura_mm' => 42, 'tiene_rgb' => true, 'ecc' => false, 'xmp' => true, 'expo' => false]
        );
        $this->crearRAM(
            comp: ['nombre' => 'TeamGroup T-Force Vulcan Z 16GB DDR4-3200 CL16', 'marca' => 'TeamGroup', 'fabricante' => 'TeamGroup', 'modelo' => 'TLZGD416G3200HC16FDC01', 'descripcion' => 'Kit 2×8 GB DDR4-3200 CL16 sin RGB con disipador plano gris antracita. La opción de precio mínimo que aún cumple con XMP 2.0.', 'imagen_url' => 'https://images.teamgroupinc.com/products/memory/u-dimm/ddr4/vulcan-z/intro/01.jpg'],
            ram: ['tipo_memoria' => 'DDR4', 'capacidad_gb' => 8, 'modulos' => 2, 'capacidad_total_gb' => 16, 'velocidad_mhz' => 3200, 'latencia_cas' => 'CL16-18-18-38', 'voltaje' => 1.35, 'factor_forma' => 'DIMM', 'altura_mm' => 33, 'tiene_rgb' => false, 'ecc' => false, 'xmp' => true, 'expo' => false]
        );
    }

    protected function seedRAMsDDR5(): void
    {
        $this->crearRAM(
            comp: ['nombre' => 'G.Skill Trident Z5 RGB 32GB DDR5-6000 CL30', 'marca' => 'G.Skill', 'fabricante' => 'G.Skill', 'modelo' => 'F5-6000J3040G16GX2-TZ5RK', 'descripcion' => 'Kit 2×16 GB DDR5-6000 CL30 con RGB en barra superior. DDR5-6000 es el punto óptimo de rendimiento en AM5 (Ryzen 7000/9000). Uno de los kits más populares en 2023-2024.', 'imagen_url' => 'https://m.media-amazon.com/images/I/71SYm-BUrAL.jpg'],
            ram: ['tipo_memoria' => 'DDR5', 'capacidad_gb' => 16, 'modulos' => 2, 'capacidad_total_gb' => 32, 'velocidad_mhz' => 6000, 'latencia_cas' => 'CL30-38-38-96', 'voltaje' => 1.35, 'factor_forma' => 'DIMM', 'altura_mm' => 44, 'tiene_rgb' => true, 'ecc' => true, 'xmp' => true, 'expo' => true]
        );
        $this->crearRAM(
            comp: ['nombre' => 'G.Skill Trident Z5 RGB 64GB DDR5-6000 CL30', 'marca' => 'G.Skill', 'fabricante' => 'G.Skill', 'modelo' => 'F5-6000J3040G32GX2-TZ5RK', 'descripcion' => 'Kit 2×32 GB DDR5-6000 CL30 RGB. 64 GB en solo dos módulos a frecuencia óptima para AM5. Precios elevados por tensión en el mercado de DRAM de alta capacidad.', 'imagen_url' => 'https://img.pccasegear.com/images/F5-6000J3040G32GX2-TZ5RK-ftr3.jpg'],
            ram: ['tipo_memoria' => 'DDR5', 'capacidad_gb' => 32, 'modulos' => 2, 'capacidad_total_gb' => 64, 'velocidad_mhz' => 6000, 'latencia_cas' => 'CL30-38-38-96', 'voltaje' => 1.35, 'factor_forma' => 'DIMM', 'altura_mm' => 44, 'tiene_rgb' => true, 'ecc' => true, 'xmp' => true, 'expo' => true]
        );
        $this->crearRAM(
            comp: ['nombre' => 'Kingston Fury Beast DDR5 32GB 5200 CL40', 'marca' => 'Kingston', 'fabricante' => 'Kingston', 'modelo' => 'KF552C40BBK2-32', 'descripcion' => 'Kit 2×16 GB DDR5-5200 CL40 sin RGB. La opción más asequible de Kingston en DDR5, con XMP 3.0 y EXPO. Perfil bajo 35 mm compatible con grandes coolers.', 'imagen_url' => 'https://innovainformatica.com/images-amigables/1012/modulo-kinston-ddr5-32gb-5200mhz-cl40-kf552c40bb-32-grande-7.jpg'],
            ram: ['tipo_memoria' => 'DDR5', 'capacidad_gb' => 16, 'modulos' => 2, 'capacidad_total_gb' => 32, 'velocidad_mhz' => 5200, 'latencia_cas' => 'CL40-39-39-80', 'voltaje' => 1.25, 'factor_forma' => 'DIMM', 'altura_mm' => 35, 'tiene_rgb' => false, 'ecc' => true, 'xmp' => true, 'expo' => true]
        );
        $this->crearRAM(
            comp: ['nombre' => 'Crucial Pro DDR5 64GB 5600 CL46', 'marca' => 'Crucial', 'fabricante' => 'Crucial', 'modelo' => 'CP2K32G56C46U5', 'descripcion' => 'Kit 2×32 GB DDR5-5600 CL46 sin RGB. El kit de 64 GB más asequible del mercado en su rango. Precio muy afectado por la escasez de DRAM DDR5 de alta densidad derivada de la demanda IA.', 'imagen_url' => 'https://cdn.idealo.com/folder/Product/203263/2/203263217/s11_produktbild_gross_2/crucial-pro-64gb-kit-ddr5-5600-cl46-cp2k32g56c46u5.jpg'],
            ram: ['tipo_memoria' => 'DDR5', 'capacidad_gb' => 32, 'modulos' => 2, 'capacidad_total_gb' => 64, 'velocidad_mhz' => 5600, 'latencia_cas' => 'CL46-45-45-90', 'voltaje' => 1.10, 'factor_forma' => 'DIMM', 'altura_mm' => 33, 'tiene_rgb' => false, 'ecc' => true, 'xmp' => true, 'expo' => true]
        );
        $this->crearRAM(
            comp: ['nombre' => 'TeamGroup T-Force Delta RGB DDR5 32GB 6000 CL30', 'marca' => 'TeamGroup', 'fabricante' => 'TeamGroup', 'modelo' => 'FF3D532G6000HC30ADC01', 'descripcion' => 'Kit 2×16 GB DDR5-6000 CL30 con RGB de tira completa y difusor translúcido. Compatible con EXPO y XMP 3.0. Buen precio en el segmento 6000 MHz sin sacrificar estética.', 'imagen_url' => 'https://images.teamgroupinc.com/products/memory/u-dimm/ddr5/delta-rgb/intro/01.jpg'],
            ram: ['tipo_memoria' => 'DDR5', 'capacidad_gb' => 16, 'modulos' => 2, 'capacidad_total_gb' => 32, 'velocidad_mhz' => 6000, 'latencia_cas' => 'CL30-40-40-96', 'voltaje' => 1.35, 'factor_forma' => 'DIMM', 'altura_mm' => 43, 'tiene_rgb' => true, 'ecc' => true, 'xmp' => true, 'expo' => true]
        );
        $this->crearRAM(
            comp: ['nombre' => 'XPG Lancer RGB DDR5 32GB 6000 CL30', 'marca' => 'XPG', 'fabricante' => 'XPG', 'modelo' => 'AX5U6000C3016G-DCLARBK', 'descripcion' => 'Kit 2×16 GB DDR5-6000 CL30 RGB. Latencias muy ajustadas para DDR5-6000. Compatible con XMP 3.0 y EXPO. Diseño con disipador negro y tira RGB lateral.', 'imagen_url' => 'https://img.pccomponentes.com/articles/1080/10805490/2953-adata-xpg-lancer-rgb-ddr5-6000mhz-64gb-2x32gb-cl30-comprar.jpg'],
            ram: ['tipo_memoria' => 'DDR5', 'capacidad_gb' => 16, 'modulos' => 2, 'capacidad_total_gb' => 32, 'velocidad_mhz' => 6000, 'latencia_cas' => 'CL30-40-40-96', 'voltaje' => 1.35, 'factor_forma' => 'DIMM', 'altura_mm' => 44, 'tiene_rgb' => true, 'ecc' => true, 'xmp' => true, 'expo' => true]
        );
        $this->crearRAM(
            comp: ['nombre' => 'G.Skill Trident Z5 Neo RGB 32GB DDR5-6000 CL30', 'marca' => 'G.Skill', 'fabricante' => 'G.Skill', 'modelo' => 'F5-6000J3040G16GX2-TZ5NR', 'descripcion' => 'Kit 2×16 GB DDR5-6000 CL30 RGB optimizado para AMD EXPO y Ryzen 7000/9000. La versión Neo del Trident Z5 está específicamente certificada y testada en placas AM5 X670E y B650.', 'imagen_url' => 'https://www.gskill.com/_upload/images/173088353518.jpg'],
            ram: ['tipo_memoria' => 'DDR5', 'capacidad_gb' => 16, 'modulos' => 2, 'capacidad_total_gb' => 32, 'velocidad_mhz' => 6000, 'latencia_cas' => 'CL30-38-38-96', 'voltaje' => 1.35, 'factor_forma' => 'DIMM', 'altura_mm' => 44, 'tiene_rgb' => true, 'ecc' => true, 'xmp' => false, 'expo' => true]
        );
        $this->crearRAM(
            comp: ['nombre' => 'Kingston Fury Beast DDR5 64GB 5200 CL40', 'marca' => 'Kingston', 'fabricante' => 'Kingston', 'modelo' => 'KF552C40BBK2-64', 'descripcion' => 'Kit 2×32 GB DDR5-5200 CL40 sin RGB. 64 GB accesibles en una sola plataforma. El precio ha aumentado notablemente desde 2024 por el apretón en la cadena de suministro de DRAM de alta densidad.', 'imagen_url' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRUXQKSW02zQ1fzR1H2u1CK7x1amVrWxYtHzw&s'],
            ram: ['tipo_memoria' => 'DDR5', 'capacidad_gb' => 32, 'modulos' => 2, 'capacidad_total_gb' => 64, 'velocidad_mhz' => 5200, 'latencia_cas' => 'CL40-39-39-80', 'voltaje' => 1.25, 'factor_forma' => 'DIMM', 'altura_mm' => 35, 'tiene_rgb' => false, 'ecc' => true, 'xmp' => true, 'expo' => true]
        );
    }

    // ═════════════════════════════════════════════════════════════════════════════
    //  GPU
    // ═════════════════════════════════════════════════════════════════════════════
    protected function seedGPUs(): void
    {
        // ── NVIDIA GPU ──────────────────────────────────────────
        $this->crearGPU(
            comp: ['nombre' => 'MSI GeForce RTX 3060 VENTUS 2X 12GB OC', 'marca' => 'MSI', 'fabricante' => 'NVIDIA', 'modelo' => 'RTX 3060 VENTUS 2X 12G OC', 'descripcion' => 'GPU NVIDIA Ampere RTX 3060 con 12 GB GDDR6 en bus 192-bit. Diseño doble ventilador compacto de MSI. Excelente relación precio/rendimiento para 1080p/1440p.', 'imagen_url' => 'https://storage-asset.msi.com/global/picture/image/feature/vga/ventus/3070-ventus-2x/kv-xs.jpg'],
            gpu: ['arquitectura' => 'Ampere', 'tipo_vram' => 'GDDR6', 'version_pcie' => 'PCIe 4.0', 'vram_gb' => 12, 'bus_bits' => 192, 'frecuencia_base_mhz' => 1320, 'frecuencia_boost_mhz' => 1807, 'tdp_watts' => 170, 'slots_pcie' => 2.0, 'longitud_mm' => 235, 'conectores_alimentacion' => ['1x 8-pin'], 'psu_minima_watts' => 550, 'salidas_video' => ['3x DisplayPort 1.4a', '1x HDMI 2.1'], 'ray_tracing' => true, 'dlss' => true, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'ASUS Dual GeForce RTX 3060 Ti OC 8GB', 'marca' => 'ASUS', 'fabricante' => 'NVIDIA', 'modelo' => 'DUAL-RTX3060TI-O8G', 'descripcion' => 'RTX 3060 Ti con 8 GB GDDR6, cooler Dual de ASUS con dos ventiladores Axial-tech. Gran rendimiento 1440p a precio contenido.', 'imagen_url' => 'https://dlcdnwebimgs.asus.com/files/media/96ba3374-6e5b-4cf1-b04a-577322d9a8b6/img/kv/product-kv_s.jpg'],
            gpu: ['arquitectura' => 'Ampere', 'tipo_vram' => 'GDDR6', 'version_pcie' => 'PCIe 4.0', 'vram_gb' => 8, 'bus_bits' => 256, 'frecuencia_base_mhz' => 1410, 'frecuencia_boost_mhz' => 1695, 'tdp_watts' => 200, 'slots_pcie' => 2.7, 'longitud_mm' => 267, 'conectores_alimentacion' => ['2x 8-pin'], 'psu_minima_watts' => 600, 'salidas_video' => ['2x DisplayPort 1.4a', '2x HDMI 2.1'], 'ray_tracing' => true, 'dlss' => true, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'Gigabyte GeForce RTX 3070 EAGLE OC 8GB', 'marca' => 'Gigabyte', 'fabricante' => 'NVIDIA', 'modelo' => 'GV-N3070EAGLE OC-8GD', 'descripcion' => 'RTX 3070 8 GB GDDR6 con cooler WINDFORCE 3X de Gigabyte. Referencia 1440p de la generación Ampere con excelente disipación térmica.', 'imagen_url' => 'https://thumb.pccomponentes.com/w-530-530/articles/45/451255/1284-gigabyte-geforce-rtx-3070-eagle-oc-8gb-gddr6-rev-20-4e8de156-6460-42be-9979-15247f1161dd.jpg'],
            gpu: ['arquitectura' => 'Ampere', 'tipo_vram' => 'GDDR6', 'version_pcie' => 'PCIe 4.0', 'vram_gb' => 8, 'bus_bits' => 256, 'frecuencia_base_mhz' => 1500, 'frecuencia_boost_mhz' => 1815, 'tdp_watts' => 220, 'slots_pcie' => 2.7, 'longitud_mm' => 285, 'conectores_alimentacion' => ['2x 8-pin'], 'psu_minima_watts' => 650, 'salidas_video' => ['3x DisplayPort 1.4a', '1x HDMI 2.1'], 'ray_tracing' => true, 'dlss' => true, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'Zotac Gaming GeForce RTX 3060 Twin Edge OC (SFF)', 'marca' => 'Zotac', 'fabricante' => 'NVIDIA', 'modelo' => 'ZT-A30600H-10M', 'descripcion' => 'RTX 3060 12 GB GDDR6 en formato compacto 228 mm ideal para builds SFF/mATX. Doble ventilador IceStorm 2.0. La opción más popular de Ampere para cajas pequeñas.', 'imagen_url' => 'https://m.media-amazon.com/images/I/61HZMYoLNoL.jpg'],
            gpu: ['arquitectura' => 'Ampere', 'tipo_vram' => 'GDDR6', 'version_pcie' => 'PCIe 4.0', 'vram_gb' => 12, 'bus_bits' => 192, 'frecuencia_base_mhz' => 1320, 'frecuencia_boost_mhz' => 1807, 'tdp_watts' => 170, 'slots_pcie' => 2.0, 'longitud_mm' => 228, 'conectores_alimentacion' => ['1x 8-pin'], 'psu_minima_watts' => 550, 'salidas_video' => ['3x DisplayPort 1.4a', '1x HDMI 2.1'], 'ray_tracing' => true, 'dlss' => true, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'MSI GeForce RTX 4060 VENTUS 2X BLACK OC 8GB', 'marca' => 'MSI', 'fabricante' => 'NVIDIA', 'modelo' => 'RTX 4060 VENTUS 2X BLACK 8G OC', 'descripcion' => 'RTX 4060 8 GB GDDR6 en bus 128-bit con DLSS 3 Frame Generation. Diseño compacto de 170 mm y TDP de solo 115 W. El punto de entrada de Ada Lovelace para 1080p.', 'imagen_url' => 'https://media.game.es/COVERV2/3D_L/V1I/V1IIHI.png'],
            gpu: ['arquitectura' => 'Ada Lovelace', 'tipo_vram' => 'GDDR6', 'version_pcie' => 'PCIe 4.0', 'vram_gb' => 8, 'bus_bits' => 128, 'frecuencia_base_mhz' => 1830, 'frecuencia_boost_mhz' => 2460, 'tdp_watts' => 115, 'slots_pcie' => 2.0, 'longitud_mm' => 232, 'conectores_alimentacion' => ['1x 8-pin'], 'psu_minima_watts' => 550, 'salidas_video' => ['3x DisplayPort 1.4a', '1x HDMI 2.1'], 'ray_tracing' => true, 'dlss' => true, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'Zotac Gaming GeForce RTX 4060 Twin Edge OC 8GB (SFF)', 'marca' => 'Zotac', 'fabricante' => 'NVIDIA', 'modelo' => 'ZT-D40600H-10M', 'descripcion' => 'RTX 4060 8 GB GDDR6 en diseño compacto de solo 200 mm. Perfecto para gabinetes SFF e ITX gracias a su bajo TDP de 115 W y perfil de 2 slots.', 'imagen_url' => 'https://hyperpc.ae/images/catalog/hardware/video-cards/zotac/4070/zotac-gaming-geforce-rtx-4070-twin-edge.jpg'],
            gpu: ['arquitectura' => 'Ada Lovelace', 'tipo_vram' => 'GDDR6', 'version_pcie' => 'PCIe 4.0', 'vram_gb' => 8, 'bus_bits' => 128, 'frecuencia_base_mhz' => 1830, 'frecuencia_boost_mhz' => 2475, 'tdp_watts' => 115, 'slots_pcie' => 2.0, 'longitud_mm' => 200, 'conectores_alimentacion' => ['1x 8-pin'], 'psu_minima_watts' => 500, 'salidas_video' => ['3x DisplayPort 1.4a', '1x HDMI 2.1'], 'ray_tracing' => true, 'dlss' => true, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'ASUS Dual GeForce RTX 4060 Ti OC 8GB', 'marca' => 'ASUS', 'fabricante' => 'NVIDIA', 'modelo' => 'DUAL-RTX4060TI-O8G', 'descripcion' => 'RTX 4060 Ti 8 GB GDDR6 con cooler Dual de ASUS. DLSS 3, Ada Lovelace. La opción preferida de gama media-alta para 1440p con eficiencia destacada.', 'imagen_url' => 'https://m.media-amazon.com/images/S/aplus-media-library-service-media/d0e5554e-07f4-4a71-9ed6-86e68b64ea1a.__CR0,0,300,300_PT0_SX300_V1___.png'],
            gpu: ['arquitectura' => 'Ada Lovelace', 'tipo_vram' => 'GDDR6', 'version_pcie' => 'PCIe 4.0', 'vram_gb' => 8, 'bus_bits' => 128, 'frecuencia_base_mhz' => 2310, 'frecuencia_boost_mhz' => 2565, 'tdp_watts' => 165, 'slots_pcie' => 2.5, 'longitud_mm' => 267, 'conectores_alimentacion' => ['1x 16-pin (12VHPWR)'], 'psu_minima_watts' => 550, 'salidas_video' => ['3x DisplayPort 1.4a', '1x HDMI 2.1'], 'ray_tracing' => true, 'dlss' => true, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'Gigabyte GeForce RTX 4060 Ti GAMING OC 16GB', 'marca' => 'Gigabyte', 'fabricante' => 'NVIDIA', 'modelo' => 'GV-N406TGAMING OC-16GD', 'descripcion' => 'RTX 4060 Ti 16 GB GDDR6, la versión de mayor VRAM de Ada Lovelace en gama media. Ideal para modelos de IA local y content creation a 1440p.', 'imagen_url' => 'https://media.ldlc.com/r1600/ld/products/00/06/05/19/LD0006051943.jpg'],
            gpu: ['arquitectura' => 'Ada Lovelace', 'tipo_vram' => 'GDDR6', 'version_pcie' => 'PCIe 4.0', 'vram_gb' => 16, 'bus_bits' => 128, 'frecuencia_base_mhz' => 2310, 'frecuencia_boost_mhz' => 2595, 'tdp_watts' => 165, 'slots_pcie' => 2.5, 'longitud_mm' => 282, 'conectores_alimentacion' => ['1x 16-pin (12VHPWR)'], 'psu_minima_watts' => 600, 'salidas_video' => ['3x DisplayPort 1.4a', '1x HDMI 2.1'], 'ray_tracing' => true, 'dlss' => true, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'ASUS ROG Strix GeForce RTX 4070 Super OC 12GB', 'marca' => 'ASUS', 'fabricante' => 'NVIDIA', 'modelo' => 'ROG-STRIX-RTX4070S-O12G-GAMING', 'descripcion' => 'RTX 4070 Super 12 GB GDDR6X con el cooler ROG Strix de primera clase. Salto significativo sobre el 4070 estándar en rendimiento 1440p/4K.', 'imagen_url' => 'https://media.karousell.com/media/photos/products/2024/1/18/asus_rog_strix_geforce_rtx_407_1705569489_f2688b1f_progressive.jpg'],
            gpu: ['arquitectura' => 'Ada Lovelace', 'tipo_vram' => 'GDDR6X', 'version_pcie' => 'PCIe 4.0', 'vram_gb' => 12, 'bus_bits' => 192, 'frecuencia_base_mhz' => 1980, 'frecuencia_boost_mhz' => 2610, 'tdp_watts' => 220, 'slots_pcie' => 3.5, 'longitud_mm' => 358, 'conectores_alimentacion' => ['1x 16-pin (12VHPWR)'], 'psu_minima_watts' => 700, 'salidas_video' => ['3x DisplayPort 1.4a', '1x HDMI 2.1'], 'ray_tracing' => true, 'dlss' => true, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'Gigabyte GeForce RTX 4070 Ti Super AORUS MASTER 16GB', 'marca' => 'Gigabyte', 'fabricante' => 'NVIDIA', 'modelo' => 'GV-N407TSAORUS M-16GD', 'descripcion' => 'RTX 4070 Ti Super 16 GB GDDR6X con el cooler AORUS MASTER flagship. Bus 256-bit y 16 GB lo convierten en la opción ideal para creadores y gaming 4K exigente.', 'imagen_url' => 'https://media.ldlc.com/bo/images/fiches/Carte_graphique/Gigabyte/gigabyte_aorus_rtx4070ti_master.jpg'],
            gpu: ['arquitectura' => 'Ada Lovelace', 'tipo_vram' => 'GDDR6X', 'version_pcie' => 'PCIe 4.0', 'vram_gb' => 16, 'bus_bits' => 256, 'frecuencia_base_mhz' => 2340, 'frecuencia_boost_mhz' => 2670, 'tdp_watts' => 285, 'slots_pcie' => 3.5, 'longitud_mm' => 357, 'conectores_alimentacion' => ['1x 16-pin (12VHPWR)'], 'psu_minima_watts' => 800, 'salidas_video' => ['3x DisplayPort 1.4a', '1x HDMI 2.1'], 'ray_tracing' => true, 'dlss' => true, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'ASUS Dual GeForce RTX 5060 Ti OC 16GB', 'marca' => 'ASUS', 'fabricante' => 'NVIDIA', 'modelo' => 'DUAL-RTX5060TI-O16G', 'descripcion' => 'RTX 5060 Ti 16 GB GDDR7 Blackwell. DLSS 4 con Multi Frame Generation. La gama media-alta de nueva generación con enorme salto de eficiencia y 16 GB de VRAM en bus 128-bit.', 'imagen_url' => 'https://hardwareand.co/images/thumb/asus-rtx5060-dual-banniere_preview.webp'],
            gpu: ['arquitectura' => 'Blackwell', 'tipo_vram' => 'GDDR7', 'version_pcie' => 'PCIe 5.0', 'vram_gb' => 16, 'bus_bits' => 128, 'frecuencia_base_mhz' => 2250, 'frecuencia_boost_mhz' => 2760, 'tdp_watts' => 180, 'slots_pcie' => 2.5, 'longitud_mm' => 270, 'conectores_alimentacion' => ['1x 16-pin (12V-2x6)'], 'psu_minima_watts' => 600, 'salidas_video' => ['3x DisplayPort 2.1', '1x HDMI 2.1b'], 'ray_tracing' => true, 'dlss' => true, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'MSI GeForce RTX 5070 GAMING TRIO OC 12GB', 'marca' => 'MSI', 'fabricante' => 'NVIDIA', 'modelo' => 'RTX 5070 GAMING TRIO OC 12G', 'descripcion' => 'RTX 5070 12 GB GDDR7 Blackwell con cooler GAMING TRIO de triple ventilador. Rendimiento cercano al RTX 4090 en rasterización gracias a DLSS 4 Multi Frame Generation.', 'imagen_url' => 'https://i.ebayimg.com/00/s/NzIzWDEwMDE=/z/RoAAAOSwaIVn2S19/$_12.PNG?set_id=880000500F'],
            gpu: ['arquitectura' => 'Blackwell', 'tipo_vram' => 'GDDR7', 'version_pcie' => 'PCIe 5.0', 'vram_gb' => 12, 'bus_bits' => 192, 'frecuencia_base_mhz' => 2160, 'frecuencia_boost_mhz' => 2720, 'tdp_watts' => 250, 'slots_pcie' => 3.0, 'longitud_mm' => 326, 'conectores_alimentacion' => ['1x 16-pin (12V-2x6)'], 'psu_minima_watts' => 700, 'salidas_video' => ['3x DisplayPort 2.1', '1x HDMI 2.1b'], 'ray_tracing' => true, 'dlss' => true, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'Gigabyte GeForce RTX 5070 Ti AORUS MASTER 16GB', 'marca' => 'Gigabyte', 'fabricante' => 'NVIDIA', 'modelo' => 'GV-N507TAORUS M-16GD', 'descripcion' => 'RTX 5070 Ti 16 GB GDDR7, bus 256-bit. Cooler AORUS MASTER con sistema de vapor en cámara doble. La opción más equilibrada de Blackwell para gaming 4K y IA local.', 'imagen_url' => 'https://cdn.hstatic.net/files/200000921511/file/bpstore-vga-gigabyte-aorus-geforce-rtx-5070-ti-master-16g__1_.png'],
            gpu: ['arquitectura' => 'Blackwell', 'tipo_vram' => 'GDDR7', 'version_pcie' => 'PCIe 5.0', 'vram_gb' => 16, 'bus_bits' => 256, 'frecuencia_base_mhz' => 2295, 'frecuencia_boost_mhz' => 2900, 'tdp_watts' => 300, 'slots_pcie' => 3.5, 'longitud_mm' => 350, 'conectores_alimentacion' => ['1x 16-pin (12V-2x6)'], 'psu_minima_watts' => 800, 'salidas_video' => ['3x DisplayPort 2.1', '1x HDMI 2.1b'], 'ray_tracing' => true, 'dlss' => true, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'ASUS ROG Astral GeForce RTX 5080 OC 16GB', 'marca' => 'ASUS', 'fabricante' => 'NVIDIA', 'modelo' => 'ROG-ASTRAL-RTX5080-O16G-GAMING', 'descripcion' => 'RTX 5080 16 GB GDDR7 con el cooler insignia ROG Astral de ASUS. Bus 256-bit, DLSS 4 Multi Frame Generation. El buque insignia accesible de Blackwell para entusiastas 4K.', 'imagen_url' => 'https://cdn.thefpsreview.com/wp-content/uploads/2025/01/nvidia-geforce-rtx-5090-5080-pricing-revealed-by-u-s-retailers-including-asus-rog-astral-geforce-rtx-5090-32gb-gddr7-oc-edition-for-2-799-99-feature.jpg'],
            gpu: ['arquitectura' => 'Blackwell', 'tipo_vram' => 'GDDR7', 'version_pcie' => 'PCIe 5.0', 'vram_gb' => 16, 'bus_bits' => 256, 'frecuencia_base_mhz' => 2295, 'frecuencia_boost_mhz' => 2950, 'tdp_watts' => 360, 'slots_pcie' => 3.5, 'longitud_mm' => 375, 'conectores_alimentacion' => ['1x 16-pin (12V-2x6)'], 'psu_minima_watts' => 850, 'salidas_video' => ['3x DisplayPort 2.1', '1x HDMI 2.1b'], 'ray_tracing' => true, 'dlss' => true, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'INNO3D GeForce RTX 5060 TWIN X2 OC 8GB (SFF)', 'marca' => 'INNO3D', 'fabricante' => 'NVIDIA', 'modelo' => 'N50602-08D7X-173305N', 'descripcion' => 'RTX 5060 8 GB GDDR7 Blackwell en formato compacto de 215 mm, 2 slots. TDP de solo 150 W. La opción Blackwell para builds SFF o ITX con PSU de bajo vataje.', 'imagen_url' => 'https://thumb.pccomponentes.com/w-530-530/articles/1087/10875101/1572-inno3d-geforce-rtx-5060-ti-twin-x2-oc-8gb-gddr7-reflex-2-rtx-ai-dlss4-8826ee1c-437d-4143-8da6-595cd7ad3e66.jpg'],
            gpu: ['arquitectura' => 'Blackwell', 'tipo_vram' => 'GDDR7', 'version_pcie' => 'PCIe 5.0', 'vram_gb' => 8, 'bus_bits' => 128, 'frecuencia_base_mhz' => 1830, 'frecuencia_boost_mhz' => 2497, 'tdp_watts' => 150, 'slots_pcie' => 2.0, 'longitud_mm' => 215, 'conectores_alimentacion' => ['1x 16-pin (12V-2x6)'], 'psu_minima_watts' => 500, 'salidas_video' => ['3x DisplayPort 2.1', '1x HDMI 2.1b'], 'ray_tracing' => true, 'dlss' => true, 'fsr' => true]
        );
        // ── AMD GPU ──────────────────────────────────────────────
        $this->crearGPU(
            comp: ['nombre' => 'Sapphire Pulse Radeon RX 7600 8GB', 'marca' => 'Sapphire', 'fabricante' => 'AMD', 'modelo' => '11324-01-20G', 'descripcion' => 'RX 7600 8 GB GDDR6, bus 128-bit. RDNA 3 con FSR 3 y AV1. El punto de entrada de la generación RX 7000 para gaming 1080p con muy buen precio.', 'imagen_url' => 'https://thumb.pccomponentes.com/w-530-530/articles/1072/10726162/2703-sapphire-pulse-amd-radeon-rx-7600-gaming-8gb-gddr6-foto.jpg'],
            gpu: ['arquitectura' => 'RDNA 3', 'tipo_vram' => 'GDDR6', 'version_pcie' => 'PCIe 4.0', 'vram_gb' => 8, 'bus_bits' => 128, 'frecuencia_base_mhz' => 1720, 'frecuencia_boost_mhz' => 2655, 'tdp_watts' => 165, 'slots_pcie' => 2.0, 'longitud_mm' => 235, 'conectores_alimentacion' => ['1x 8-pin'], 'psu_minima_watts' => 500, 'salidas_video' => ['3x DisplayPort 2.1', '1x HDMI 2.1'], 'ray_tracing' => true, 'dlss' => false, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'PowerColor Red Devil Radeon RX 7800 XT 16GB', 'marca' => 'PowerColor', 'fabricante' => 'AMD', 'modelo' => 'RX 7800 XT 16G-E/OC', 'descripcion' => 'RX 7800 XT 16 GB GDDR6, bus 256-bit. Cooler Red Devil de triple ventilador con botón BIOS silencioso. La opción premium de RDNA 3 para 1440p con mucha VRAM.', 'imagen_url' => 'https://cdn.mos.cms.futurecdn.net/xJceKxjg5Kb9aPRUsTzWVe.jpg'],
            gpu: ['arquitectura' => 'RDNA 3', 'tipo_vram' => 'GDDR6', 'version_pcie' => 'PCIe 4.0', 'vram_gb' => 16, 'bus_bits' => 256, 'frecuencia_base_mhz' => 1295, 'frecuencia_boost_mhz' => 2430, 'tdp_watts' => 263, 'slots_pcie' => 2.5, 'longitud_mm' => 323, 'conectores_alimentacion' => ['2x 8-pin'], 'psu_minima_watts' => 700, 'salidas_video' => ['3x DisplayPort 2.1', '1x HDMI 2.1'], 'ray_tracing' => true, 'dlss' => false, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'Sapphire Pulse Radeon RX 9070 16GB', 'marca' => 'Sapphire', 'fabricante' => 'AMD', 'modelo' => '11340-01-20G', 'descripcion' => 'RX 9070 16 GB GDDR6, bus 256-bit. Primera generación RDNA 4 con FSR 4 y trazado de rayos mejorado. Cooler Pulse de doble ventilador. Compite con la RTX 5070 a precio inferior.', 'imagen_url' => 'https://sm.pcmag.com/pcmag_au/review/s/sapphire-p/sapphire-pulse-amd-radeon-rx-9070_s7xt.jpg'],
            gpu: ['arquitectura' => 'RDNA 4', 'tipo_vram' => 'GDDR6', 'version_pcie' => 'PCIe 5.0', 'vram_gb' => 16, 'bus_bits' => 256, 'frecuencia_base_mhz' => 1700, 'frecuencia_boost_mhz' => 2520, 'tdp_watts' => 220, 'slots_pcie' => 2.5, 'longitud_mm' => 280, 'conectores_alimentacion' => ['1x 16-pin (12V-2x6)'], 'psu_minima_watts' => 650, 'salidas_video' => ['3x DisplayPort 2.1', '1x HDMI 2.1b'], 'ray_tracing' => true, 'dlss' => false, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'PowerColor Red Devil Radeon RX 9070 XT 16GB', 'marca' => 'PowerColor', 'fabricante' => 'AMD', 'modelo' => 'RX 9070 XT 16G-E/OC', 'descripcion' => 'RX 9070 XT 16 GB GDDR6, bus 256-bit. El flagship de RDNA 4 con cooler Red Devil de triple ventilador. Supera a la RTX 5070 en rasterización y tiene FSR 4 Neural.', 'imagen_url' => 'https://pausehardware.com/wp-content/uploads/2025/03/test-rx-9070-xt-16gb-780x470.webp'],
            gpu: ['arquitectura' => 'RDNA 4', 'tipo_vram' => 'GDDR6', 'version_pcie' => 'PCIe 5.0', 'vram_gb' => 16, 'bus_bits' => 256, 'frecuencia_base_mhz' => 1840, 'frecuencia_boost_mhz' => 2970, 'tdp_watts' => 304, 'slots_pcie' => 2.5, 'longitud_mm' => 330, 'conectores_alimentacion' => ['1x 16-pin (12V-2x6)'], 'psu_minima_watts' => 750, 'salidas_video' => ['3x DisplayPort 2.1', '1x HDMI 2.1b'], 'ray_tracing' => true, 'dlss' => false, 'fsr' => true]
        );
        // ── Intel GPU ────────────────────────────────────────────────
        $this->crearGPU(
            comp: ['nombre' => 'ASRock Intel Arc A380 Challenger ITX 6GB (SFF)', 'marca' => 'ASRock', 'fabricante' => 'Intel', 'modelo' => 'A380 Challenger ITX 6G OC', 'descripcion' => 'Arc A380 6 GB GDDR6, bus 96-bit. Formato ITX de solo 173 mm. La única GPU de gama de entrada Intel Arc con soporte AV1 encode/decode. Perfecta para builds SFF ultracompactos.', 'imagen_url' => 'https://acf.geeknetic.es/imagenes/auto/22/10/13/54l-rx1-image.png'],
            gpu: ['arquitectura' => 'RDNA 2', 'tipo_vram' => 'GDDR6', 'version_pcie' => 'PCIe 4.0', 'vram_gb' => 6, 'bus_bits' => 96, 'frecuencia_base_mhz' => 2000, 'frecuencia_boost_mhz' => 2450, 'tdp_watts' => 75, 'slots_pcie' => 2.0, 'longitud_mm' => 173, 'conectores_alimentacion' => [], 'psu_minima_watts' => 350, 'salidas_video' => ['3x DisplayPort 2.0', '1x HDMI 2.1'], 'ray_tracing' => true, 'dlss' => false, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'Gigabyte Intel Arc A750 Eagle OC 8GB', 'marca' => 'Gigabyte', 'fabricante' => 'Intel', 'modelo' => 'A750 EAGLE OC 8G', 'descripcion' => 'Arc A750 8 GB GDDR6, bus 256-bit. Cooler EAGLE de doble ventilador. Rendimiento cercano a la RTX 3060 con excelente encode AV1 y precio muy competitivo.', 'imagen_url' => 'https://acf.geeknetic.es/imgri/imagenes/auto/2023/2/13/4hj-la-intel-arc-a750-edicion-limitada-esta-disponible-en-una-tienda-de-japon-por-unos-140-euros-al-camb.png?f=webp'],
            gpu: ['arquitectura' => 'RDNA 2', 'tipo_vram' => 'GDDR6', 'version_pcie' => 'PCIe 4.0', 'vram_gb' => 8, 'bus_bits' => 256, 'frecuencia_base_mhz' => 2050, 'frecuencia_boost_mhz' => 2400, 'tdp_watts' => 225, 'slots_pcie' => 2.5, 'longitud_mm' => 275, 'conectores_alimentacion' => ['1x 8-pin'], 'psu_minima_watts' => 600, 'salidas_video' => ['3x DisplayPort 2.0', '1x HDMI 2.1'], 'ray_tracing' => true, 'dlss' => false, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'ASRock Intel Arc A770 Phantom Gaming OC 16GB', 'marca' => 'ASRock', 'fabricante' => 'Intel', 'modelo' => 'A770 PG OC 16GO', 'descripcion' => 'Arc A770 16 GB GDDR6, bus 256-bit. El flagship de Intel Arc A-series. Cooler Phantom Gaming de triple ventilador. Soporte XeSS, ray tracing y encode AV1 multibanda.', 'imagen_url' => 'https://static.tweaktown.com/news/16x9/92564_asrock-is-about-to-launch-new-phantom-gaming-intel-arc-a770-16gb-gpu-for-330.jpg'],
            gpu: ['arquitectura' => 'RDNA 2', 'tipo_vram' => 'GDDR6', 'version_pcie' => 'PCIe 4.0', 'vram_gb' => 16, 'bus_bits' => 256, 'frecuencia_base_mhz' => 2100, 'frecuencia_boost_mhz' => 2400, 'tdp_watts' => 225, 'slots_pcie' => 2.5, 'longitud_mm' => 300, 'conectores_alimentacion' => ['1x 8-pin'], 'psu_minima_watts' => 650, 'salidas_video' => ['3x DisplayPort 2.0', '1x HDMI 2.1'], 'ray_tracing' => true, 'dlss' => false, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'Gigabyte Intel Arc B580 Gaming OC 12GB', 'marca' => 'Gigabyte', 'fabricante' => 'Intel', 'modelo' => 'A580 GAMING OC 12G', 'descripcion' => 'Arc B580 12 GB GDDR6, bus 192-bit. La GPU sorpresa de finales de 2024: supera a la RTX 4060 en rasterización con el doble de VRAM. XeSS 2, DP 2.1, AV1. Cooler GAMING OC.', 'imagen_url' => 'https://cdn.shortpixel.ai/spai/q_lossy+ret_img+to_auto/www.pcguide.com/wp-content/uploads/2024/12/GUNNIR-Intel-Arc-B580-Index-and-Photon.jpg'],
            gpu: ['arquitectura' => 'RDNA 2', 'tipo_vram' => 'GDDR6', 'version_pcie' => 'PCIe 4.0', 'vram_gb' => 12, 'bus_bits' => 192, 'frecuencia_base_mhz' => 2280, 'frecuencia_boost_mhz' => 2850, 'tdp_watts' => 190, 'slots_pcie' => 2.5, 'longitud_mm' => 286, 'conectores_alimentacion' => ['1x 8-pin'], 'psu_minima_watts' => 550, 'salidas_video' => ['3x DisplayPort 2.1', '1x HDMI 2.1'], 'ray_tracing' => true, 'dlss' => false, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'ASRock Intel Arc B580 Steel Legend OC 12GB', 'marca' => 'ASRock', 'fabricante' => 'Intel', 'modelo' => 'B580 SL OC 12GO', 'descripcion' => 'Arc B580 12 GB GDDR6, bus 192-bit. Cooler Steel Legend de triple ventilador. La alternativa premium al B580 estándar con OC de fábrica y mejor disipación para overclock.', 'imagen_url' => 'https://www.hwcooling.net/wp-content/uploads/2025/09/asrock-intel-arc-b580-steel-legend-oc-12gb-test-recenze-review-00-1024x576.jpg'],
            gpu: ['arquitectura' => 'RDNA 2', 'tipo_vram' => 'GDDR6', 'version_pcie' => 'PCIe 4.0', 'vram_gb' => 12, 'bus_bits' => 192, 'frecuencia_base_mhz' => 2300, 'frecuencia_boost_mhz' => 2900, 'tdp_watts' => 200, 'slots_pcie' => 2.5, 'longitud_mm' => 295, 'conectores_alimentacion' => ['1x 8-pin'], 'psu_minima_watts' => 600, 'salidas_video' => ['3x DisplayPort 2.1', '1x HDMI 2.1'], 'ray_tracing' => true, 'dlss' => false, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'Gigabyte Intel Arc B770 Gaming OC 16GB', 'marca' => 'Gigabyte', 'fabricante' => 'Intel', 'modelo' => 'B770 GAMING OC 16G', 'descripcion' => 'Arc B770 16 GB GDDR6, bus 256-bit. El flagship de la serie B. Mayor rendimiento que el B580, con 16 GB de VRAM y soporte PCIe 4.0 x16. Excelente para creadores de contenido y 1440p.', 'imagen_url' => 'https://www.hardwarepremium.com/wp-content/uploads/2025/09/INTEL-Arc-B770-Portada.png'],
            gpu: ['arquitectura' => 'RDNA 2', 'tipo_vram' => 'GDDR6', 'version_pcie' => 'PCIe 4.0', 'vram_gb' => 16, 'bus_bits' => 256, 'frecuencia_base_mhz' => 2400, 'frecuencia_boost_mhz' => 2950, 'tdp_watts' => 225, 'slots_pcie' => 2.5, 'longitud_mm' => 300, 'conectores_alimentacion' => ['1x 8-pin'], 'psu_minima_watts' => 650, 'salidas_video' => ['3x DisplayPort 2.1', '1x HDMI 2.1'], 'ray_tracing' => true, 'dlss' => false, 'fsr' => true]
        );
        $this->crearGPU(
            comp: ['nombre' => 'ASUS Dual Intel Arc B580 OC 12GB', 'marca' => 'ASUS', 'fabricante' => 'Intel', 'modelo' => 'DUAL-B580-O12G', 'descripcion' => 'Arc B580 12 GB GDDR6 con cooler Dual Axial-tech de ASUS. Diseño compacto de 225 mm con buena disipación pasiva en reposo. Perfecto para gaming 1080p/1440p con excelente relación calidad/precio.', 'imagen_url' => 'https://media.ldlc.com/r1600/ld/products/00/06/19/56/LD0006195623.jpg'],
            gpu: ['arquitectura' => 'RDNA 2', 'tipo_vram' => 'GDDR6', 'version_pcie' => 'PCIe 4.0', 'vram_gb' => 12, 'bus_bits' => 192, 'frecuencia_base_mhz' => 2280, 'frecuencia_boost_mhz' => 2870, 'tdp_watts' => 190, 'slots_pcie' => 2.5, 'longitud_mm' => 225, 'conectores_alimentacion' => ['1x 8-pin'], 'psu_minima_watts' => 550, 'salidas_video' => ['3x DisplayPort 2.1', '1x HDMI 2.1'], 'ray_tracing' => true, 'dlss' => false, 'fsr' => true]
        );
    }
    // ═════════════════════════════════════════════════════════════════════════════
    //  Almacenamiento
    // ═════════════════════════════════════════════════════════════════════════════
    protected function seedAlmacenamientos(): void
    {
        $this->crearAlmacenamiento(
            comp: ['nombre' => 'Samsung 970 EVO Plus 1TB NVMe', 'marca' => 'Samsung', 'fabricante' => 'Samsung', 'modelo' => 'MZ-V7S1T0BW', 'descripcion' => 'SSD NVMe PCIe 3.0 x4 de 1 TB con NAND TLC V-NAND Samsung. Uno de los NVMe Gen3 más vendidos de todos los tiempos gracias a su equilibrio entre velocidad (3500/3300 MB/s), fiabilidad y precio. Incluye caché DRAM.', 'imagen_url' => 'https://m.media-amazon.com/images/I/61OOAXlbRtL.jpg'],
            alm: ['interfaz' => 'NVMe PCIe 3.0', 'factor_forma' => 'M.2 2280', 'tipo_nand' => 'TLC', 'tipo' => 'nvme', 'capacidad_gb' => 1000, 'velocidad_lectura_mbs' => 3500, 'velocidad_escritura_mbs' => 3300, 'rpm' => null, 'cache_mb' => 1024, 'tbw' => 600, 'cifrado' => true, 'dram' => true]
        );
        $this->crearAlmacenamiento(
            comp: ['nombre' => 'Samsung 990 Pro 2TB NVMe PCIe 4.0', 'marca' => 'Samsung', 'fabricante' => 'Samsung', 'modelo' => 'MZ-V9P2T0BW', 'descripcion' => 'SSD NVMe PCIe 4.0 x4 de 2 TB, la evolución del 980 Pro. 7450/6900 MB/s con eficiencia energética mejorada. TLC V-NAND y DRAM integrada. Ideal para gaming de alta gama y workstations.', 'imagen_url' => 'https://i.blogs.es/9ed0cb/ssd-980-pro/450_1000.jpg'],
            alm: ['interfaz' => 'NVMe PCIe 4.0', 'factor_forma' => 'M.2 2280', 'tipo_nand' => 'TLC', 'tipo' => 'nvme', 'capacidad_gb' => 2000, 'velocidad_lectura_mbs' => 7450, 'velocidad_escritura_mbs' => 6900, 'rpm' => null, 'cache_mb' => 2048, 'tbw' => 1200, 'cifrado' => true, 'dram' => true]
        );
        $this->crearAlmacenamiento(
            comp: ['nombre' => 'Western Digital Black SN850X 1TB NVMe PCIe 4.0', 'marca' => 'Western Digital', 'fabricante' => 'Western Digital', 'modelo' => 'WDS100T2X0E', 'descripcion' => 'SSD NVMe PCIe 4.0 x4 de 1 TB con tecnología Game Mode 2.0. 7300/6300 MB/s, TLC y DRAM. Certificado para PS5. El rival más serio del Samsung 990 Pro en gaming PC y consola.', 'imagen_url' => 'https://www.muycomputer.com/wp-content/uploads/2022/05/WD_Black_SN850X.jpg'],
            alm: ['interfaz' => 'NVMe PCIe 4.0', 'factor_forma' => 'M.2 2280', 'tipo_nand' => 'TLC', 'tipo' => 'nvme', 'capacidad_gb' => 1000, 'velocidad_lectura_mbs' => 7300, 'velocidad_escritura_mbs' => 6300, 'rpm' => null, 'cache_mb' => 1024, 'tbw' => 600, 'cifrado' => true, 'dram' => true]
        );
        $this->crearAlmacenamiento(
            comp: ['nombre' => 'Crucial P5 Plus 2TB NVMe PCIe 4.0', 'marca' => 'Crucial', 'fabricante' => 'Crucial', 'modelo' => 'CT2000P5PSSD8', 'descripcion' => 'SSD NVMe PCIe 4.0 x4 de 2 TB con NAND TLC Micron propia y DRAM. Velocidades de 6600/5000 MB/s. Excelente opción de gran capacidad Gen4 con garantía de 5 años y precio muy competitivo.', 'imagen_url' => 'https://dist.contentdriver.com.au/crucial/P5-PLUS-HEATSINK-CT2000P5PSSD5/images/large-mobile.jpg'],
            alm: ['interfaz' => 'NVMe PCIe 4.0', 'factor_forma' => 'M.2 2280', 'tipo_nand' => 'TLC', 'tipo' => 'nvme', 'capacidad_gb' => 2000, 'velocidad_lectura_mbs' => 6600, 'velocidad_escritura_mbs' => 5000, 'rpm' => null, 'cache_mb' => 2048, 'tbw' => 1200, 'cifrado' => true, 'dram' => true]
        );
        $this->crearAlmacenamiento(
            comp: ['nombre' => 'Lexar NM790 4TB NVMe PCIe 4.0', 'marca' => 'Lexar', 'fabricante' => 'Lexar', 'modelo' => 'LNM790X004T-RNNNG', 'descripcion' => 'SSD NVMe PCIe 4.0 x4 de 4 TB con controlador InnoGrit IG5236 y TLC. 7400/6500 MB/s. La opción de mayor capacidad disponible en Gen4 a un precio muy razonable. Sin DRAM externa.', 'imagen_url' => 'https://i.ytimg.com/vi/PyiOG2GfSCg/maxresdefault.jpg'],
            alm: ['interfaz' => 'NVMe PCIe 4.0', 'factor_forma' => 'M.2 2280', 'tipo_nand' => 'TLC', 'tipo' => 'nvme', 'capacidad_gb' => 4000, 'velocidad_lectura_mbs' => 7400, 'velocidad_escritura_mbs' => 6500, 'rpm' => null, 'cache_mb' => null, 'tbw' => 3000, 'cifrado' => false, 'dram' => false]
        );
        $this->crearAlmacenamiento(
            comp: ['nombre' => 'ADATA XPG Gammix S70 Blade 1TB NVMe PCIe 4.0', 'marca' => 'ADATA', 'fabricante' => 'ADATA', 'modelo' => 'AGAMMIXS70B-1T-CS', 'descripcion' => 'SSD NVMe PCIe 4.0 x4 de 1 TB con controlador InnoGrit IG5236 y TLC. 7400/5500 MB/s. Incluye disipador de aluminio delgado preinstalado. Buena opción Gen4 con disipador en precio contenido.', 'imagen_url' => 'https://i.blogs.es/812e85/adata-xpg-gammix-s70-blade/450_1000.webp'],
            alm: ['interfaz' => 'NVMe PCIe 4.0', 'factor_forma' => 'M.2 2280', 'tipo_nand' => 'TLC', 'tipo' => 'nvme', 'capacidad_gb' => 1000, 'velocidad_lectura_mbs' => 7400, 'velocidad_escritura_mbs' => 5500, 'rpm' => null, 'cache_mb' => null, 'tbw' => 740, 'cifrado' => false, 'dram' => false]
        );
        $this->crearAlmacenamiento(
            comp: ['nombre' => 'Samsung 9100 Pro 2TB NVMe PCIe 5.0', 'marca' => 'Samsung', 'fabricante' => 'Samsung', 'modelo' => 'MZ-X9P2T0BW', 'descripcion' => 'SSD NVMe PCIe 5.0 x4 de 2 TB con V-NAND TLC propietaria y DRAM. 14500/13400 MB/s. La nueva referencia Gen5 de Samsung. Requiere disipador robusto por su elevada temperatura de operación.', 'imagen_url' => 'https://cdn-reichelt.de/resize/600%2F-/web/xxl_ws/E600%2FMZ-VAPXT0BW_07.png?type=ProductXxl&resize=600%252F-&'],
            alm: ['interfaz' => 'NVMe PCIe 5.0', 'factor_forma' => 'M.2 2280', 'tipo_nand' => 'TLC', 'tipo' => 'nvme', 'capacidad_gb' => 2000, 'velocidad_lectura_mbs' => 14500, 'velocidad_escritura_mbs' => 13400, 'rpm' => null, 'cache_mb' => 2048, 'tbw' => 1200, 'cifrado' => true, 'dram' => true]
        );
        $this->crearAlmacenamiento(
            comp: ['nombre' => 'Crucial T705 2TB NVMe PCIe 5.0', 'marca' => 'Crucial', 'fabricante' => 'Crucial', 'modelo' => 'CT2000T705SSD3', 'descripcion' => 'SSD NVMe PCIe 5.0 x4 de 2 TB con controlador Phison E26 y TLC Micron 232L. 14500/12700 MB/s. Incluye disipador con aletas. Una de las primeras Gen5 disponibles masivamente en España a precio razonable.', 'imagen_url' => 'https://cdn.mos.cms.futurecdn.net/iZEGMzkmaX8o87Apg5HsZV.jpg'],
            alm: ['interfaz' => 'NVMe PCIe 5.0', 'factor_forma' => 'M.2 2280', 'tipo_nand' => 'TLC', 'tipo' => 'nvme', 'capacidad_gb' => 2000, 'velocidad_lectura_mbs' => 14500, 'velocidad_escritura_mbs' => 12700, 'rpm' => null, 'cache_mb' => 2048, 'tbw' => 1200, 'cifrado' => true, 'dram' => true]
        );
        $this->crearAlmacenamiento(
            comp: ['nombre' => 'Western Digital Black SN850X 4TB NVMe PCIe 5.0', 'marca' => 'Western Digital', 'fabricante' => 'Western Digital', 'modelo' => 'WDS400T2X0E', 'descripcion' => 'SSD NVMe PCIe 5.0 x4 de 4 TB con TLC y DRAM. 14900/14000 MB/s. La opción de mayor capacidad Gen5 disponible en el mercado consumer. Para workstations de vídeo 8K y gaming extremo.', 'imagen_url' => 'https://media.ldlc.com/r1600/ld/products/00/06/16/13/LD0006161307.jpg'],
            alm: ['interfaz' => 'NVMe PCIe 5.0', 'factor_forma' => 'M.2 2280', 'tipo_nand' => 'TLC', 'tipo' => 'nvme', 'capacidad_gb' => 4000, 'velocidad_lectura_mbs' => 14900, 'velocidad_escritura_mbs' => 14000, 'rpm' => null, 'cache_mb' => 4096, 'tbw' => 2400, 'cifrado' => true, 'dram' => true]
        );
        // ── SATA SSD 2.5" ─────────────────────────────────────────────────────
        $this->crearAlmacenamiento(
            comp: ['nombre' => 'Samsung 870 EVO 1TB SATA SSD', 'marca' => 'Samsung', 'fabricante' => 'Samsung', 'modelo' => 'MZ-77E1T0B/EU', 'descripcion' => 'SSD SATA III 2.5" de 1 TB con V-NAND TLC y DRAM integrada. 560/530 MB/s. El SSD SATA de mayor reputación del mercado. Ideal para actualizar un PC o portátil antiguo con HDD.', 'imagen_url' => 'https://images.samsung.com/is/image/samsung/p6pim/es/feature/95634014/es-feature-870-evo-sata-3-2-5-ssd-374601874?$FB_TYPE_A_MO_JPG$'],
            alm: ['interfaz' => 'SATA III', 'factor_forma' => '2.5"', 'tipo_nand' => 'TLC', 'tipo' => 'ssd', 'capacidad_gb' => 1000, 'velocidad_lectura_mbs' => 560, 'velocidad_escritura_mbs' => 530, 'rpm' => null, 'cache_mb' => 1024, 'tbw' => 600, 'cifrado' => true, 'dram' => true]
        );
        $this->crearAlmacenamiento(
            comp: ['nombre' => 'Kingston A400 480GB SATA SSD', 'marca' => 'Kingston', 'fabricante' => 'Kingston', 'modelo' => 'SA400S37/480G', 'descripcion' => 'SSD SATA III 2.5" de 480 GB con TLC y sin DRAM. 500/450 MB/s. El SSD de entrada más vendido de la historia en España. Perfecto para dar una segunda vida a equipos con HDD mecánico a mínimo coste.', 'imagen_url' => 'https://m.media-amazon.com/images/I/81Dz-1BA-3L._AC_UF350,350_QL80_.jpg'],
            alm: ['interfaz' => 'SATA III', 'factor_forma' => '2.5"', 'tipo_nand' => 'TLC', 'tipo' => 'ssd', 'capacidad_gb' => 480, 'velocidad_lectura_mbs' => 500, 'velocidad_escritura_mbs' => 450, 'rpm' => null, 'cache_mb' => null, 'tbw' => 160, 'cifrado' => false, 'dram' => false]
        );
        // ── HDD 3.5" ──────────────────────────────────────────────────────────
        $this->crearAlmacenamiento(
            comp: ['nombre' => 'Seagate Barracuda 4TB HDD 3.5"', 'marca' => 'Seagate', 'fabricante' => 'Seagate', 'modelo' => 'ST4000DM004', 'descripcion' => 'HDD SATA III 3.5" de 4 TB a 5400 rpm con 256 MB de caché. El disco mecánico de uso general más vendido en España. Ideal como almacenamiento secundario masivo junto a un SSD NVMe.', 'imagen_url' => 'https://www.asusbymacman.es/45469-medium_default/seagate-barracuda-4tb-sata-iii-35-st4000dm004-disco-duro.jpg'],
            alm: ['interfaz' => 'SATA III', 'factor_forma' => '3.5"', 'tipo_nand' => 'N/A', 'tipo' => 'hdd', 'capacidad_gb' => 4000, 'velocidad_lectura_mbs' => 190, 'velocidad_escritura_mbs' => 190, 'rpm' => 5400, 'cache_mb' => 256, 'tbw' => null, 'cifrado' => false, 'dram' => false]
        );
        $this->crearAlmacenamiento(
            comp: ['nombre' => 'Western Digital Red Plus 8TB HDD 3.5"', 'marca' => 'Western Digital', 'fabricante' => 'Western Digital', 'modelo' => 'WD80EFPX', 'descripcion' => 'HDD SATA III 3.5" de 8 TB CMR a 5640 rpm con 128 MB caché. Certificado para NAS 24/7. La opción más popular en el segmento NAS doméstico por su fiabilidad, compatibilidad y precio por TB.', 'imagen_url' => 'https://mesajil.com/wp-content/uploads/2025/10/126277.webp'],
            alm: ['interfaz' => 'SATA III', 'factor_forma' => '3.5"', 'tipo_nand' => 'N/A', 'tipo' => 'hdd', 'capacidad_gb' => 8000, 'velocidad_lectura_mbs' => 215, 'velocidad_escritura_mbs' => 215, 'rpm' => 5640, 'cache_mb' => 128, 'tbw' => null, 'cifrado' => false, 'dram' => false]
        );
    } 
    // ═════════════════════════════════════════════════════════════════════════════
    //  Gabinetes
    // ═════════════════════════════════════════════════════════════════════════════
    protected function crearGabinete(array $comp, array $gab): void
    {
        $marcaId    = $this->marcas[$comp['marca']] ?? null;
        $componente = Componente::create([
            'nombre'        => $comp['nombre'],
            'marca_id'      => $marcaId,
            'fabricante_id' => $marcaId,
            'categoria'     => 'gabinete',
            'modelo'        => $comp['modelo'],
            'imagen_url' => $comp['imagen_url'] ?? null,
            'descripcion'   => $comp['descripcion'] ?? null,
            'activo'        => true,
        ]);
    
        $g = Gabinete::create([
            'componente_id'                    => $componente->id,
            'tipo_gabinete_id'                 => $gab['tipo_id'],
            'estructura_gabinete_id'           => $gab['estructura_id'],
            'longitud_gpu_max_mm'              => $gab['gpu_max'],
            'altura_cooler_max_mm'             => $gab['cooler_max'],
            'largo_psu_max_mm'                 => $gab['psu_max'],
            'bahias_35'                        => $gab['bahias35'],
            'bahias_25'                        => $gab['bahias25'],
            'ventiladores_frontales'           => $gab['vent_front'],
            'ventiladores_traseros'            => $gab['vent_tras'],
            'ventiladores_superiores'          => $gab['vent_sup'],
            'ventiladores_incluidos'           => $gab['vent_incl'],
            'tam_ventilador_frontal_mm'        => $gab['tam_front'],
            'tam_ventilador_superior_mm'       => $gab['tam_sup'],
            'tam_ventilador_trasero_mm'        => $gab['tam_tras'],
            'soporte_radiadores'               => $gab['sop_rad'],
            'puertos_usb_frontales'            => $gab['usb_front'],
            'montaje_vertical_pcie'            => $gab['vert_pcie'],
            'panel_frontal'                    => $gab['panel'],
            'ancho_mm'                         => $gab['ancho'],
            'alto_mm'                          => $gab['alto'],
            'profundidad_mm'                   => $gab['prof'],
            'profundidad_camara_principal_mm'  => $gab['cam_p']  ?? null,
            'profundidad_camara_secundaria_mm' => $gab['cam_s']  ?? null,
            'particion_min_mm'                 => $gab['p_min']  ?? null,
            'particion_max_mm'                 => $gab['p_max']  ?? null,
        ]);
    
        $g->factoresForma()->attach(array_unique($gab['ff']));
        $g->tiposPSU()->attach(array_unique($gab['psu_tipos']));
    }
 
    protected function seedGabinetes(): void
    {
        $mid  = TipoGabinete::where('nombre', 'Mid Tower')->first();
        $mini = TipoGabinete::where('nombre', 'Mini Tower')->first();
        $full = TipoGabinete::where('nombre', 'Full Tower')->first();
        $sff  = TipoGabinete::where('nombre', 'SFF')->first();
        $mitx = TipoGabinete::where('nombre', 'SFF')->first();

        $conv = EstructuraGabinete::where('nombre', 'Tradicional')->first();
        $sand = EstructuraGabinete::where('nombre', 'Doble cámara')->first();
        $svar = EstructuraGabinete::where('nombre', 'Doble cámara flex')->first();

        $atx  = FactorForma::where('nombre', 'ATX')->first()->id;
        $eatx = FactorForma::where('nombre', 'E-ATX')->first()->id;
        $matx = FactorForma::where('nombre', 'Micro-ATX')->first()->id;
        $itx  = FactorForma::where('nombre', 'Mini-ITX')->first()->id;
        $dtx  = $itx;

        $pATX  = TipoPSU::where('nombre', 'ATX')->first()->id;
        $pSFX  = TipoPSU::where('nombre', 'SFX')->first()->id;
        $pSFXL = TipoPSU::where('nombre', 'SFX-L')->first()->id;
    
        /// ATX
        $this->crearGabinete(
            comp: ['nombre' => 'Fractal Design North', 'marca' => 'Fractal Design', 'modelo' => 'FD-C-NOR1C-02', 'descripcion' => 'Mid Tower ATX de diseño nórdico con frente de madera y panel lateral TG. Excelente refrigeración y acabado premium destacado en el segmento 100-130 €.', 'imagen_url' => 'https://static.wixstatic.com/media/7d7d74_8f6165ce2f404434b521ddd808a85093~mv2.jpg/v1/fill/w_568,h_710,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/7d7d74_8f6165ce2f404434b521ddd808a85093~mv2.jpg'],
            gab: ['tipo_id' => $mid->id, 'estructura_id' => $conv->id, 'ff' => [$atx, $matx, $itx], 'psu_tipos' => [$pATX], 'gpu_max' => 355, 'cooler_max' => 185, 'psu_max' => 250, 'bahias35' => 2, 'bahias25' => 2, 'vent_front' => 2, 'vent_sup' => 2, 'vent_tras' => 1, 'vent_incl' => 0, 'tam_front' => 140, 'tam_sup' => 120, 'tam_tras' => 120, 'sop_rad' => [120, 140, 240, 280, 360], 'usb_front' => ['2xUSB3', '1xUSB-C 3.2 Gen2'], 'vert_pcie' => true, 'panel' => 'Tempered Glass + Frente madera', 'ancho' => 230, 'alto' => 469, 'prof' => 427]
        );
        $this->crearGabinete(
            comp: ['nombre' => 'Corsair 4000D', 'marca' => 'Corsair', 'modelo' => 'CC-9011241-WW', 'descripcion' => 'Mid Tower ATX de Corsair con panel TG y frontal de malla. Gestión de cables mejorada, soporte para radiadores 360 mm y amplio espacio interno. Ideal para builds equilibradas.', 'imagen_url' => 'https://computerspace.in/cdn/shop/products/NewProject_25_b7e264ba-a9eb-4726-bcf6-50029655d86a.jpg?v=1629054333'],
            gab: ['tipo_id' => $mid->id, 'estructura_id' => $conv->id, 'ff' => [$atx, $matx, $itx], 'psu_tipos' => [$pATX], 'gpu_max' => 360, 'cooler_max' => 170, 'psu_max' => 180, 'bahias35' => 2, 'bahias25' => 2, 'vent_front' => 2, 'vent_sup' => 2, 'vent_tras' => 1, 'vent_incl' => 0, 'tam_front' => 120, 'tam_sup' => 120, 'tam_tras' => 120, 'sop_rad' => [120, 240, 280, 360], 'usb_front' => ['1xUSB3', '1xUSB-C', '2xUSB2'], 'vert_pcie' => false, 'panel' => 'Tempered Glass', 'ancho' => 230, 'alto' => 466, 'prof' => 453]
        );
        $this->crearGabinete(
            comp: ['nombre' => 'Lian Li Lancool III', 'marca' => 'Lian Li', 'modelo' => 'G99.OL3XW.00', 'descripcion' => 'Mid Tower ATX con tres ventiladores ARGB de 140 mm incluidos, amplia cámara principal y estructura optimizada para refrigeración líquida de alto caudal.', 'imagen_url' => 'https://cdn.wccftech.com/wp-content/uploads/2022/07/2022-07-16_9-54-35.png'],
            gab: ['tipo_id' => $mid->id, 'estructura_id' => $conv->id, 'ff' => [$atx, $eatx, $matx, $itx], 'psu_tipos' => [$pATX], 'gpu_max' => 435, 'cooler_max' => 190, 'psu_max' => 220, 'bahias35' => 2, 'bahias25' => 4, 'vent_front' => 3, 'vent_sup' => 3, 'vent_tras' => 1, 'vent_incl' => 3, 'tam_front' => 140, 'tam_sup' => 120, 'tam_tras' => 120, 'sop_rad' => [120, 140, 240, 280, 360, 420], 'usb_front' => ['2xUSB3', '1xUSB-C 3.2 Gen2'], 'vert_pcie' => true, 'panel' => 'Tempered Glass', 'ancho' => 240, 'alto' => 494, 'prof' => 468]
        );
        $this->crearGabinete(
            comp: ['nombre' => 'DeepCool CH560 Digital', 'marca' => 'DeepCool', 'modelo' => 'R-CH560-BKAAE4-G-1', 'descripcion' => 'Mid Tower ATX con panel TG y pantalla LCD integrada en el frontal para métricas del sistema. Incluye 4 ventiladores ARGB de 140 mm. Excelente para builds con mucha iluminación.', 'imagen_url' => 'https://www.neobyte.es/104119-medium_default/deepcool-ch560-digital-caja-eatx.jpg'],
            gab: ['tipo_id' => $mid->id, 'estructura_id' => $conv->id, 'ff' => [$atx, $eatx, $matx, $itx], 'psu_tipos' => [$pATX], 'gpu_max' => 400, 'cooler_max' => 185, 'psu_max' => 220, 'bahias35' => 2, 'bahias25' => 2, 'vent_front' => 3, 'vent_sup' => 2, 'vent_tras' => 1, 'vent_incl' => 4, 'tam_front' => 140, 'tam_sup' => 140, 'tam_tras' => 120, 'sop_rad' => [120, 140, 240, 280, 360, 420], 'usb_front' => ['2xUSB3', '1xUSB-C'], 'vert_pcie' => true, 'panel' => 'Tempered Glass + LCD frontal', 'ancho' => 235, 'alto' => 498, 'prof' => 455]
        );
        $this->crearGabinete(
            comp: ['nombre' => 'Lian Li PC-O11 Vision', 'marca' => 'Lian Li', 'modelo' => 'G99.O11VX.00', 'descripcion' => 'Mid Tower ATX de doble cámara estilo pecera con paneles TG en tres lados. Interior dividido en cámara principal (mobo+GPU) y secundaria (PSU+almacenamiento). Ideal para builds de lujo con refrigeración líquida custom.', 'imagen_url' => 'https://i.ytimg.com/vi/sYBH2_qJji4/maxresdefault.jpg'],
            gab: ['tipo_id' => $mid->id, 'estructura_id' => $sand->id, 'ff' => [$atx, $eatx, $matx, $itx], 'psu_tipos' => [$pATX], 'gpu_max' => 420, 'cooler_max' => 172, 'psu_max' => 200, 'bahias35' => 0, 'bahias25' => 4, 'vent_front' => 3, 'vent_sup' => 3, 'vent_tras' => 1, 'vent_incl' => 0, 'tam_front' => 120, 'tam_sup' => 120, 'tam_tras' => 120, 'sop_rad' => [120, 240, 360, 420], 'usb_front' => ['2xUSB3', '1xUSB-C 3.2 Gen2'], 'vert_pcie' => true, 'panel' => 'Tempered Glass 3 caras', 'ancho' => 285, 'alto' => 476, 'prof' => 465, 'cam_p' => 295, 'cam_s' => 155]
        );
        $this->crearGabinete(
            comp: ['nombre' => 'Corsair 5000X RGB', 'marca' => 'Corsair', 'modelo' => 'CC-9011212-WW', 'descripcion' => 'Mid Tower ATX "full glass" con paneles TG en frontal, lateral y superior. Tres ventiladores LL RGB de 120 mm incluidos y sistema de gestión de cables ocultos. Uno de los más espectaculares en su gama de precio.', 'imagen_url' => 'https://brightstarcomp.com/cdn/shop/files/85be533a2e6f170e16b4c571a613af88.jpg?v=1741573544'],
            gab: ['tipo_id' => $mid->id, 'estructura_id' => $conv->id, 'ff' => [$atx, $eatx, $matx, $itx], 'psu_tipos' => [$pATX], 'gpu_max' => 400, 'cooler_max' => 190, 'psu_max' => 225, 'bahias35' => 4, 'bahias25' => 4, 'vent_front' => 3, 'vent_sup' => 3, 'vent_tras' => 1, 'vent_incl' => 3, 'tam_front' => 120, 'tam_sup' => 120, 'tam_tras' => 120, 'sop_rad' => [120, 240, 280, 360], 'usb_front' => ['1xUSB3', '1xUSB-C', '2xUSB2'], 'vert_pcie' => true, 'panel' => 'Tempered Glass 3 caras', 'ancho' => 245, 'alto' => 520, 'prof' => 520]
        );
        // mATX 
        $this->crearGabinete(
            comp: ['nombre' => 'Jonsbo D31 Mesh', 'marca' => 'Jonsbo', 'modelo' => 'D31 MESH Black', 'descripcion' => 'Mini Tower mATX con frontal de malla de alto flujo de aire, panel lateral TG y soporte para radiadores de 240 mm. Compacto, silencioso y de fabricación en aluminio.', 'imagen_url' => 'https://jonsbo.vn/wp-content/uploads/2025/08/c42.jpg'],
            gab: ['tipo_id' => $mini->id, 'estructura_id' => $conv->id, 'ff' => [$matx, $itx], 'psu_tipos' => [$pATX], 'gpu_max' => 320, 'cooler_max' => 165, 'psu_max' => 160, 'bahias35' => 1, 'bahias25' => 2, 'vent_front' => 2, 'vent_sup' => 1, 'vent_tras' => 1, 'vent_incl' => 0, 'tam_front' => 120, 'tam_sup' => 120, 'tam_tras' => 120, 'sop_rad' => [120, 240], 'usb_front' => ['2xUSB3', '1xUSB-C'], 'vert_pcie' => false, 'panel' => 'Tempered Glass + Frente malla', 'ancho' => 185, 'alto' => 358, 'prof' => 340]
        );
        $this->crearGabinete(
            comp: ['nombre' => 'ASUS Prime AP201', 'marca' => 'ASUS', 'modelo' => 'AP201', 'descripcion' => 'Mini Tower mATX de diseño compacto con estructura de malla de acero en tres lados y soporte para refrigeración líquida de 240 mm. Compatible con GPU de hasta 338 mm a pesar de su volumen reducido (24,4 L).', 'imagen_url' => 'https://dlcdnwebimgs.asus.com/files/media/e4438114-3b1a-475b-86fd-18521d62b2e7/v1/video/ASUS-Prime-AP201.jpg'],
            gab: ['tipo_id' => $mini->id, 'estructura_id' => $conv->id, 'ff' => [$matx, $itx], 'psu_tipos' => [$pATX], 'gpu_max' => 338, 'cooler_max' => 155, 'psu_max' => 160, 'bahias35' => 0, 'bahias25' => 2, 'vent_front' => 2, 'vent_sup' => 1, 'vent_tras' => 1, 'vent_incl' => 0, 'tam_front' => 120, 'tam_sup' => 120, 'tam_tras' => 120, 'sop_rad' => [120, 240], 'usb_front' => ['1xUSB3', '1xUSB-C 3.2 Gen2'], 'vert_pcie' => false, 'panel' => 'Malla de acero 3 caras', 'ancho' => 188, 'alto' => 300, 'prof' => 433]
        );
        $this->crearGabinete(
            comp: ['nombre' => 'Antec Performance 1M', 'marca' => 'Antec', 'modelo' => 'P1M Black', 'descripcion' => 'Mini Tower mATX con paneles laterales de aluminio anodizado, panel TG y aislamiento acústico en techo y laterales. Enfocado en silencio y calidad de acabado.', 'imagen_url' => 'https://www.asusbymacman.es/51114-thickbox_default/antec-performance-1-m-aventurine-caja.jpg'],
            gab: ['tipo_id' => $mini->id, 'estructura_id' => $conv->id, 'ff' => [$matx, $itx], 'psu_tipos' => [$pATX], 'gpu_max' => 300, 'cooler_max' => 155, 'psu_max' => 170, 'bahias35' => 2, 'bahias25' => 2, 'vent_front' => 2, 'vent_sup' => 1, 'vent_tras' => 1, 'vent_incl' => 0, 'tam_front' => 120, 'tam_sup' => 120, 'tam_tras' => 120, 'sop_rad' => [120, 240], 'usb_front' => ['2xUSB3'], 'vert_pcie' => false, 'panel' => 'Aluminio + Tempered Glass', 'ancho' => 200, 'alto' => 395, 'prof' => 370]
        );
        $this->crearGabinete(
            comp: ['nombre' => 'Jonsbo Z20', 'marca' => 'Jonsbo', 'modelo' => 'Z20 Black', 'descripcion' => 'Mini Tower mATX ultra compacto (15,7 L) con frente de aluminio cepillado, panel lateral TG y soporte para GPU de hasta 330 mm. Una de las opciones mATX más pequeñas del mercado.', 'imagen_url' => 'https://pics.computerbase.de/1/1/2/8/9/4-b669ab26b43854d2/article-1280x720.0c4e9986.jpg'],
            gab: ['tipo_id' => $mini->id, 'estructura_id' => $conv->id, 'ff' => [$matx, $itx], 'psu_tipos' => [$pATX], 'gpu_max' => 330, 'cooler_max' => 80, 'psu_max' => 150, 'bahias35' => 0, 'bahias25' => 1, 'vent_front' => 0, 'vent_sup' => 2, 'vent_tras' => 0, 'vent_incl' => 0, 'tam_front' => 0, 'tam_sup' => 120, 'tam_tras' => 0, 'sop_rad' => [120, 240], 'usb_front' => ['2xUSB3', '1xUSB-C'], 'vert_pcie' => false, 'panel' => 'Aluminio + Tempered Glass', 'ancho' => 173, 'alto' => 290, 'prof' => 310]
        );
        $this->crearGabinete(
            comp: ['nombre' => 'Fractal Design Pop Mini', 'marca' => 'Fractal Design', 'modelo' => 'FD-C-POM1A-01', 'descripcion' => 'Mini Tower mATX con frontal de acero texturizado, panel lateral TG y dos ventiladores de 120 mm incluidos. Relación calidad/precio destacada en el segmento mATX económico.', 'imagen_url' => 'https://www.fractal-design.com/app/uploads/2022/06/PopMiniAirVis_KV4_2560.jpg'],
            gab: ['tipo_id' => $mini->id, 'estructura_id' => $conv->id, 'ff' => [$matx, $itx], 'psu_tipos' => [$pATX], 'gpu_max' => 341, 'cooler_max' => 169, 'psu_max' => 175, 'bahias35' => 2, 'bahias25' => 2, 'vent_front' => 2, 'vent_sup' => 1, 'vent_tras' => 1, 'vent_incl' => 2, 'tam_front' => 120, 'tam_sup' => 120, 'tam_tras' => 120, 'sop_rad' => [120, 240], 'usb_front' => ['2xUSB3', '1xUSB-C'], 'vert_pcie' => false, 'panel' => 'Tempered Glass', 'ancho' => 175, 'alto' => 380, 'prof' => 360]
        );
        $this->crearGabinete(
            comp: ['nombre' => 'NZXT H5 Flow', 'marca' => 'NZXT', 'modelo' => 'CC-H51FB-01', 'descripcion' => 'Mini Tower mATX con frontal de malla y panel lateral TG. Diseño compacto con excelente gestión de cables. Compatible con radiadores de 280 mm en el frontal.', 'imagen_url' => 'https://www.asusbymacman.es/43899-large_default/nzxt-h5-flow-2024-black-caja.jpg'],
            gab: ['tipo_id' => $mini->id, 'estructura_id' => $conv->id, 'ff' => [$matx, $itx], 'psu_tipos' => [$pATX], 'gpu_max' => 365, 'cooler_max' => 165, 'psu_max' => 200, 'bahias35' => 2, 'bahias25' => 2, 'vent_front' => 2, 'vent_sup' => 1, 'vent_tras' => 1, 'vent_incl' => 0, 'tam_front' => 120, 'tam_sup' => 120, 'tam_tras' => 120, 'sop_rad' => [120, 240, 280], 'usb_front' => ['1xUSB3', '1xUSB-C'], 'vert_pcie' => false, 'panel' => 'Tempered Glass', 'ancho' => 210, 'alto' => 387, 'prof' => 398]
        );
        $this->crearGabinete(
            comp: ['nombre' => 'Lian Li PC-O11 Air Mini', 'marca' => 'Lian Li', 'modelo' => 'G99.O11AMX.00', 'descripcion' => 'Mini Tower mATX de doble cámara con paneles TG en lateral y frontal. Diseño sandwich compacto que permite hasta 420 mm de GPU y radiadores de 360 mm en la cámara secundaria.', 'imagen_url' => 'https://i.ytimg.com/vi/PvaRGVPWlh0/maxresdefault.jpg'],
            gab: ['tipo_id' => $mini->id, 'estructura_id' => $sand->id, 'ff' => [$matx, $itx], 'psu_tipos' => [$pATX], 'gpu_max' => 420, 'cooler_max' => 157, 'psu_max' => 180, 'bahias35' => 0, 'bahias25' => 2, 'vent_front' => 3, 'vent_sup' => 0, 'vent_tras' => 0, 'vent_incl' => 0, 'tam_front' => 120, 'tam_sup' => 0, 'tam_tras' => 0, 'sop_rad' => [120, 240, 360], 'usb_front' => ['2xUSB3', '1xUSB-C 3.2 Gen2'], 'vert_pcie' => false, 'panel' => 'Tempered Glass x2', 'ancho' => 216, 'alto' => 404, 'prof' => 380, 'cam_p' => 250, 'cam_s' => 115]
        );
         // ITX / SFF 
        $this->crearGabinete(
            comp: ['nombre' => 'Cooler Master NR200P', 'marca' => 'Cooler Master', 'modelo' => 'SL-N20P-WGNN-S00', 'descripcion' => 'SFF ITX con estructura de malla, intercambiable con panel TG, soporte para refrigeración líquida de 280 mm y GPU de hasta 330 mm. El SFF más popular de los últimos años en el mercado español.', 'imagen_url' => 'https://manuals.plus/wp-content/uploads/2022/08/COOLER-MASTER-NR200P-Mini-ITX-PC-Case-features.jpg'],
            gab: ['tipo_id' => $sff->id, 'estructura_id' => $conv->id, 'ff' => [$itx, $dtx], 'psu_tipos' => [$pSFX, $pSFXL], 'gpu_max' => 330, 'cooler_max' => 155, 'psu_max' => 130, 'bahias35' => 0, 'bahias25' => 2, 'vent_front' => 2, 'vent_sup' => 1, 'vent_tras' => 0, 'vent_incl' => 0, 'tam_front' => 120, 'tam_sup' => 120, 'tam_tras' => 0, 'sop_rad' => [120, 240, 280], 'usb_front' => ['1xUSB-C', '2xUSB3'], 'vert_pcie' => false, 'panel' => 'Malla + Tempered Glass (intercambiables)', 'ancho' => 185, 'alto' => 274, 'prof' => 360]
        );
        $this->crearGabinete(
            comp: ['nombre' => 'Cooler Master NR200P V2', 'marca' => 'Cooler Master', 'modelo' => 'SL-N20P-WGNN-S02', 'descripcion' => 'Revisión del NR200P con mejoras en el sistema de cable management, nueva estructura de ventilación superior y compatibilidad con PSU ATX cortas. Mantiene el mismo volumen compacto (18 L) con más opciones de refrigeración.', 'imagen_url' => 'https://www.coolermaster.com/on/demandware.static/-/Sites-cooler-master-main/default/dw2e61ec65/Assets/masterbox-nr200p-v2/large/gallery-1.png'],
            gab: ['tipo_id' => $sff->id, 'estructura_id' => $conv->id, 'ff' => [$itx, $dtx], 'psu_tipos' => [$pSFX, $pSFXL], 'gpu_max' => 335, 'cooler_max' => 155, 'psu_max' => 130, 'bahias35' => 0, 'bahias25' => 2, 'vent_front' => 2, 'vent_sup' => 2, 'vent_tras' => 0, 'vent_incl' => 0, 'tam_front' => 120, 'tam_sup' => 120, 'tam_tras' => 0, 'sop_rad' => [120, 240, 280], 'usb_front' => ['1xUSB-C', '2xUSB3'], 'vert_pcie' => false, 'panel' => 'Malla + Tempered Glass (intercambiables)', 'ancho' => 185, 'alto' => 280, 'prof' => 362]
        );
        $this->crearGabinete(
            comp: ['nombre' => 'Fractal Design Terra', 'marca' => 'Fractal Design', 'modelo' => 'FD-C-TER1N-02', 'descripcion' => 'SFF ITX de estructura sandwich variable con chasis de aluminio y paneles laterales de aluminio o malla. Volumen de solo 10,5 L con GPU de hasta 322 mm y PSU SFX/SFX-L. El SFF más elegante del mercado.', 'imagen_url' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS9OhtylBmUCDLu8lKu6GwFw4VScSHo7vznNQ&s'],
            gab: ['tipo_id' => $sff->id, 'estructura_id' => $svar->id, 'ff' => [$itx], 'psu_tipos' => [$pSFX, $pSFXL], 'gpu_max' => 322, 'cooler_max' => 65, 'psu_max' => 130, 'bahias35' => 0, 'bahias25' => 1, 'vent_front' => 0, 'vent_sup' => 0, 'vent_tras' => 1, 'vent_incl' => 0, 'tam_front' => 0, 'tam_sup' => 0, 'tam_tras' => 120, 'sop_rad' => [120], 'usb_front' => ['1xUSB-C 3.2 Gen2'], 'vert_pcie' => false, 'panel' => 'Aluminio + Malla lateral', 'ancho' => 153, 'alto' => 310, 'prof' => 258, 'cam_p' => 174, 'cam_s' => 74, 'p_min' => 74, 'p_max' => 174]
        );
        $this->crearGabinete(
            comp: ['nombre' => 'Lian Li DAN A3-mATX', 'marca' => 'Lian Li', 'modelo' => 'G99.A3MX.00', 'descripcion' => 'SFF mATX de estructura sandwich desarrollado en colaboración con DAN Cases. Volumen de 17,7 L compatible con GPU de hasta 340 mm, rad de 240 mm y PSU ATX corta o SFX-L. El SFF mATX de referencia del mercado.', 'imagen_url' => 'https://i.ytimg.com/vi/Rwy2hjOVx4U/hq720.jpg?sqp=-oaymwEhCK4FEIIDSFryq4qpAxMIARUAAAAAGAElAADIQj0AgKJD&rs=AOn4CLC-hPZ_oQS4fHk8acS7Y0ACmMIBOw'],
            gab: ['tipo_id' => $sff->id, 'estructura_id' => $sand->id, 'ff' => [$matx, $itx], 'psu_tipos' => [$pATX, $pSFX, $pSFXL], 'gpu_max' => 340, 'cooler_max' => 76, 'psu_max' => 150, 'bahias35' => 0, 'bahias25' => 2, 'vent_front' => 0, 'vent_sup' => 2, 'vent_tras' => 0, 'vent_incl' => 0, 'tam_front' => 0, 'tam_sup' => 120, 'tam_tras' => 0, 'sop_rad' => [120, 240], 'usb_front' => ['1xUSB-C 3.2 Gen2', '1xUSB3'], 'vert_pcie' => false, 'panel' => 'Aluminio anodizado + Malla', 'ancho' => 219, 'alto' => 239, 'prof' => 338, 'cam_p' => 185, 'cam_s' => 52]
        );
        $this->crearGabinete(
            comp: ['nombre' => 'Jonsbo D31 STD', 'marca' => 'Jonsbo', 'modelo' => 'D31 STD Black', 'descripcion' => 'Mini Tower ITX compacto con frontal de aluminio cepillado y panel lateral TG. Compatible con PSU ATX estándar y GPU de hasta 310 mm en un volumen de solo 14 L.', 'imagen_url' => 'https://minhancomputercdn.com/media/product/12582_v____case_jonsbo_d31_std_sc_black__13_.jpg'],
            gab: ['tipo_id' => $mitx->id, 'estructura_id' => $conv->id, 'ff' => [$itx], 'psu_tipos' => [$pATX], 'gpu_max' => 310, 'cooler_max' => 155, 'psu_max' => 160, 'bahias35' => 0, 'bahias25' => 1, 'vent_front' => 1, 'vent_sup' => 1, 'vent_tras' => 1, 'vent_incl' => 0, 'tam_front' => 120, 'tam_sup' => 120, 'tam_tras' => 120, 'sop_rad' => [120, 240], 'usb_front' => ['2xUSB3', '1xUSB-C'], 'vert_pcie' => false, 'panel' => 'Aluminio + Tempered Glass', 'ancho' => 185, 'alto' => 318, 'prof' => 245]
        );
        $this->crearGabinete(
            comp: ['nombre' => 'Lian Li A4-H2O', 'marca' => 'Lian Li', 'modelo' => 'PC-A4H2OX4', 'descripcion' => 'SFF ITX de doble cámara optimizado para refrigeración líquida de 240 mm en tan solo 11 L. Estructura de aluminio anodizado en negro o plata. GPU horizontal en cámara principal de hasta 305 mm.', 'imagen_url' => 'https://i.ytimg.com/vi/gnwHhJiSv1g/maxresdefault.jpg'],
            gab: ['tipo_id' => $sff->id, 'estructura_id' => $sand->id, 'ff' => [$itx], 'psu_tipos' => [$pSFX, $pSFXL], 'gpu_max' => 305, 'cooler_max' => 52, 'psu_max' => 130, 'bahias35' => 0, 'bahias25' => 0, 'vent_front' => 0, 'vent_sup' => 0, 'vent_tras' => 0, 'vent_incl' => 0, 'tam_front' => 0, 'tam_sup' => 0, 'tam_tras' => 0, 'sop_rad' => [240], 'usb_front' => ['1xUSB-C', '1xUSB3'], 'vert_pcie' => false, 'panel' => 'Aluminio anodizado + Malla perforada', 'ancho' => 66, 'alto' => 320, 'prof' => 155, 'cam_p' => 175, 'cam_s' => 65]
        );
    }
    // ═════════════════════════════════════════════════════════════════════════════
    //  PSUs
    // ═════════════════════════════════════════════════════════════════════════════
    protected function crearPSU(array $comp, array $psu): void
    {
        $marcaId = $this->marcas[$comp['marca']] ?? null;
        $fabId   = $this->marcas[$comp['fabricante'] ?? $comp['marca']] ?? $marcaId;
        $componente = Componente::create(['nombre' => $comp['nombre'], 'marca_id' => $marcaId, 'fabricante_id' => $fabId, 'categoria' => 'psu', 'modelo' => $comp['modelo'], 'imagen_url' => $comp['imagen_url'] ?? null, 'descripcion' => $comp['descripcion'] ?? null, 'activo' => true]);
        PSU::create(['componente_id' => $componente->id, 'certificacion_id' => $this->certs[$psu['cert']] ?? null, 'tipo_psu_id' => $this->tipos[$psu['tipo']] ?? null, 'vatios' => $psu['vatios'], 'modular' => $psu['modular'], 'version_atx' => $psu['version_atx'] ?? null, 'conectores_pcie_16pin' => $psu['pcie16'] ?? 0, 'conectores_pcie_8pin' => $psu['pcie8'] ?? 0, 'conectores_sata' => $psu['sata'], 'conectores_molex' => $psu['molex'], 'largo_mm' => $psu['largo_mm'], 'ventilador_mm' => $psu['vent_mm'], 'ventilador_zero_rpm' => $psu['zero_rpm']]);
    }

    protected function seedPSUs(): void
    {
        $this->tipos = [];
        foreach (TipoPSU::all() as $t) { $this->tipos[$t->nombre] = $t->id; }

        $this->certs = [];
        foreach (\App\Models\Auxiliares\CertificacionPSU::all() as $c) { $this->certs[$c->nombre] = $c->id; }
        

        $this->crearPSU(
            comp: ['nombre' => 'DeepCool PQ650M', 'marca' => 'DeepCool', 'modelo' => 'R-PQ650M-FA0B-EU', 'descripcion' => 'PSU ATX 650W 80 Plus Gold semi modular. Ventilador de 120 mm con modo semi-pasivo. Buena opción económica para sistemas con RTX 4060 Ti o RX 7700 XT.', 'imagen_url' => 'https://gzhls.at/pix/98/fd/98fdd340eb51f171-n.webp'],
            psu:  ['cert' => '80 Plus Gold', 'tipo' => 'ATX', 'vatios' => 650, 'modular' => 'semi_modular', 'version_atx' => '2.4', 'pcie16' => 0, 'pcie8' => 2, 'sata' => 8, 'molex' => 4, 'largo_mm' => 140, 'vent_mm' => 120, 'zero_rpm' => true]
        );
       $this->crearPSU(
            comp: ['nombre' => 'Seasonic Focus GX-750', 'marca' => 'Seasonic', 'modelo' => 'SSR-750FX', 'descripcion' => 'PSU ATX 750W 80 Plus Gold full modular. Referencia de calidad-precio. Ventilador de 120 mm con modo híbrido zero-rpm. Ideal para RTX 4070 o RX 7800 XT.', 'imagen_url' => 'https://assetsio.gnwcdn.com/seasonic-focus-gx750-psu-df-deal.jpg?width=1600&height=900&fit=crop&quality=100&format=png&enable=upscale&auto=webp'],
            psu:  ['cert' => '80 Plus Gold', 'tipo' => 'ATX', 'vatios' => 750, 'modular' => 'full_modular', 'version_atx' => '2.4', 'pcie16' => 0, 'pcie8' => 4, 'sata' => 12, 'molex' => 4, 'largo_mm' => 140, 'vent_mm' => 120, 'zero_rpm' => true]
        );
        $this->crearPSU(
            comp: ['nombre' => 'EVGA SuperNOVA 850 G6', 'marca' => 'EVGA', 'modelo' => '220-G6-0850-X1', 'descripcion' => 'PSU ATX 850W 80 Plus Gold full modular. Compacta (140 mm). Ventilador de 135 mm con modo eco zero-rpm. Apta para RTX 4070 Ti / RX 7900 XT.', 'imagen_url' => 'https://image.citycenter.jo/cache/catalog/12022/850evga-1200x630.jpg'],
            psu:  ['cert' => '80 Plus Gold', 'tipo' => 'ATX', 'vatios' => 850, 'modular' => 'full_modular', 'version_atx' => '2.4', 'pcie16' => 0, 'pcie8' => 4, 'sata' => 10, 'molex' => 4, 'largo_mm' => 140, 'vent_mm' => 135, 'zero_rpm' => true]
        );
        $this->crearPSU(
            comp: ['nombre' => 'MSI MAG A750GL PCIE5', 'marca' => 'MSI', 'modelo' => 'MAG A750GL', 'descripcion' => 'PSU ATX 3.0 750W 80 Plus Gold full modular con conector 12VHPWR nativo para PCIe 5.0. Compatible con RTX 4070 y RX 7800 XT sin adaptadores. Ventilador de 135 mm con modo semi-pasivo.', 'imagen_url' => 'https://storage-asset.msi.com/global/picture/image/feature/power/MAG/A750GL-PCIE5/dc-img.jpg'],
            psu:  ['cert' => '80 Plus Gold', 'tipo' => 'ATX 3.0', 'vatios' => 750, 'modular' => 'full_modular', 'version_atx' => '3.0', 'pcie16' => 1, 'pcie8' => 2, 'sata' => 10, 'molex' => 3, 'largo_mm' => 150, 'vent_mm' => 135, 'zero_rpm' => true]
        );
        $this->crearPSU(
            comp: ['nombre' => 'Corsair RM850x', 'marca' => 'Corsair', 'modelo' => 'CP-9020201-EU', 'descripcion' => 'PSU ATX 850W 80 Plus Gold full modular. Ventilador de 135 mm con modo cero rpm hasta el 40% de carga. 10 años de garantía. Apta para RTX 4080 / RX 7900 XTX.', 'imagen_url' => 'https://www.bhphotovideo.com/images/fb/corsair_cp_9020200_na_rmx_series_rm850x_850w_1728338.jpg'],
            psu:  ['cert' => '80 Plus Gold', 'tipo' => 'ATX', 'vatios' => 850, 'modular' => 'full_modular', 'version_atx' => '2.4', 'pcie16' => 0, 'pcie8' => 6, 'sata' => 12, 'molex' => 4, 'largo_mm' => 160, 'vent_mm' => 135, 'zero_rpm' => true]
        );
        $this->crearPSU(
            comp: ['nombre' => 'Fractal Design Ion+ 3 850W Platinum', 'marca' => 'Fractal Design', 'modelo' => 'FD-P-IA2P-860-EU', 'descripcion' => 'PSU ATX 860W 80 Plus Platinum full modular. Ventilador de 140 mm con modo pasivo hasta 300W. Diseño premium con cables planos. Ideal para workstations silenciosas con RTX 4080.', 'imagen_url' => 'https://media.ldlc.com/r1600/ld/products/00/05/87/19/LD0005871936_1.jpg'],
            psu:  ['cert' => '80 Plus Platinum', 'tipo' => 'ATX', 'vatios' => 850, 'modular' => 'full_modular', 'version_atx' => '2.4', 'pcie16' => 0, 'pcie8' => 4, 'sata' => 12, 'molex' => 4, 'largo_mm' => 150, 'vent_mm' => 140, 'zero_rpm' => true]
        );
        $this->crearPSU(
            comp: ['nombre' => 'Gigabyte UD1000GM PG5', 'marca' => 'Gigabyte', 'modelo' => 'GP-UD1000GM PG5', 'descripcion' => 'PSU ATX 3.0 1000W 80 Plus Gold full modular con 12VHPWR nativo. La opción más asequible con soporte PCIe 5.0 en la gama de 1000W. Ventilador de 120 mm silencioso.', 'imagen_url' => 'https://elchapuzasinformatico.com/wp-content/uploads/2022/02/Gigabyte-UD1000GM-PG5-1.jpg'],
            psu:  ['cert' => '80 Plus Gold', 'tipo' => 'ATX 3.0', 'vatios' => 1000, 'modular' => 'full_modular', 'version_atx' => '3.0', 'pcie16' => 1, 'pcie8' => 4, 'sata' => 10, 'molex' => 4, 'largo_mm' => 150, 'vent_mm' => 120, 'zero_rpm' => true]
        );
        $this->crearPSU(
            comp: ['nombre' => 'Corsair HX1000i', 'marca' => 'Corsair', 'modelo' => 'CP-9020214-EU', 'descripcion' => 'PSU ATX 3.0 1000W 80 Plus Platinum full modular con telemetría digital vía iCUE. Conector 12VHPWR nativo. Ventilador de 140 mm zero-rpm. Opción monitoreada para RTX 4090/5090 con CPU 13900K o 7950X.', 'imagen_url' => 'https://media.game.es/COVERV2/3D_L/V1I/V1IOJL.png'],
            psu:  ['cert' => '80 Plus Platinum', 'tipo' => 'ATX 3.0', 'vatios' => 1000, 'modular' => 'full_modular', 'version_atx' => '3.0', 'pcie16' => 2, 'pcie8' => 4, 'sata' => 14, 'molex' => 4, 'largo_mm' => 160, 'vent_mm' => 140, 'zero_rpm' => true]
        );
        $this->crearPSU(
            comp: ['nombre' => 'Thermaltake Toughpower GF3 1200W', 'marca' => 'Thermaltake', 'modelo' => 'PS-TPD-1200FNFAGE-4', 'descripcion' => 'PSU ATX 3.0 1200W 80 Plus Gold full modular con 12VHPWR nativo. Ventilador de 140 mm con modo eco. Apta para RTX 5090 con margen en sistemas de alta gama.', 'imagen_url' => 'https://thermaltake.azureedge.net/pub/media/wysiwyg/key3/img/ToughpowerGF3/bg2_1200.jpg'],
            psu:  ['cert' => '80 Plus Gold', 'tipo' => 'ATX 3.0', 'vatios' => 1200, 'modular' => 'full_modular', 'version_atx' => '3.0', 'pcie16' => 2, 'pcie8' => 4, 'sata' => 12, 'molex' => 4, 'largo_mm' => 190, 'vent_mm' => 140, 'zero_rpm' => true]
        );
        $this->crearPSU(
            comp: ['nombre' => 'Seasonic Prime TX-1300', 'marca' => 'Seasonic', 'modelo' => 'SSR-1300TR', 'descripcion' => 'PSU ATX 1300W 80 Plus Titanium full modular. Ventilador de 135 mm totalmente pasivo hasta 600W. 12 años de garantía. Para RTX 5090 + 7950X3D o setups de workstation extrema.', 'imagen_url' => 'https://seasonic.com/wp-content/uploads/2025/10/4.webp'],
            psu:  ['cert' => '80 Plus Titanium', 'tipo' => 'ATX', 'vatios' => 1300, 'modular' => 'full_modular', 'version_atx' => '2.4', 'pcie16' => 0, 'pcie8' => 8, 'sata' => 14, 'molex' => 4, 'largo_mm' => 190, 'vent_mm' => 135, 'zero_rpm' => true]
        );
        $this->crearPSU(
            comp: ['nombre' => 'be quiet! Dark Power Pro 13 1600W', 'marca' => 'be quiet!', 'modelo' => 'BN362', 'descripcion' => 'PSU ATX 3.0 1600W 80 Plus Titanium full modular. Cuatro raíles +12V independientes, overclocking switch para fusionarlos. Dos 12VHPWR nativos. SilentWings 4 de 135 mm. Para RTX 5090 o workstations extremas.', 'imagen_url' => 'https://www.awd-it.co.uk/media/wysiwyg/dpp_1.jpg'],
            psu:  ['cert' => '80 Plus Titanium', 'tipo' => 'ATX 3.0', 'vatios' => 1600, 'modular' => 'full_modular', 'version_atx' => '3.0', 'pcie16' => 2, 'pcie8' => 8, 'sata' => 16, 'molex' => 6, 'largo_mm' => 220, 'vent_mm' => 135, 'zero_rpm' => true]
        );
        $this->crearPSU(
            comp: ['nombre' => 'Seasonic Focus SFX-L 650W', 'marca' => 'Seasonic', 'modelo' => 'SSR-650SFX', 'descripcion' => 'PSU SFX-L 650W 80 Plus Gold full modular. Ventilador de 120 mm FDB con modo semi-pasivo. Cables trenzados premium. Bracket ATX incluido. La SFX-L de referencia para builds ITX de gama media con GPU hasta RTX 4070.', 'imagen_url' => 'https://www.alternate.es/p/1200x630/t/Seasonic_FOCUS_SGX_650_unidad_de_fuente_de_alimentaci_n_650_W_20_4_pin_ATX_SFX_Negro__Fuente_de_alimentaci_n_de_PC@@tn6e9h_2.jpg'],
            psu:  ['cert' => '80 Plus Gold', 'tipo' => 'SFX-L', 'vatios' => 650, 'modular' => 'full_modular', 'version_atx' => '2.4', 'pcie16' => 0, 'pcie8' => 2, 'sata' => 8, 'molex' => 2, 'largo_mm' => 130, 'vent_mm' => 120, 'zero_rpm' => true]
        );
    }
    // ─────────────────────────────────────────────────────────────────────────
    // Refrigeracion
    // ─────────────────────────────────────────────────────────────────────────
    protected function crearRefrigeracionAire(array $comp, array $aire, array $sockets): void
    {
        $marcaId    = $this->marcas[$comp['marca']] ?? null;
        $fabId      = $this->marcas[$comp['fabricante'] ?? $comp['marca']] ?? $marcaId;
        $componente = Componente::create([
            'nombre'      => $comp['nombre'],
            'marca_id'    => $marcaId,
            'fabricante_id'=> $fabId,
            'categoria'   => 'refrigeracion_aire',
            'modelo'      => $comp['modelo'],
            'imagen_url' => $comp['imagen_url'] ?? null,
            'descripcion' => $comp['descripcion'] ?? null,
            'activo'      => true,
        ]);
        $ra = RefrigeracionAire::create([
            'componente_id'         => $componente->id,
            'tipo_refrigeracion_id' => $this->tiposRefrig['Aire'] ?? null,
            'tdp_max_watts'         => $aire['tdp_max_watts'],
            'altura_mm'             => $aire['altura_mm'],
            'ancho_mm'              => $aire['ancho_mm'],
            'profundidad_mm'        => $aire['profundidad_mm'],
            'num_ventiladores'      => $aire['num_ventiladores'],
            'tam_ventilador_mm'     => $aire['tam_ventilador_mm'],
            'rpm_min'               => $aire['rpm_min'],
            'rpm_max'               => $aire['rpm_max'],
            'ruido_db_min'          => $aire['ruido_db_min'],
            'ruido_db_max'          => $aire['ruido_db_max'],
            'num_heatpipes'         => $aire['num_heatpipes'],
            'incluye_pasta_termica' => $aire['incluye_pasta_termica'] ?? true,
            'tiene_rgb'             => $aire['tiene_rgb'] ?? false,
            'disipador_dual_torre'  => $aire['disipador_dual_torre'] ?? false,
        ]);
        // Sockets compatibles
        foreach ($sockets as $sockNombre) {
            $sid = $this->sockets[$sockNombre] ?? null;
            if ($sid) $ra->socketsCompatibles()->attach($sid);
        }
    }
    
    protected function crearRefrigeracionLiquida(array $comp, array $liq, array $sockets): void
    {
        $marcaId    = $this->marcas[$comp['marca']] ?? null;
        $fabId      = $this->marcas[$comp['fabricante'] ?? $comp['marca']] ?? $marcaId;
        $componente = Componente::create([
            'nombre'       => $comp['nombre'],
            'marca_id'     => $marcaId,
            'fabricante_id'=> $fabId,
            'categoria'    => 'refrigeracion_liquida',
            'modelo'       => $comp['modelo'],
            'imagen_url' => $comp['imagen_url'] ?? null,
            'descripcion'  => $comp['descripcion'] ?? null,
            'activo'       => true,
        ]);
        $rl = RefrigeracionLiquida::create([
            'componente_id'         => $componente->id,
            'tipo_refrigeracion_id' => $this->tiposRefrig['Líquida AIO'] ?? null,
            'tdp_max_watts'         => $liq['tdp_max_watts'],
            'tam_radiador_mm'       => $liq['tam_radiador_mm'],
            'ancho_radiador_mm'     => $liq['ancho_radiador_mm'],
            'alto_radiador_mm'      => $liq['alto_radiador_mm'],
            'grosor_radiador_mm'    => $liq['grosor_radiador_mm'],
            'altura_bomba_mm'       => $liq['altura_bomba_mm'],
            'ancho_bomba_mm'        => $liq['ancho_bomba_mm'],
            'profundidad_bomba_mm'  => $liq['profundidad_bomba_mm'],
            'pantalla_cabezal'      => $liq['pantalla_cabezal'] ?? false,
            'num_ventiladores'      => $liq['num_ventiladores'],
            'tam_ventilador_mm'     => $liq['tam_ventilador_mm'],
            'rpm_min'               => $liq['rpm_min'],
            'rpm_max'               => $liq['rpm_max'],
            'ruido_db_min'          => $liq['ruido_db_min'],
            'ruido_db_max'          => $liq['ruido_db_max'],
            'flujo_personalizable'  => $liq['flujo_personalizable'] ?? false,
            'incluye_pasta_termica' => $liq['incluye_pasta_termica'] ?? true,
            'tiene_rgb'             => $liq['tiene_rgb'] ?? false,
        ]);
        foreach ($sockets as $sockNombre) {
            $sid = $this->sockets[$sockNombre] ?? null;
            if ($sid) $rl->socketsCompatibles()->attach($sid);
        }
    }

    //  REFRIGERACIÓN POR AIRE
    protected function seedRefrigeracionesAire(): void
    {
        $this->crearRefrigeracionAire(
            comp: ['nombre' => 'DeepCool Assassin IV', 'marca' => 'DeepCool', 'modelo' => 'R-ASN4-BKNNMT-G', 'descripcion' => 'Disipador dual torre 280W TDP con 8 heatpipes Ø6 mm. Dos ventiladores FT14 S de 140 mm. Altura 165 mm. RGB opcional mediante adaptador. Compatible con LGA1700, LGA1851, AM4 y AM5.', 'imagen_url' => 'https://cdn.wccftech.com/wp-content/uploads/2023/01/DeepCool-Assassin-IV-CPU-Cooler.png'],
            aire: ['tdp_max_watts' => 280, 'altura_mm' => 165, 'ancho_mm' => 147, 'profundidad_mm' => 136, 'num_ventiladores' => 2, 'tam_ventilador_mm' => 140, 'rpm_min' => 300, 'rpm_max' => 1350, 'ruido_db_min' => 17.0, 'ruido_db_max' => 28.0, 'num_heatpipes' => 8, 'incluye_pasta_termica' => true, 'tiene_rgb' => false, 'disipador_dual_torre' => true],
            sockets: ['AM4', 'AM5', 'LGA1700', 'LGA1851']
        );
        $this->crearRefrigeracionAire(
            comp: ['nombre' => 'DeepCool AK620 G2', 'marca' => 'DeepCool', 'modelo' => 'R-AK620-BKNNMT-G', 'descripcion' => 'Disipador dual torre 260W TDP con 6 heatpipes Ø6 mm niquelados. Dos ventiladores FK120 de 120 mm. Altura 160 mm. Excelente relación calidad-precio en su categoría.', 'imagen_url' => 'https://hyperpc.kz/images/catalog/hardware/cooling/deepcool/ak620/deepcool-ak620-zero-dark.jpg'],
            aire: ['tdp_max_watts' => 260, 'altura_mm' => 160, 'ancho_mm' => 129, 'profundidad_mm' => 101, 'num_ventiladores' => 2, 'tam_ventilador_mm' => 120, 'rpm_min' => 500, 'rpm_max' => 1850, 'ruido_db_min' => 17.6, 'ruido_db_max' => 32.0, 'num_heatpipes' => 6, 'incluye_pasta_termica' => true, 'tiene_rgb' => false, 'disipador_dual_torre' => true],
            sockets: ['AM4', 'AM5', 'LGA1700', 'LGA1851']
        );
        $this->crearRefrigeracionAire(
            comp: ['nombre' => 'Thermalright Phantom Spirit 120 EVO', 'marca' => 'Thermalright', 'modelo' => 'PHANTOM-SPIRIT-120-EVO', 'descripcion' => 'Disipador dual torre 250W TDP con 6 heatpipes de cobre. Ventilador TL-C12 Pro de 120 mm incluido (se recomienda un segundo). Altura 158 mm. Almohadilla de pasta incluida.', 'imagen_url' => 'https://pricespy-75b8.kxcdn.com/product/standard/280/15509921.jpg'],
            aire: ['tdp_max_watts' => 250, 'altura_mm' => 158, 'ancho_mm' => 125, 'profundidad_mm' => 75, 'num_ventiladores' => 1, 'tam_ventilador_mm' => 120, 'rpm_min' => 300, 'rpm_max' => 1550, 'ruido_db_min' => 15.0, 'ruido_db_max' => 26.0, 'num_heatpipes' => 6, 'incluye_pasta_termica' => true, 'tiene_rgb' => false, 'disipador_dual_torre' => true],
            sockets: ['AM4', 'AM5', 'LGA1700', 'LGA1851']
        );
        $this->crearRefrigeracionAire(
            comp: ['nombre' => 'Noctua NH-D15', 'marca' => 'Noctua', 'modelo' => 'NH-D15', 'descripcion' => 'Disipador dual torre flagship de Noctua. 6 heatpipes Ø6 mm. Dos ventiladores NF-A15 de 140 mm. Altura 165 mm. Referencia absoluta en refrigeración por aire silenciosa. Compatible con LGA1700/AM5 mediante kit SecuFirm2+.', 'imagen_url' => 'https://elchapuzasinformatico.com/wp-content/uploads/2024/07/Noctua-NH-D15-G2.jpg'],
            aire: ['tdp_max_watts' => 250, 'altura_mm' => 165, 'ancho_mm' => 150, 'profundidad_mm' => 135, 'num_ventiladores' => 2, 'tam_ventilador_mm' => 140, 'rpm_min' => 300, 'rpm_max' => 1500, 'ruido_db_min' => 19.2, 'ruido_db_max' => 24.6, 'num_heatpipes' => 6, 'incluye_pasta_termica' => true, 'tiene_rgb' => false, 'disipador_dual_torre' => true],
            sockets: ['AM4', 'AM5', 'LGA1700', 'LGA1851']
        );
        $this->crearRefrigeracionAire(
            comp: ['nombre' => 'Noctua NH-U12S redux', 'marca' => 'Noctua', 'modelo' => 'NH-U12S-redux', 'descripcion' => 'Torre simple 158W TDP con 5 heatpipes. Ventilador NF-P12 redux de 120 mm. Altura 158 mm. Edición económica de Noctua con el mismo sistema de montaje SecuFirm2. Ideal para plataformas con espacio limitado para RAM.', 'imagen_url' => 'https://m.media-amazon.com/images/S/aplus-media-library-service-media/a4b7648a-4837-4c85-a75d-aa3dcd7ed04b.__CR0,0,220,220_PT0_SX220_V1___.jpg'],
            aire: ['tdp_max_watts' => 158, 'altura_mm' => 158, 'ancho_mm' => 125, 'profundidad_mm' => 71, 'num_ventiladores' => 1, 'tam_ventilador_mm' => 120, 'rpm_min' => 450, 'rpm_max' => 1200, 'ruido_db_min' => 16.8, 'ruido_db_max' => 22.4, 'num_heatpipes' => 5, 'incluye_pasta_termica' => true, 'tiene_rgb' => false, 'disipador_dual_torre' => false],
            sockets: ['AM4', 'AM5', 'LGA1700', 'LGA1851']
        );
        $this->crearRefrigeracionAire(
            comp: ['nombre' => 'Noctua NH-D15S', 'marca' => 'Noctua', 'modelo' => 'NH-D15S', 'descripcion' => 'Variante asimétrica del NH-D15 con un único ventilador NF-A15 de 140 mm. Altura 165 mm. Deja libre el slot PCIe más cercano y las ranuras de RAM. 240W TDP. Incluye kit LGA1700.', 'imagen_url' => 'https://www.trippodo.com/668831-medium_default/noctua-nh-d15s-ventilador-de-pc-procesador-enfriador-14-cm-cobre-metalico.jpg'],
            aire: ['tdp_max_watts' => 240, 'altura_mm' => 165, 'ancho_mm' => 150, 'profundidad_mm' => 135, 'num_ventiladores' => 1, 'tam_ventilador_mm' => 140, 'rpm_min' => 300, 'rpm_max' => 1500, 'ruido_db_min' => 19.2, 'ruido_db_max' => 24.6, 'num_heatpipes' => 6, 'incluye_pasta_termica' => true, 'tiene_rgb' => false, 'disipador_dual_torre' => true],
            sockets: ['AM4', 'AM5', 'LGA1700', 'LGA1851']
        );
        $this->crearRefrigeracionAire(
            comp: ['nombre' => 'Noctua NH-L12S', 'marca' => 'Noctua', 'modelo' => 'NH-L12S', 'descripcion' => 'Disipador low profile 70 mm de altura con ventilador superior NF-A12×15 de 120×15 mm. 5 heatpipes. TDP 70W (hasta 95W con ventilador 120 mm adicional). Ideal para gabinetes SFF como Dan A4.', 'imagen_url' => 'https://www.dateks.lv/images/pic/2400/2400/507/883.jpg'],
            aire: ['tdp_max_watts' => 95, 'altura_mm' => 70, 'ancho_mm' => 128, 'profundidad_mm' => 112, 'num_ventiladores' => 1, 'tam_ventilador_mm' => 120, 'rpm_min' => 300, 'rpm_max' => 1250, 'ruido_db_min' => 14.8, 'ruido_db_max' => 22.4, 'num_heatpipes' => 5, 'incluye_pasta_termica' => true, 'tiene_rgb' => false, 'disipador_dual_torre' => false],
            sockets: ['AM4', 'AM5', 'LGA1700', 'LGA1851']
        );
        $this->crearRefrigeracionAire(
            comp: ['nombre' => 'Arctic Freezer 36', 'marca' => 'Arctic', 'modelo' => 'ACFRE00123A', 'descripcion' => 'Torre simple 210W TDP con 4 heatpipes de contacto directo. Ventilador P12 Max de 120 mm (3300 rpm). Altura 157 mm. Soporte PWM Sharing Technology. Opción económica con rendimiento sólido.', 'imagen_url' => 'https://www.electroprecio.com/media/catalog/product/cache/1/thumbnail/600x400/9df78eab33525d08d6e5fb8d27136e95/6/a/6a4130e8e72b32bbe3c2382a546ef620a8ee446a.jpg.jpg'],
            aire: ['tdp_max_watts' => 210, 'altura_mm' => 157, 'ancho_mm' => 123, 'profundidad_mm' => 76, 'num_ventiladores' => 1, 'tam_ventilador_mm' => 120, 'rpm_min' => 200, 'rpm_max' => 3300, 'ruido_db_min' => 0.5, 'ruido_db_max' => 36.0, 'num_heatpipes' => 4, 'incluye_pasta_termica' => true, 'tiene_rgb' => false, 'disipador_dual_torre' => false],
            sockets: ['AM4', 'AM5', 'LGA1700', 'LGA1851']
        );
        $this->crearRefrigeracionAire(
            comp: ['nombre' => 'be quiet! Dark Rock Pro 5', 'marca' => 'be quiet!', 'modelo' => 'BK036', 'descripcion' => 'Disipador dual torre silencioso 270W TDP. 7 heatpipes de cobre con recubrimiento negro. Dos ventiladores Silent Wings 4 de 120 y 135 mm. Altura 162 mm. Compatible con RAM de hasta 54 mm.', 'imagen_url' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTqbYECDBMpOnjtS2r0fnYUgym3S4-bYo8KcA&s'],
            aire: ['tdp_max_watts' => 270, 'altura_mm' => 162, 'ancho_mm' => 136, 'profundidad_mm' => 145, 'num_ventiladores' => 2, 'tam_ventilador_mm' => 120, 'rpm_min' => 300, 'rpm_max' => 1500, 'ruido_db_min' => 12.8, 'ruido_db_max' => 24.4, 'num_heatpipes' => 7, 'incluye_pasta_termica' => true, 'tiene_rgb' => false, 'disipador_dual_torre' => true],
            sockets: ['AM4', 'AM5', 'LGA1700', 'LGA1851']
        );
        $this->crearRefrigeracionAire(
            comp: ['nombre' => 'Thermalright Peerless Assassin 120 SE', 'marca' => 'Thermalright', 'modelo' => 'PA120-SE', 'descripcion' => 'Disipador dual torre 260W TDP. 6 heatpipes. Dos ventiladores TL-C12 Pro de 120 mm. Altura 155 mm. La opción más barata en su categoría de rendimiento. Referencia en relación calidad-precio.', 'imagen_url' => 'https://computerlounge.co.nz/cdn/shop/files/e86d644396599307dbd670e3c8dfeec197682e07_68133_1.jpg?v=1737004259&width=1200'],
            aire: ['tdp_max_watts' => 260, 'altura_mm' => 155, 'ancho_mm' => 123, 'profundidad_mm' => 104, 'num_ventiladores' => 2, 'tam_ventilador_mm' => 120, 'rpm_min' => 300, 'rpm_max' => 1550, 'ruido_db_min' => 15.0, 'ruido_db_max' => 26.0, 'num_heatpipes' => 6, 'incluye_pasta_termica' => true, 'tiene_rgb' => false, 'disipador_dual_torre' => true],
            sockets: ['AM4', 'AM5', 'LGA1700', 'LGA1851']
        );
        $this->crearRefrigeracionAire(
            comp: ['nombre' => 'Noctua NH-L9i-17xx', 'marca' => 'Noctua', 'modelo' => 'NH-L9i-17xx', 'descripcion' => 'Disipador ultra low profile 37 mm específico para Intel LGA1700/1851. 2 heatpipes. Ventilador NF-A9×14 de 92×14 mm. TDP 50W. El más compacto de Noctua para builds NUC o mini-ITX con restricción de altura.', 'imagen_url' => 'https://www.asusbymacman.es/14102-large_default/noctua-nh-l9i-17xx-disipador-cpu.jpg'],
            aire: ['tdp_max_watts' => 50, 'altura_mm' => 37, 'ancho_mm' => 114, 'profundidad_mm' => 92, 'num_ventiladores' => 1, 'tam_ventilador_mm' => 92, 'rpm_min' => 300, 'rpm_max' => 2500, 'ruido_db_min' => 14.8, 'ruido_db_max' => 23.6, 'num_heatpipes' => 2, 'incluye_pasta_termica' => true, 'tiene_rgb' => false, 'disipador_dual_torre' => false],
            sockets: ['LGA1700', 'LGA1851']
        );
    }
    
    //  REFRIGERACIÓN LÍQUIDA AIO 
    protected function seedRefrigeracionesLiquidas(): void
    {
        $this->crearRefrigeracionLiquida(
            comp: ['nombre' => 'Arctic Liquid Freezer III Pro 240', 'marca' => 'Arctic', 'modelo' => 'ACFRE00134A', 'descripcion' => 'AIO 240 mm con bomba integrada en el radiador (velocidad variable vía PWM). Dos ventiladores P12 Max de 120 mm. Bomba 0.8 MPa. MX-6 incluida. Sin RGB. Compatible AM5 y LGA1851 de serie.', 'imagen_url' => 'https://thumb.pccomponentes.com/w-530-530/articles/1093/10933298/6816-refrigeracion-liquida-arctic-liquid-freezer-iii-pro-240-2x120mm-fdb-131-m3-h-negro-721b90d9-cd25-416e-bf03-af405fb93c52.jpg'],
            liq: ['tdp_max_watts' => 250, 'tam_radiador_mm' => 240, 'ancho_radiador_mm' => 120, 'alto_radiador_mm' => 240, 'grosor_radiador_mm' => 38, 'altura_bomba_mm' => 53, 'ancho_bomba_mm' => 53, 'profundidad_bomba_mm' => 43, 'pantalla_cabezal' => false, 'num_ventiladores' => 2, 'tam_ventilador_mm' => 120, 'rpm_min' => 200, 'rpm_max' => 3000, 'ruido_db_min' => 0.5, 'ruido_db_max' => 37.5, 'flujo_personalizable' => false, 'incluye_pasta_termica' => true, 'tiene_rgb' => false],
            sockets: ['AM4', 'AM5', 'LGA1700', 'LGA1851']
        );
        $this->crearRefrigeracionLiquida(
            comp: ['nombre' => 'NZXT Kraken 240', 'marca' => 'NZXT', 'modelo' => 'RL-KN240-B1', 'descripcion' => 'AIO 240 mm con cabezal circular de 60 mm con pantalla LCD de 1.54" y anillo ARGB. Dos ventiladores F120 RGB Core de 120 mm. Tuberías nylon trenzado. Diseño icónico NZXT.', 'imagen_url' => 'https://m.media-amazon.com/images/I/61oM5XN5T3L.jpg'],
            liq: ['tdp_max_watts' => 250, 'tam_radiador_mm' => 240, 'ancho_radiador_mm' => 120, 'alto_radiador_mm' => 240, 'grosor_radiador_mm' => 30, 'altura_bomba_mm' => 78, 'ancho_bomba_mm' => 78, 'profundidad_bomba_mm' => 52, 'pantalla_cabezal' => true, 'num_ventiladores' => 2, 'tam_ventilador_mm' => 120, 'rpm_min' => 500, 'rpm_max' => 1800, 'ruido_db_min' => 21.0, 'ruido_db_max' => 33.0, 'flujo_personalizable' => false, 'incluye_pasta_termica' => true, 'tiene_rgb' => true],
            sockets: ['AM4', 'AM5', 'LGA1700', 'LGA1851']
        );
        $this->crearRefrigeracionLiquida(
            comp: ['nombre' => 'DeepCool LT240', 'marca' => 'DeepCool', 'modelo' => 'R-LT240-WHAMNT-G', 'descripcion' => 'AIO 240 mm con cabezal ARGB rotativo 360°. Dos ventiladores FK120 de 120 mm. Radiador aluminio 27 mm grosor. Sistema anti-leaks. Buena relación calidad-precio.', 'imagen_url' => 'https://s3.e2e4.ru/imgproxy/3711552'],
            liq: ['tdp_max_watts' => 220, 'tam_radiador_mm' => 240, 'ancho_radiador_mm' => 120, 'alto_radiador_mm' => 240, 'grosor_radiador_mm' => 27, 'altura_bomba_mm' => 68, 'ancho_bomba_mm' => 68, 'profundidad_bomba_mm' => 52, 'pantalla_cabezal' => false, 'num_ventiladores' => 2, 'tam_ventilador_mm' => 120, 'rpm_min' => 500, 'rpm_max' => 1850, 'ruido_db_min' => 17.6, 'ruido_db_max' => 32.0, 'flujo_personalizable' => false, 'incluye_pasta_termica' => true, 'tiene_rgb' => true],
            sockets: ['AM4', 'AM5', 'LGA1700', 'LGA1851']
        );
        $this->crearRefrigeracionLiquida(
            comp: ['nombre' => 'Arctic Liquid Freezer III Pro 280', 'marca' => 'Arctic', 'modelo' => 'ACFRE00136A', 'descripcion' => 'AIO 280 mm con bomba integrada en radiador controlada por PWM. Dos ventiladores P14 Max de 140 mm (hasta 3000 rpm). Rendimiento excepcional para su precio. Sin RGB.', 'imagen_url' => 'https://assetsio.gnwcdn.com/arctic-liquid-freezer-iii-240-df-deal.jpg?width=1600&height=900&fit=crop&quality=100&format=png&enable=upscale&auto=webp'],
            liq: ['tdp_max_watts' => 300, 'tam_radiador_mm' => 280, 'ancho_radiador_mm' => 140, 'alto_radiador_mm' => 280, 'grosor_radiador_mm' => 38, 'altura_bomba_mm' => 53, 'ancho_bomba_mm' => 53, 'profundidad_bomba_mm' => 43, 'pantalla_cabezal' => false, 'num_ventiladores' => 2, 'tam_ventilador_mm' => 140, 'rpm_min' => 200, 'rpm_max' => 3000, 'ruido_db_min' => 0.5, 'ruido_db_max' => 38.0, 'flujo_personalizable' => false, 'incluye_pasta_termica' => true, 'tiene_rgb' => false],
            sockets: ['AM4', 'AM5', 'LGA1700', 'LGA1851']
        );
        $this->crearRefrigeracionLiquida(
            comp: ['nombre' => 'NZXT Kraken Elite 280', 'marca' => 'NZXT', 'modelo' => 'RL-KE280-B1', 'descripcion' => 'AIO 280 mm premium con pantalla LCD 2.36" en el cabezal. Dos ventiladores F140 RGB de 140 mm. Tuberías de nylon trenzado 400 mm. CAM software con curvas personalizables. Máxima personalización visual.', 'imagen_url' => 'https://www.asusbymacman.es/49905-large_default/nzxt-kraken-elite-280-rgb-black-refrigeracion-liquida.jpg'],
            liq: ['tdp_max_watts' => 300, 'tam_radiador_mm' => 280, 'ancho_radiador_mm' => 140, 'alto_radiador_mm' => 280, 'grosor_radiador_mm' => 30, 'altura_bomba_mm' => 78, 'ancho_bomba_mm' => 78, 'profundidad_bomba_mm' => 52, 'pantalla_cabezal' => true, 'num_ventiladores' => 2, 'tam_ventilador_mm' => 140, 'rpm_min' => 500, 'rpm_max' => 1500, 'ruido_db_min' => 21.0, 'ruido_db_max' => 36.0, 'flujo_personalizable' => false, 'incluye_pasta_termica' => true, 'tiene_rgb' => true],
            sockets: ['AM4', 'AM5', 'LGA1700', 'LGA1851']
        );
        $this->crearRefrigeracionLiquida(
            comp: ['nombre' => 'Arctic Liquid Freezer III Pro 360', 'marca' => 'Arctic', 'modelo' => 'ACFRE00138A', 'descripcion' => 'AIO 360 mm con bomba integrada en radiador PWM. Tres ventiladores P12 Max de 120 mm. Radiador 38 mm de grosor. La mejor opción sin RGB para CPUs de alto TDP (Ryzen 9 9950X, Core i9-14900K).', 'imagen_url' => 'https://elitehubs.com/cdn/shop/files/Liquid_Freezer_III_360_ARGB_Black_G02_2_result.jpg?v=1721029670&width=533'],
            liq: ['tdp_max_watts' => 350, 'tam_radiador_mm' => 360, 'ancho_radiador_mm' => 120, 'alto_radiador_mm' => 360, 'grosor_radiador_mm' => 38, 'altura_bomba_mm' => 53, 'ancho_bomba_mm' => 53, 'profundidad_bomba_mm' => 43, 'pantalla_cabezal' => false, 'num_ventiladores' => 3, 'tam_ventilador_mm' => 120, 'rpm_min' => 200, 'rpm_max' => 3000, 'ruido_db_min' => 0.5, 'ruido_db_max' => 37.5, 'flujo_personalizable' => false, 'incluye_pasta_termica' => true, 'tiene_rgb' => false],
            sockets: ['AM4', 'AM5', 'LGA1700', 'LGA1851']
        );
        $this->crearRefrigeracionLiquida(
            comp: ['nombre' => 'NZXT Kraken Elite 360', 'marca' => 'NZXT', 'modelo' => 'RL-KE360-B1', 'descripcion' => 'AIO 360 mm con pantalla LCD 2.36" en el cabezal. Tres ventiladores F120 RGB Core de 120 mm. Tuberías de nylon 400 mm. La opción de gama alta de NZXT para builds de alto rendimiento con estética premium.', 'imagen_url' => 'https://img.pccomponentes.com/articles/1087/10870514/132-nzxt-kraken-elite-360-kit-refrigeracion-liquida-con-pantalla-ips-360mm-negro.jpg'],
            liq: ['tdp_max_watts' => 350, 'tam_radiador_mm' => 360, 'ancho_radiador_mm' => 120, 'alto_radiador_mm' => 360, 'grosor_radiador_mm' => 30, 'altura_bomba_mm' => 78, 'ancho_bomba_mm' => 78, 'profundidad_bomba_mm' => 52, 'pantalla_cabezal' => true, 'num_ventiladores' => 3, 'tam_ventilador_mm' => 120, 'rpm_min' => 500, 'rpm_max' => 1800, 'ruido_db_min' => 21.0, 'ruido_db_max' => 33.0, 'flujo_personalizable' => false, 'incluye_pasta_termica' => true, 'tiene_rgb' => true],
            sockets: ['AM4', 'AM5', 'LGA1700', 'LGA1851']
        );
        $this->crearRefrigeracionLiquida(
            comp: ['nombre' => 'Arctic Liquid Freezer III Pro 420', 'marca' => 'Arctic', 'modelo' => 'ACFRE00140A', 'descripcion' => 'AIO 420 mm con bomba integrada en radiador. Tres ventiladores P14 Max de 140 mm (hasta 3000 rpm). Radiador 38 mm de grosor. La AIO de mayor superficie de disipación del mercado por menos de 150€.', 'imagen_url' => 'https://assetsio.gnwcdn.com/arctic-liquid-freezer-420-df-deal.jpg?width=690&quality=85&format=jpg&dpr=3&auto=webp'],
            liq: ['tdp_max_watts' => 400, 'tam_radiador_mm' => 420, 'ancho_radiador_mm' => 140, 'alto_radiador_mm' => 420, 'grosor_radiador_mm' => 38, 'altura_bomba_mm' => 53, 'ancho_bomba_mm' => 53, 'profundidad_bomba_mm' => 43, 'pantalla_cabezal' => false, 'num_ventiladores' => 3, 'tam_ventilador_mm' => 140, 'rpm_min' => 200, 'rpm_max' => 3000, 'ruido_db_min' => 0.5, 'ruido_db_max' => 38.0, 'flujo_personalizable' => false, 'incluye_pasta_termica' => true, 'tiene_rgb' => false],
            sockets: ['AM4', 'AM5', 'LGA1700', 'LGA1851']
        );
        $this->crearRefrigeracionLiquida(
            comp: ['nombre' => 'NZXT Kraken Elite 420', 'marca' => 'NZXT', 'modelo' => 'RL-KE420-B1', 'descripcion' => 'AIO 420 mm con pantalla LCD 2.36" en el cabezal. Tres ventiladores F140 RGB Core de 140 mm. Tuberías nylon 400 mm. El AIO de gama más alta de NZXT para Full Tower con máxima disipación.', 'imagen_url' => 'https://pcbox.vtexassets.com/arquivos/ids/3091859-853-853/RL-KR42E-W2_GAL_5.jpg?v=638847072675200000'],
            liq: ['tdp_max_watts' => 400, 'tam_radiador_mm' => 420, 'ancho_radiador_mm' => 140, 'alto_radiador_mm' => 420, 'grosor_radiador_mm' => 30, 'altura_bomba_mm' => 78, 'ancho_bomba_mm' => 78, 'profundidad_bomba_mm' => 52, 'pantalla_cabezal' => true, 'num_ventiladores' => 3, 'tam_ventilador_mm' => 140, 'rpm_min' => 500, 'rpm_max' => 1500, 'ruido_db_min' => 21.0, 'ruido_db_max' => 36.0, 'flujo_personalizable' => false, 'incluye_pasta_termica' => true, 'tiene_rgb' => true],
            sockets: ['AM4', 'AM5', 'LGA1700', 'LGA1851']
        );
    }

    // ── Ventiladores ──────────────────────────────────────────────────────────
    protected function seedVentiladores(): void
    {
        $this->crearVentilador(
            comp: ['nombre' => 'Noctua NF-F12 PWM', 'marca' => 'Noctua', 'modelo' => 'NF-F12 PWM', 'descripcion' => 'Ventilador 120 mm de referencia en silencio. Diseño Focused Flow con 7 palas asimétricas. Conector PWM 4 pines con ULNA incluido. El favorito para radiadores y disipadores de alta densidad.', 'imagen_url' => 'https://www.sts-tutorial.com/assets/images/content/review/noctua-nf-f12-industrial-3000/extra-1.jpg'],
            vent: ['tipo' => 'Normal', 'rpm_min' => 300, 'rpm_max' => 1500, 'ruido_db_min' => 10.8, 'ruido_db_max' => 22.4, 'flujo_aire_cfm' => 54.97, 'static_pressure_mmh2o' => 2.61, 'num_ventiladores' => 1, 'tiene_rgb' => false, 'pwm' => true, 'tam_mm' => 120]
        );
        $this->crearVentilador(
            comp: ['nombre' => 'Noctua NF-A14 PWM', 'marca' => 'Noctua', 'modelo' => 'NF-A14 PWM', 'descripcion' => 'Ventilador 140 mm de alto rendimiento con diseño AAO (Advanced Acoustic Optimisation). 9 palas con perfil de borde de ataque ondulado. Ideal para admisión/extracción en gabinetes o radiadores 140/280/420 mm.', 'imagen_url' => 'https://www.trippodo.com/818644-large_default/noctua-nf-a14-industrialppc-3000-pwm-carcasa-del-o.jpg'],
            vent: ['tipo' => 'Normal', 'rpm_min' => 300, 'rpm_max' => 1500, 'ruido_db_min' => 10.1, 'ruido_db_max' => 24.6, 'flujo_aire_cfm' => 82.52, 'static_pressure_mmh2o' => 2.37, 'num_ventiladores' => 1, 'tiene_rgb' => false, 'pwm' => true, 'tam_mm' => 140]
        );
        $this->crearVentilador(
            comp: ['nombre' => 'Arctic P12 PWM PST Value Pack (x5)', 'marca' => 'Arctic', 'modelo' => 'ACFAN00133A', 'descripcion' => 'Pack de 5 ventiladores 120 mm orientados a presión estática. Conector PST para encadenar hasta 5 unidades en 1 cabezal PWM. Rodamiento de fluido dinámico. La mejor relación calidad-precio del mercado para llenar un gabinete o radiadores.', 'imagen_url' => 'https://m.media-amazon.com/images/I/71Q3f5N4OZL.jpg'],
            vent: ['tipo' => 'Normal', 'rpm_min' => 200, 'rpm_max' => 1800, 'ruido_db_min' => 0.3, 'ruido_db_max' => 25.0, 'flujo_aire_cfm' => 56.30, 'static_pressure_mmh2o' => 2.20, 'num_ventiladores' => 5, 'tiene_rgb' => false, 'pwm' => true, 'tam_mm' => 120]
        );
        $this->crearVentilador(
            comp: ['nombre' => 'Arctic P14 PWM PST Value Pack (x5)', 'marca' => 'Arctic', 'modelo' => 'ACFAN00138A', 'descripcion' => 'Pack de 5 ventiladores 140 mm de presión estática alta. Misma lógica PST que el P12 pero en formato 140 mm. Perfecto para radiadores 280/420 mm o gabinetes que acepten 140. Caudal superior al P12.', 'imagen_url' => 'https://images.kupujemprodajem.com/photos/oglasi/9/58/166049589/big-166049589_6625647bcc2e44-728880071.webp'],
            vent: ['tipo' => 'Normal', 'rpm_min' => 200, 'rpm_max' => 1700, 'ruido_db_min' => 0.5, 'ruido_db_max' => 24.0, 'flujo_aire_cfm' => 68.10, 'static_pressure_mmh2o' => 1.90, 'num_ventiladores' => 5, 'tiene_rgb' => false, 'pwm' => true, 'tam_mm' => 140]
        );
        $this->crearVentilador(
            comp: ['nombre' => 'Lian Li UNI FAN SL120 RGB (x3)', 'marca' => 'Lian Li', 'modelo' => 'UF-SL120-3B', 'descripcion' => 'Pack de 3 ventiladores 120 mm daisy-chain con iluminación ARGB de 16 LEDs por cara. Los ventiladores se encadenan entre sí con un único conector al controlador. Hub incluido. Compatible con L-Connect 3.', 'imagen_url' => 'https://media.ldlc.com/r1600/ld/products/00/06/18/49/LD0006184917.jpg'],
            vent: ['tipo' => 'Normal', 'rpm_min' => 800, 'rpm_max' => 1900, 'ruido_db_min' => 21.7, 'ruido_db_max' => 32.8, 'flujo_aire_cfm' => 61.18, 'static_pressure_mmh2o' => 2.10, 'num_ventiladores' => 3, 'tiene_rgb' => true, 'pwm' => true, 'tam_mm' => 120]
        );
        $this->crearVentilador(
            comp: ['nombre' => 'Lian Li UNI FAN SL140 RGB (x2)', 'marca' => 'Lian Li', 'modelo' => 'UF-SL140-2B', 'descripcion' => 'Pack de 2 ventiladores 140 mm daisy-chain ARGB. Mayor caudal que el SL120 a menor ruido. Ideal para radiadores 420 mm o como fans de case en lateral o techo.', 'imagen_url' => 'https://m.media-amazon.com/images/I/513N9GYxJnL.jpg'],
            vent: ['tipo' => 'Normal', 'rpm_min' => 700, 'rpm_max' => 1600, 'ruido_db_min' => 19.5, 'ruido_db_max' => 30.5, 'flujo_aire_cfm' => 78.40, 'static_pressure_mmh2o' => 1.86, 'num_ventiladores' => 2, 'tiene_rgb' => true, 'pwm' => true, 'tam_mm' => 140]
        );
        $this->crearVentilador(
            comp: ['nombre' => 'Noctua NF-A12x25 PWM', 'marca' => 'Noctua', 'modelo' => 'NF-A12x25 PWM', 'descripcion' => 'El ventilador 120 mm con mejor rendimiento absoluto según benchmarks independientes. Carcasa con tolerancias de 0.5 mm, rodamiento SSO2, 7 palas Stealth Blade. Estándar de la industria para AIOs y disipadores de alta gama.', 'imagen_url' => 'https://cdn.ibertronica.es/product/VENNF-A12X25PWM_00001.jpeg'],
            vent: ['tipo' => 'Normal', 'rpm_min' => 450, 'rpm_max' => 2000, 'ruido_db_min' => 12.8, 'ruido_db_max' => 22.6, 'flujo_aire_cfm' => 60.09, 'static_pressure_mmh2o' => 2.34, 'num_ventiladores' => 1, 'tiene_rgb' => false, 'pwm' => true, 'tam_mm' => 120]
        );
        $this->crearVentilador(
            comp: ['nombre' => 'be quiet! Silent Wings 4 140mm PWM', 'marca' => 'be quiet!', 'modelo' => 'BL094', 'descripcion' => 'Ventilador 140 mm Silent Wings 4ª generación. Rodamiento de fluido de larga duración, motor sin escobillas de 6 polos. 11 palas aerodinámicas. Referencia de be quiet! para silencio máximo en case fans 140 mm.', 'imagen_url' => 'https://www.custompc.com/wp-content/sites/custompc/2023/06/be-quiet-Silent-Wings-4-140mm-550x309.jpg'],
            vent: ['tipo' => 'Normal', 'rpm_min' => 200, 'rpm_max' => 1400, 'ruido_db_min' => 8.9, 'ruido_db_max' => 24.4, 'flujo_aire_cfm' => 78.10, 'static_pressure_mmh2o' => 1.87, 'num_ventiladores' => 1, 'tiene_rgb' => false, 'pwm' => true, 'tam_mm' => 140]
        );
        $this->crearVentilador(
            comp: ['nombre' => 'Noctua NF-A12x15 PWM', 'marca' => 'Noctua', 'modelo' => 'NF-A12x15 PWM', 'descripcion' => 'Ventilador 120 mm slim de 15 mm de grosor para refrigeraciones de bajo perfil y espacios reducidos. 7 palas Stealth Blade, rodamiento SSO2. Incluye ULNA. Compatible con disipadores ITX como el NH-L9a.', 'imagen_url' => 'https://www.worten.es/i/df77fe1cab892c143897e39b1bc98b4eefeeb0af'],
            vent: ['tipo' => 'Low Profile', 'rpm_min' => 300, 'rpm_max' => 1850, 'ruido_db_min' => 11.3, 'ruido_db_max' => 23.9, 'flujo_aire_cfm' => 35.84, 'static_pressure_mmh2o' => 1.42, 'num_ventiladores' => 1, 'tiene_rgb' => false, 'pwm' => true, 'tam_mm' => 120]
        );
        $this->crearVentilador(
            comp: ['nombre' => 'Thermalright TL-C12 Pro ARGB (x3)', 'marca' => 'Thermalright', 'modelo' => 'TL-C12 Pro', 'descripcion' => 'Pack de 3 ventiladores 120 mm con ARGB y excelente relación precio-rendimiento. 9 palas con optimización de flujo. La opción económica de referencia para builders que buscan buen airflow con iluminación.', 'imagen_url' => 'https://m.media-amazon.com/images/I/61pXx9HRB4L.jpg'],
            vent: ['tipo' => 'Normal', 'rpm_min' => 500, 'rpm_max' => 1550, 'ruido_db_min' => 15.1, 'ruido_db_max' => 26.3, 'flujo_aire_cfm' => 66.17, 'static_pressure_mmh2o' => 2.08, 'num_ventiladores' => 3, 'tiene_rgb' => true, 'pwm' => true, 'tam_mm' => 120]
        );
        $this->crearVentilador(
            comp: ['nombre' => 'DeepCool FL12R ARGB (x3)', 'marca' => 'DeepCool', 'modelo' => 'FL12R', 'descripcion' => 'Pack de 3 ventiladores 120 mm con anillo ARGB exterior de alta densidad. Sistema daisy-chain para reducir cableado. Compatible con control ARGB de placa base. Buen equilibrio entre presión estática y caudal.', 'https://assets.kogan.com/images/crazydealsaus/CDA-CFD-FL12R-3P-WH/1-e704f853b0-cfd-fl12r-3p-wh.jpg?auto=webp&bg-color=fff&canvas=1200%2C800&dpr=1&enable=upscale&fit=bounds&height=800&quality=90&width=1200'],
            vent: ['tipo' => 'Normal', 'rpm_min' => 500, 'rpm_max' => 1850, 'ruido_db_min' => 17.6, 'ruido_db_max' => 29.8, 'flujo_aire_cfm' => 68.99, 'static_pressure_mmh2o' => 2.19, 'num_ventiladores' => 3, 'tiene_rgb' => true, 'pwm' => true, 'tam_mm' => 120]
        );
        $this->crearVentilador(
            comp: ['nombre' => 'Phanteks D30 140mm DRGB (x3)', 'marca' => 'Phanteks', 'modelo' => 'PH-F140D30_DRGB_PWM3P', 'descripcion' => 'Pack de 3 ventiladores 140 mm con diseño de palas duales (Dual-Ring) y 30 LEDs DRGB por ventilador. Sistema daisy-chain D-ARGB. Excepcional combinación de caudal, presión y silencio para el formato 140.', 'imagen_url' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTLnHaPD-zC9-CcOyPDR-7fPrwpb4Os6QgPDw&s'],
            vent: ['tipo' => 'Normal', 'rpm_min' => 400, 'rpm_max' => 1200, 'ruido_db_min' => 14.0, 'ruido_db_max' => 25.4, 'flujo_aire_cfm' => 84.10, 'static_pressure_mmh2o' => 1.98, 'num_ventiladores' => 3, 'tiene_rgb' => true, 'pwm' => true, 'tam_mm' => 140]
        );
    }
}

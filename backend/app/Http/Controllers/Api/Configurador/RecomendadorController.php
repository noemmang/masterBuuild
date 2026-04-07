<?php

namespace App\Http\Controllers\Api\Configurador;

use App\Http\Controllers\Controller;
use App\Models\Componentes\Componente;
use App\Models\Componentes\CPU;
use App\Models\Componentes\GPU;
use App\Models\Componentes\RAM;
use App\Models\Componentes\PlacaBase;
use App\Models\Componentes\PSU;
use App\Models\Componentes\Gabinete;
use App\Models\Componentes\RefrigeracionAire;
use App\Models\Componentes\RefrigeracionLiquida;
use App\Models\Negocio\EntradaPrecio;
use Illuminate\Http\Request;

class RecomendadorController extends Controller
{
    // Perfiles de uso predefinidos
    const PERFILES = [
        'gaming_basico' => [
            'descripcion'     => 'Gaming casual, juegos indie y títulos de gama media',
            'nucleos_min'     => 6,
            'vram_min_gb'     => 8,
            'ram_min_gb'      => 16,
            'refrigeracion'   => 'aire',
        ],
        'gaming_alto' => [
            'descripcion'     => 'Gaming en alta resolución, títulos AAA',
            'nucleos_min'     => 8,
            'vram_min_gb'     => 12,
            'ram_min_gb'      => 32,
            'refrigeracion'   => 'cualquiera',
        ],
        'edicion_video' => [
            'descripcion'     => 'Edición de vídeo y fotografía',
            'nucleos_min'     => 10,
            'vram_min_gb'     => 12,
            'ram_min_gb'      => 32,
            'refrigeracion'   => 'liquida',
        ],
        'renderizado' => [
            'descripcion'     => 'Renderizado 3D y animación',
            'nucleos_min'     => 16,
            'vram_min_gb'     => 16,
            'ram_min_gb'      => 64,
            'refrigeracion'   => 'liquida',
        ],
        'oficina' => [
            'descripcion'     => 'Ofimática, navegación y tareas ligeras',
            'nucleos_min'     => 4,
            'vram_min_gb'     => 0,
            'ram_min_gb'      => 16,
            'refrigeracion'   => 'aire',
        ],
    ];

    // Distribución del presupuesto por perfil (porcentajes)
    const DISTRIBUCION = [
        'gaming_basico' => [
            'cpu'       => 0.20,
            'gpu'       => 0.35,
            'ram'       => 0.10,
            'placa_base'=> 0.15,
            'psu'       => 0.10,
            'gabinete'  => 0.05,
            'refrigeracion' => 0.05,
        ],
        'gaming_alto' => [
            'cpu'       => 0.18,
            'gpu'       => 0.40,
            'ram'       => 0.10,
            'placa_base'=> 0.12,
            'psu'       => 0.10,
            'gabinete'  => 0.05,
            'refrigeracion' => 0.05,
        ],
        'edicion_video' => [
            'cpu'       => 0.25,
            'gpu'       => 0.30,
            'ram'       => 0.20,
            'placa_base'=> 0.10,
            'psu'       => 0.08,
            'gabinete'  => 0.04,
            'refrigeracion' => 0.03,
        ],
        'renderizado' => [
            'cpu'       => 0.30,
            'gpu'       => 0.35,
            'ram'       => 0.15,
            'placa_base'=> 0.10,
            'psu'       => 0.05,
            'gabinete'  => 0.03,
            'refrigeracion' => 0.02,
        ],
        'oficina' => [
            'cpu'       => 0.30,
            'gpu'       => 0.00,
            'ram'       => 0.15,
            'placa_base'=> 0.25,
            'psu'       => 0.15,
            'gabinete'  => 0.10,
            'refrigeracion' => 0.05,
        ],
    ];

    // Presupuesto mínimo por perfil
    const PRESUPUESTO_MINIMO = [
        'gaming_basico' => 600,
        'gaming_alto'   => 1200,
        'edicion_video' => 1500,
        'renderizado'   => 2500,
        'oficina'       => 400,
    ];

    public function recomendar(Request $request)
    {
        $data = $request->validate([
            'perfil'      => 'required|in:gaming_basico,gaming_alto,edicion_video,renderizado,oficina',
            'presupuesto' => 'required|numeric|min:200',
            'con_cupones' => 'boolean',
        ]);

        $perfil      = $data['perfil'];
        $presupuesto = $data['presupuesto'];
        $conCupones  = $data['con_cupones'] ?? true;

        // Verificar presupuesto mínimo
        $minimoRequerido = self::PRESUPUESTO_MINIMO[$perfil];
        if ($presupuesto < $minimoRequerido) {
            return response()->json([
                'viable'   => false,
                'mensaje'  => "El presupuesto de {$presupuesto}€ es insuficiente para un perfil de " . self::PERFILES[$perfil]['descripcion'] . ". Se necesitan al menos {$minimoRequerido}€.",
                'minimo'   => $minimoRequerido,
                'falta'    => $minimoRequerido - $presupuesto,
            ], 422);
        }

        $distribucion  = self::DISTRIBUCION[$perfil];
        $perfilConfig  = self::PERFILES[$perfil];
        $configuracion = [];
        $totalReal     = 0;

        // ── CPU ──────────────────────────────────────────
        $presupuestoCpu = $presupuesto * $distribucion['cpu'];
        $cpu = $this->mejorCPU($presupuestoCpu, $perfilConfig['nucleos_min'], $conCupones);

        if (!$cpu) {
            return response()->json([
                'viable'  => false,
                'mensaje' => "No se encontró ninguna CPU adecuada para el presupuesto asignado ({$presupuestoCpu}€). Intenta aumentar tu presupuesto.",
            ], 422);
        }

        $configuracion['cpu'] = $cpu;
        $totalReal += $cpu['precio_efectivo'];

        // ── Placa Base ───────────────────────────────────
        $presupuestoPlaca = $presupuesto * $distribucion['placa_base'];
        $placaBase = $this->mejorPlacaBase(
            $presupuestoPlaca,
            $cpu['socket_id'],
            $cpu['tipo_memoria_id'],
            $conCupones
        );

        if ($placaBase) {
            $configuracion['placa_base'] = $placaBase;
            $totalReal += $placaBase['precio_efectivo'];
        }

        // ── RAM ──────────────────────────────────────────
        $presupuestoRam = $presupuesto * $distribucion['ram'];
        $ram = $this->mejorRAM(
            $presupuestoRam,
            $cpu['tipo_memoria_id'],
            $perfilConfig['ram_min_gb'],
            $conCupones
        );

        if ($ram) {
            $configuracion['ram'] = $ram;
            $totalReal += $ram['precio_efectivo'];
        }

        // ── GPU ──────────────────────────────────────────
        if ($distribucion['gpu'] > 0) {
            $presupuestoGpu = $presupuesto * $distribucion['gpu'];
            $gpu = $this->mejorGPU(
                $presupuestoGpu,
                $perfilConfig['vram_min_gb'],
                $conCupones
            );

            if ($gpu) {
                $configuracion['gpu'] = $gpu;
                $totalReal += $gpu['precio_efectivo'];
            }
        }

        // ── PSU ──────────────────────────────────────────
        $consumoEstimado = ($cpu['tdp_watts'] ?? 65) + ($configuracion['gpu']['tdp_watts'] ?? 0) + 50;
        $presupuestoPsu  = $presupuesto * $distribucion['psu'];
        $psu = $this->mejorPSU($presupuestoPsu, $consumoEstimado, $conCupones);

        if ($psu) {
            $configuracion['psu'] = $psu;
            $totalReal += $psu['precio_efectivo'];
        }

        // ── Refrigeración ────────────────────────────────
        $presupuestoRefrig = $presupuesto * $distribucion['refrigeracion'];
        $tipoRefrig        = $perfilConfig['refrigeracion'];

        if ($tipoRefrig === 'liquida' || $tipoRefrig === 'cualquiera') {
            $refrigeracion = $this->mejorRefrigeracionLiquida(
                $presupuestoRefrig,
                $cpu['socket_id'],
                $cpu['tdp_watts'],
                $conCupones
            );
        } else {
            $refrigeracion = $this->mejorRefrigeracionAire(
                $presupuestoRefrig,
                $cpu['socket_id'],
                $cpu['tdp_watts'],
                $conCupones
            );
        }

        if ($refrigeracion) {
            $configuracion['refrigeracion'] = $refrigeracion;
            $totalReal += $refrigeracion['precio_efectivo'];
        }

        // ── Gabinete ─────────────────────────────────────
        $presupuestoGabinete = $presupuesto * $distribucion['gabinete'];
        $gabinete = $this->mejorGabinete(
            $presupuestoGabinete,
            $configuracion['gpu']['longitud_mm'] ?? 0,
            $configuracion['refrigeracion']['altura_mm'] ?? 0,
            $conCupones
        );

        if ($gabinete) {
            $configuracion['gabinete'] = $gabinete;
            $totalReal += $gabinete['precio_efectivo'];
        }

        return response()->json([
            'viable'        => true,
            'perfil'        => $perfil,
            'descripcion'   => self::PERFILES[$perfil]['descripcion'],
            'presupuesto'   => $presupuesto,
            'total_estimado'=> round($totalReal, 2),
            'ahorro'        => round($presupuesto - $totalReal, 2),
            'configuracion' => $configuracion,
            'consumo_estimado_watts' => $consumoEstimado,
        ]);
    }

    // ── Helpers privados ──────────────────────────────────

    private function mejorCPU(float $presupuesto, int $nucleosMin, bool $conCupones): ?array
    {
        $cpu = CPU::where('nucleos', '>=', $nucleosMin)
            ->whereHas('componente.preciosActuales', fn($q) =>
                $q->where('en_stock', true)
                  ->whereRaw('COALESCE(precio_con_cupon, precio) <= ?', [$presupuesto])
            )
            ->with(['componente.preciosActuales.tienda', 'socket', 'tipoMemoria'])
            ->get()
            ->sortByDesc('nucleos')
            ->first();

        if (!$cpu) return null;

        $mejorPrecio = $this->obtenerMejorPrecio($cpu->componente, $conCupones);

        return [
            'uuid'           => $cpu->componente->uuid,
            'nombre'         => $cpu->componente->nombre,
            'socket_id'      => $cpu->socket_id,
            'socket'         => $cpu->socket->nombre,
            'tipo_memoria_id'=> $cpu->tipo_memoria_id,
            'tipo_memoria'   => $cpu->tipoMemoria->nombre,
            'nucleos'        => $cpu->nucleos,
            'hilos'          => $cpu->hilos,
            'tdp_watts'      => $cpu->tdp_watts,
            'precio_efectivo'=> $mejorPrecio['precio'],
            'tienda'         => $mejorPrecio['tienda'],
            'con_cupon'      => $mejorPrecio['con_cupon'],
        ];
    }

    private function mejorGPU(float $presupuesto, int $vramMin, bool $conCupones): ?array
    {
        $gpu = GPU::where('vram_gb', '>=', $vramMin)
            ->whereHas('componente.preciosActuales', fn($q) =>
                $q->where('en_stock', true)
                  ->whereRaw('COALESCE(precio_con_cupon, precio) <= ?', [$presupuesto])
            )
            ->with(['componente.preciosActuales.tienda'])
            ->get()
            ->sortByDesc('vram_gb')
            ->first();

        if (!$gpu) return null;

        $mejorPrecio = $this->obtenerMejorPrecio($gpu->componente, $conCupones);

        return [
            'uuid'           => $gpu->componente->uuid,
            'nombre'         => $gpu->componente->nombre,
            'vram_gb'        => $gpu->vram_gb,
            'tdp_watts'      => $gpu->tdp_watts,
            'longitud_mm'    => $gpu->longitud_mm,
            'precio_efectivo'=> $mejorPrecio['precio'],
            'tienda'         => $mejorPrecio['tienda'],
            'con_cupon'      => $mejorPrecio['con_cupon'],
        ];
    }

    private function mejorRAM(float $presupuesto, int $tipoMemoriaId, int $capacidadMin, bool $conCupones): ?array
    {
        $ram = RAM::where('tipo_memoria_id', $tipoMemoriaId)
            ->where('capacidad_total_gb', '>=', $capacidadMin)
            ->whereHas('componente.preciosActuales', fn($q) =>
                $q->where('en_stock', true)
                  ->whereRaw('COALESCE(precio_con_cupon, precio) <= ?', [$presupuesto])
            )
            ->with(['componente.preciosActuales.tienda'])
            ->get()
            ->sortByDesc('velocidad_mhz')
            ->first();

        if (!$ram) return null;

        $mejorPrecio = $this->obtenerMejorPrecio($ram->componente, $conCupones);

        return [
            'uuid'            => $ram->componente->uuid,
            'nombre'          => $ram->componente->nombre,
            'capacidad_total' => $ram->capacidad_total_gb,
            'velocidad_mhz'   => $ram->velocidad_mhz,
            'precio_efectivo' => $mejorPrecio['precio'],
            'tienda'          => $mejorPrecio['tienda'],
            'con_cupon'       => $mejorPrecio['con_cupon'],
        ];
    }

    private function mejorPlacaBase(float $presupuesto, int $socketId, int $tipoMemoriaId, bool $conCupones): ?array
    {
        $placa = PlacaBase::where('socket_id', $socketId)
            ->where('tipo_memoria_id', $tipoMemoriaId)
            ->whereHas('componente.preciosActuales', fn($q) =>
                $q->where('en_stock', true)
                  ->whereRaw('COALESCE(precio_con_cupon, precio) <= ?', [$presupuesto])
            )
            ->with(['componente.preciosActuales.tienda', 'chipset'])
            ->get()
            ->sortByDesc('slots_m2')
            ->first();

        if (!$placa) return null;

        $mejorPrecio = $this->obtenerMejorPrecio($placa->componente, $conCupones);

        return [
            'uuid'           => $placa->componente->uuid,
            'nombre'         => $placa->componente->nombre,
            'chipset'        => $placa->chipset->nombre,
            'slots_m2'       => $placa->slots_m2,
            'precio_efectivo'=> $mejorPrecio['precio'],
            'tienda'         => $mejorPrecio['tienda'],
            'con_cupon'      => $mejorPrecio['con_cupon'],
        ];
    }

    private function mejorPSU(float $presupuesto, int $consumoTotal, bool $conCupones): ?array
    {
        $vatiosMinimos = ceil($consumoTotal * 1.3);

        $psu = PSU::where('vatios', '>=', $vatiosMinimos)
            ->whereHas('componente.preciosActuales', fn($q) =>
                $q->where('en_stock', true)
                  ->whereRaw('COALESCE(precio_con_cupon, precio) <= ?', [$presupuesto])
            )
            ->with(['componente.preciosActuales.tienda', 'certificacion'])
            ->get()
            ->sortBy('vatios')
            ->first();

        if (!$psu) return null;

        $mejorPrecio = $this->obtenerMejorPrecio($psu->componente, $conCupones);

        return [
            'uuid'           => $psu->componente->uuid,
            'nombre'         => $psu->componente->nombre,
            'vatios'         => $psu->vatios,
            'certificacion'  => $psu->certificacion->nombre,
            'precio_efectivo'=> $mejorPrecio['precio'],
            'tienda'         => $mejorPrecio['tienda'],
            'con_cupon'      => $mejorPrecio['con_cupon'],
        ];
    }

    private function mejorRefrigeracionAire(float $presupuesto, int $socketId, int $tdpCpu, bool $conCupones): ?array
    {
        $refrig = RefrigeracionAire::where('tdp_max_watts', '>=', $tdpCpu)
            ->whereHas('socketsCompatibles', fn($q) =>
                $q->where('socket_id', $socketId)
            )
            ->whereHas('componente.preciosActuales', fn($q) =>
                $q->where('en_stock', true)
                  ->whereRaw('COALESCE(precio_con_cupon, precio) <= ?', [$presupuesto])
            )
            ->with(['componente.preciosActuales.tienda'])
            ->get()
            ->sortByDesc('tdp_max_watts')
            ->first();

        if (!$refrig) return null;

        $mejorPrecio = $this->obtenerMejorPrecio($refrig->componente, $conCupones);

        return [
            'uuid'           => $refrig->componente->uuid,
            'nombre'         => $refrig->componente->nombre,
            'tipo'           => 'aire',
            'tdp_max_watts'  => $refrig->tdp_max_watts,
            'altura_mm'      => $refrig->altura_mm,
            'precio_efectivo'=> $mejorPrecio['precio'],
            'tienda'         => $mejorPrecio['tienda'],
            'con_cupon'      => $mejorPrecio['con_cupon'],
        ];
    }

    private function mejorRefrigeracionLiquida(float $presupuesto, int $socketId, int $tdpCpu, bool $conCupones): ?array
    {
        $refrig = RefrigeracionLiquida::where('tdp_max_watts', '>=', $tdpCpu)
            ->whereHas('socketsCompatibles', fn($q) =>
                $q->where('socket_id', $socketId)
            )
            ->whereHas('componente.preciosActuales', fn($q) =>
                $q->where('en_stock', true)
                  ->whereRaw('COALESCE(precio_con_cupon, precio) <= ?', [$presupuesto])
            )
            ->with(['componente.preciosActuales.tienda'])
            ->get()
            ->sortByDesc('tam_radiador_mm')
            ->first();

        if (!$refrig) return null;

        $mejorPrecio = $this->obtenerMejorPrecio($refrig->componente, $conCupones);

        return [
            'uuid'            => $refrig->componente->uuid,
            'nombre'          => $refrig->componente->nombre,
            'tipo'            => 'liquida',
            'tdp_max_watts'   => $refrig->tdp_max_watts,
            'tam_radiador_mm' => $refrig->tam_radiador_mm,
            'precio_efectivo' => $mejorPrecio['precio'],
            'tienda'          => $mejorPrecio['tienda'],
            'con_cupon'       => $mejorPrecio['con_cupon'],
        ];
    }

    private function mejorGabinete(float $presupuesto, int $longitudGpu, int $alturaCooler, bool $conCupones): ?array
    {
        $query = Gabinete::whereHas('componente.preciosActuales', fn($q) =>
            $q->where('en_stock', true)
              ->whereRaw('COALESCE(precio_con_cupon, precio) <= ?', [$presupuesto])
        );

        if ($longitudGpu > 0) {
            $query->where('longitud_gpu_max_mm', '>=', $longitudGpu);
        }

        if ($alturaCooler > 0) {
            $query->where('altura_cooler_max_mm', '>=', $alturaCooler);
        }

        $gabinete = $query->with(['componente.preciosActuales.tienda'])
            ->get()
            ->sortByDesc('longitud_gpu_max_mm')
            ->first();

        if (!$gabinete) return null;

        $mejorPrecio = $this->obtenerMejorPrecio($gabinete->componente, $conCupones);

        return [
            'uuid'                => $gabinete->componente->uuid,
            'nombre'              => $gabinete->componente->nombre,
            'longitud_gpu_max_mm' => $gabinete->longitud_gpu_max_mm,
            'altura_cooler_max_mm'=> $gabinete->altura_cooler_max_mm,
            'precio_efectivo'     => $mejorPrecio['precio'],
            'tienda'              => $mejorPrecio['tienda'],
            'con_cupon'           => $mejorPrecio['con_cupon'],
        ];
    }

    private function obtenerMejorPrecio($componente, bool $conCupones): array
    {
        $precios = $componente->preciosActuales
            ->where('en_stock', true)
            ->sortBy(fn($p) => $conCupones
                ? ($p->precio_con_cupon ?? $p->precio)
                : $p->precio
            );

        $mejor = $precios->first();

        if (!$mejor) {
            return ['precio' => 0, 'tienda' => null, 'con_cupon' => false];
        }

        return [
            'precio'    => $conCupones
                ? ($mejor->precio_con_cupon ?? $mejor->precio)
                : $mejor->precio,
            'tienda'    => $mejor->tienda->nombre,
            'con_cupon' => !is_null($mejor->precio_con_cupon),
        ];
    }
}
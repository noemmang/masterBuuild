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
use Illuminate\Http\Request;

class ConfiguradorController extends Controller
{
    // Validar compatibilidad de una configuración completa
    public function validar(Request $request)
    {
        $data = $request->validate([
            'cpu_uuid'                  => 'nullable|string',
            'gpu_uuid'                  => 'nullable|string',
            'ram_uuid'                  => 'nullable|string',
            'placa_base_uuid'           => 'nullable|string',
            'psu_uuid'                  => 'nullable|string',
            'gabinete_uuid'             => 'nullable|string',
            'refrigeracion_uuid'        => 'nullable|string',
            'tipo_refrigeracion'        => 'nullable|in:aire,liquida',
        ]);

        $resultado = [
            'compatible'  => true,
            'advertencias'=> [],
            'errores'     => [],
            'notas'       => [],
            'consumo_total_watts' => 0,
        ];

        // Cargar componentes seleccionados
        $cpu        = $this->cargarComponente($data['cpu_uuid'] ?? null, 'cpu');
        $gpu        = $this->cargarComponente($data['gpu_uuid'] ?? null, 'gpu');
        $ram        = $this->cargarComponente($data['ram_uuid'] ?? null, 'ram');
        $placaBase  = $this->cargarComponente($data['placa_base_uuid'] ?? null, 'placaBase');
        $psu        = $this->cargarComponente($data['psu_uuid'] ?? null, 'psu');
        $gabinete   = $this->cargarComponente($data['gabinete_uuid'] ?? null, 'gabinete');
        $refrigeracion = null;

        if (!empty($data['refrigeracion_uuid'])) {
            $tipo = $data['tipo_refrigeracion'] ?? 'aire';
            $refrigeracion = $this->cargarComponente(
                $data['refrigeracion_uuid'],
                $tipo === 'aire' ? 'refrigeracionAire' : 'refrigeracionLiquida'
            );
        }

        // ── CPU ↔ Placa Base ──────────────────────────────
        if ($cpu && $placaBase) {
            if ($cpu->specs->socket_id !== $placaBase->specs->socket_id) {
                $resultado['errores'][] = [
                    'tipo'    => 'socket_incompatible',
                    'mensaje' => "El socket de la CPU ({$cpu->specs->socket->nombre}) no es compatible con la placa base ({$placaBase->specs->socket->nombre})",
                ];
                $resultado['compatible'] = false;
            }

            if ($cpu->specs->tipo_memoria_id !== $placaBase->specs->tipo_memoria_id) {
                $resultado['errores'][] = [
                    'tipo'    => 'memoria_incompatible',
                    'mensaje' => "El tipo de memoria de la CPU ({$cpu->specs->tipoMemoria->nombre}) no coincide con la placa base ({$placaBase->specs->tipoMemoria->nombre})",
                ];
                $resultado['compatible'] = false;
            }
        }

        // ── RAM ↔ Placa Base ──────────────────────────────
        if ($ram && $placaBase) {
            if ($ram->specs->tipo_memoria_id !== $placaBase->specs->tipo_memoria_id) {
                $resultado['errores'][] = [
                    'tipo'    => 'ram_incompatible',
                    'mensaje' => "El tipo de RAM ({$ram->specs->tipoMemoria->nombre}) no es compatible con la placa base ({$placaBase->specs->tipoMemoria->nombre})",
                ];
                $resultado['compatible'] = false;
            }

            if ($ram->specs->velocidad_mhz > $placaBase->specs->frecuencia_memoria_max_mhz) {
                $resultado['advertencias'][] = [
                    'tipo'    => 'ram_velocidad',
                    'mensaje' => "La RAM ({$ram->specs->velocidad_mhz}MHz) supera la frecuencia máxima de la placa base ({$placaBase->specs->frecuencia_memoria_max_mhz}MHz). Funcionará a velocidad reducida.",
                ];
            }
        }

        // ── RAM ↔ CPU ─────────────────────────────────────
        if ($ram && $cpu) {
            if ($ram->specs->velocidad_mhz > $cpu->specs->frecuencia_memoria_max_mhz) {
                $resultado['advertencias'][] = [
                    'tipo'    => 'ram_velocidad_cpu',
                    'mensaje' => "La RAM ({$ram->specs->velocidad_mhz}MHz) supera la frecuencia máxima soportada por la CPU ({$cpu->specs->frecuencia_memoria_max_mhz}MHz).",
                ];
            }
        }

        // ── GPU ↔ Gabinete ────────────────────────────────
        if ($gpu && $gabinete) {
            if ($gpu->specs->longitud_mm > $gabinete->specs->longitud_gpu_max_mm) {
                $resultado['errores'][] = [
                    'tipo'    => 'gpu_longitud',
                    'mensaje' => "La GPU ({$gpu->specs->longitud_mm}mm) no cabe en el gabinete (máximo {$gabinete->specs->longitud_gpu_max_mm}mm)",
                ];
                $resultado['compatible'] = false;
            }

            // Nota de excepción: compatibilidad solo en montaje vertical
            if ($gabinete->specs->montaje_vertical_pcie &&
                $gpu->specs->longitud_mm <= $gabinete->specs->longitud_gpu_max_mm) {
                $resultado['notas'][] = [
                    'tipo'    => 'montaje_vertical',
                    'mensaje' => "Esta GPU es compatible con el gabinete, pero debido a su longitud ({$gpu->specs->longitud_mm}mm) puede requerir montaje vertical PCIe para una correcta instalación.",
                ];
            }
        }

        // ── Placa Base ↔ Gabinete ─────────────────────────
        if ($placaBase && $gabinete) {
            $factoresGabinete = $gabinete->specs->factoresForma->pluck('id')->toArray();
            if (!in_array($placaBase->specs->factor_forma_id, $factoresGabinete)) {
                $resultado['errores'][] = [
                    'tipo'    => 'factor_forma',
                    'mensaje' => "El factor de forma de la placa base ({$placaBase->specs->factorForma->nombre}) no es compatible con el gabinete",
                ];
                $resultado['compatible'] = false;
            }
        }

        // ── Refrigeración ↔ CPU ───────────────────────────
        if ($refrigeracion && $cpu) {
            $tdpRefrig = $refrigeracion->specs->tdp_max_watts;
            $tdpCpu    = $cpu->specs->tdp_max_watts ?? $cpu->specs->tdp_watts;

            if ($tdpRefrig < $tdpCpu) {
                $resultado['errores'][] = [
                    'tipo'    => 'refrigeracion_tdp',
                    'mensaje' => "La refrigeración (máx {$tdpRefrig}W) no es suficiente para la CPU ({$tdpCpu}W TDP máximo)",
                ];
                $resultado['compatible'] = false;
            } elseif ($tdpRefrig < $tdpCpu * 1.2) {
                $resultado['advertencias'][] = [
                    'tipo'    => 'refrigeracion_tdp_ajustado',
                    'mensaje' => "La refrigeración tiene poca holgura para el TDP de la CPU. Se recomienda una con mayor capacidad para mejor rendimiento.",
                ];
            }

            // Verificar socket compatible
            $socketsCooler = $refrigeracion->specs->socketsCompatibles->pluck('id')->toArray();
            if (!in_array($cpu->specs->socket_id, $socketsCooler)) {
                $resultado['errores'][] = [
                    'tipo'    => 'refrigeracion_socket',
                    'mensaje' => "La refrigeración no es compatible con el socket de la CPU ({$cpu->specs->socket->nombre})",
                ];
                $resultado['compatible'] = false;
            }
        }

        // ── Refrigeración ↔ Gabinete ──────────────────────
        if ($refrigeracion && $gabinete) {
            if ($data['tipo_refrigeracion'] === 'aire') {
                $altura = $refrigeracion->specs->altura_mm;
                $maxAltura = $gabinete->specs->altura_cooler_max_mm;

                if ($altura > $maxAltura) {
                    $resultado['errores'][] = [
                        'tipo'    => 'cooler_altura',
                        'mensaje' => "El cooler ({$altura}mm) no cabe en el gabinete (máximo {$maxAltura}mm)",
                    ];
                    $resultado['compatible'] = false;
                }
            } else {
                // AIO — verificar tamaño de radiador
                $tamRadiador = $refrigeracion->specs->tam_radiador_mm;
                $soporteRadiadores = $gabinete->specs->soporte_radiadores ?? [];

                if (!in_array($tamRadiador, $soporteRadiadores)) {
                    $resultado['errores'][] = [
                        'tipo'    => 'radiador_tamanio',
                        'mensaje' => "El radiador ({$tamRadiador}mm) no es compatible con el gabinete. Tamaños soportados: " . implode(', ', $soporteRadiadores) . "mm",
                    ];
                    $resultado['compatible'] = false;
                }

                // Nota: bomba AIO vs GPU vertical
                if ($gabinete->specs->montaje_vertical_pcie && $refrigeracion->specs->altura_bomba_mm) {
                    $resultado['notas'][] = [
                        'tipo'    => 'bomba_gpu_vertical',
                        'mensaje' => "Con GPU en montaje vertical, verifica que la bomba del AIO ({$refrigeracion->specs->altura_bomba_mm}mm) no interfiera con la tarjeta gráfica.",
                    ];
                }
            }
        }

        // ── RAM ↔ Refrigeración Aire ──────────────────────
        if ($ram && $refrigeracion && $data['tipo_refrigeracion'] === 'aire') {
            if ($ram->specs->altura_mm && $refrigeracion->specs->altura_mm) {
                if ($ram->specs->altura_mm > 40 && $refrigeracion->specs->disipador_dual_torre) {
                    $resultado['advertencias'][] = [
                        'tipo'    => 'ram_cooler_altura',
                        'mensaje' => "La RAM de alto perfil ({$ram->specs->altura_mm}mm) puede interferir con el cooler de doble torre. Verifica la compatibilidad.",
                    ];
                }
            }
        }

        // ── PSU ↔ Consumo total ───────────────────────────
        $consumoCpu = $cpu ? ($cpu->specs->tdp_max_watts ?? $cpu->specs->tdp_watts) : 0;
        $consumoGpu = $gpu ? $gpu->specs->tdp_watts : 0;
        $consumoBase = 50; // placa base + RAM + almacenamiento estimado
        $consumoTotal = $consumoCpu + $consumoGpu + $consumoBase;
        $resultado['consumo_total_watts'] = $consumoTotal;

        if ($psu) {
            $margen = $psu->specs->vatios - $consumoTotal;

            if ($psu->specs->vatios < $consumoTotal) {
                $resultado['errores'][] = [
                    'tipo'    => 'psu_insuficiente',
                    'mensaje' => "La PSU ({$psu->specs->vatios}W) no tiene suficiente potencia para el sistema ({$consumoTotal}W estimado)",
                ];
                $resultado['compatible'] = false;
            } elseif ($margen < $consumoTotal * 0.2) {
                $resultado['advertencias'][] = [
                    'tipo'    => 'psu_margen',
                    'mensaje' => "La PSU tiene poco margen ({$margen}W). Se recomienda al menos un 20% de margen para mayor estabilidad y vida útil.",
                ];
            }

            // Verificar conector GPU
            if ($gpu) {
                $psuMinima = $gpu->specs->psu_minima_watts;
                if ($psu->specs->vatios < $psuMinima) {
                    $resultado['errores'][] = [
                        'tipo'    => 'psu_gpu',
                        'mensaje' => "La GPU requiere una PSU mínima de {$psuMinima}W",
                    ];
                    $resultado['compatible'] = false;
                }
            }
        } else {
            // Sin PSU seleccionada, recomendar vatios mínimos
            $resultado['notas'][] = [
                'tipo'    => 'psu_recomendada',
                'mensaje' => "Para esta configuración se recomiendan al menos " . ceil($consumoTotal * 1.3) . "W de PSU",
            ];
        }

        return response()->json($resultado);
    }

    // Cargar componente con sus specs específicas
    private function cargarComponente(?string $uuid, string $relacion): ?object
    {
        if (!$uuid) return null;

        $componente = Componente::where('uuid', $uuid)
            ->activo()
            ->with($relacion)
            ->first();

        if (!$componente || !$componente->$relacion) return null; // ← esta línea

        return (object)[
            'uuid'   => $componente->uuid,
            'nombre' => $componente->nombre,
            'specs'  => $componente->$relacion,
        ];
    }
}
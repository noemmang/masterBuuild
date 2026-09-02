<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Forma de un componente para su ficha de detalle
 * (GET /api/v1/componentes/{uuid}). Sustituye al antiguo
 * `return response()->json($componente)` que devolvía el modelo Eloquent
 * tal cual.
 *
 * Eso tenía dos problemas de fondo, no solo de estilo:
 *
 *  1. Nombres de relación con siglas en mayúsculas (tipoVRAM, versionPCIe,
 *     tipoPSU, tipoNAND, tiposPSU) se serializaban con
 *     Str::snake($nombreDelMétodo), que separa cada mayúscula suelta:
 *     "tipoVRAM" → "tipo_v_r_a_m", no "tipo_vram". El dato llegaba al
 *     frontend con una clave que nadie escribió a mano en ningún sitio,
 *     así que cualquier código que buscara "tipo_vram" encontraba
 *     `undefined` en silencio. (Se han renombrado además los métodos de
 *     relación en los modelos para no depender de esto en ningún otro
 *     sitio que también serialice el modelo a pelo).
 *  2. Las columnas `decimal` (frecuencia_base_ghz, voltaje, precio...) las
 *     serializa Eloquent como STRING cuando no llevan un cast explícito
 *     (p.ej. "3.70", no 3.7), para no perder precisión. El frontend las
 *     trataba como número (barras de comparación en spec-compare,
 *     ordenación); con un string ahí esas comprobaciones fallaban también
 *     en silencio.
 *
 * Aquí se decide explícitamente cada nombre de campo y cada tipo, así que
 * ninguno de los dos problemas puede colarse sin que se note en el propio
 * código de este archivo.
 */
class ComponenteDetalleResource extends JsonResource
{
    // Sin el wrapper {"data": {...}} que Laravel añade por defecto a los
    // JsonResource sueltos: show() devolvía el objeto tal cual antes de
    // pasar por este Resource, y el frontend ya lee la respuesta como el
    // componente directamente, no anidada bajo "data" (a diferencia del
    // listado, donde "data" es el array de resultados de la paginación,
    // eso sí se mantiene).
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'uuid'        => $this->uuid,
            'nombre'      => $this->nombre,
            'categoria'   => $this->categoria,
            'modelo'      => $this->modelo,
            'imagen_url'  => $this->imagen_url,
            'descripcion' => $this->descripcion,
            'marca'       => $this->marca ? ['nombre' => $this->marca->nombre] : null,
            'fabricante'  => $this->fabricante ? ['nombre' => $this->fabricante->nombre] : null,
            'precios'     => $this->precios(),
            'precio_min'  => $this->precioMin(),
            'specs'       => $this->specs(),
        ];
    }

    /**
     * Un precio por tienda, con el mismo shape que ya consume
     * price-history.component / spec-compare (tienda, precio, url,
     * en_stock).
     */
    private function precios(): array
    {
        return $this->preciosActuales->map(fn ($p) => [
            'tienda'        => $p->tienda?->nombre,
            'precio'        => (float) $p->precio,
            'moneda'        => $p->moneda,
            'url'           => $p->url,
            'en_stock'      => (bool) $p->en_stock,
            'vigente_desde' => $p->vigente_desde?->toIso8601String(),
        ])->values()->all();
    }

    private function precioMin(): ?float
    {
        $conStock = $this->preciosActuales->where('en_stock', true);
        $mejor = ($conStock->isNotEmpty() ? $conStock : $this->preciosActuales)
            ->sortBy('precio')
            ->first();

        return $mejor ? (float) $mejor->precio : null;
    }

    private function specs(): ?array
    {
        return match (true) {
            $this->categoria === 'cpu' && $this->cpu !== null => [
                'socket'                       => $this->cpu->socket?->nombre,
                'arquitectura'                 => $this->cpu->arquitectura?->nombre,
                'tipo_memoria'                 => $this->cpu->tipoMemoria?->nombre,
                'nucleos'                      => (int) $this->cpu->nucleos,
                'hilos'                        => (int) $this->cpu->hilos,
                'frecuencia_base_ghz'          => (float) $this->cpu->frecuencia_base_ghz,
                'frecuencia_boost_ghz'         => $this->cpu->frecuencia_boost_ghz !== null ? (float) $this->cpu->frecuencia_boost_ghz : null,
                'tdp_watts'                    => (int) $this->cpu->tdp_watts,
                'tdp_max_watts'                => $this->cpu->tdp_max_watts !== null ? (int) $this->cpu->tdp_max_watts : null,
                'frecuencia_memoria_max_mhz'   => (int) $this->cpu->frecuencia_memoria_max_mhz,
                'memoria_max_gb'               => (int) $this->cpu->memoria_max_gb,
                'grafica_integrada'            => (bool) $this->cpu->grafica_integrada,
                'nombre_grafica_integrada'     => $this->cpu->nombre_grafica_integrada,
                'proceso_nm'                   => $this->cpu->proceso_nm !== null ? (int) $this->cpu->proceso_nm : null,
                'incluye_cooler'               => (bool) $this->cpu->incluye_cooler,
                'overclock'                    => (bool) $this->cpu->overclock,
            ],
            $this->categoria === 'gpu' && $this->gpu !== null => [
                'arquitectura'            => $this->gpu->arquitectura?->nombre,
                'tipo_vram'               => $this->gpu->tipoVram?->nombre,
                'version_pcie'            => $this->gpu->versionPcie?->nombre,
                'vram_gb'                 => (int) $this->gpu->vram_gb,
                'bus_bits'                => (int) $this->gpu->bus_bits,
                'frecuencia_base_mhz'     => (int) $this->gpu->frecuencia_base_mhz,
                'frecuencia_boost_mhz'    => $this->gpu->frecuencia_boost_mhz !== null ? (int) $this->gpu->frecuencia_boost_mhz : null,
                'tdp_watts'               => (int) $this->gpu->tdp_watts,
                'slots_pcie'              => (float) $this->gpu->slots_pcie,
                'longitud_mm'             => (int) $this->gpu->longitud_mm,
                'conectores_alimentacion' => $this->gpu->conectores_alimentacion,
                'psu_minima_watts'        => (int) $this->gpu->psu_minima_watts,
                'salidas_video'           => $this->gpu->salidas_video,
                'ray_tracing'             => (bool) $this->gpu->ray_tracing,
                'dlss'                    => (bool) $this->gpu->dlss,
                'fsr'                     => (bool) $this->gpu->fsr,
            ],
            $this->categoria === 'ram' && $this->ram !== null => [
                'tipo_memoria'       => $this->ram->tipoMemoria?->nombre,
                'capacidad_gb'       => (int) $this->ram->capacidad_gb,
                'modulos'            => (int) $this->ram->modulos,
                'capacidad_total_gb' => (int) $this->ram->capacidad_total_gb,
                'velocidad_mhz'      => (int) $this->ram->velocidad_mhz,
                'latencia_cas'       => $this->ram->latencia_cas,
                'voltaje'            => (float) $this->ram->voltaje,
                'factor_forma'       => $this->ram->factor_forma,
                'altura_mm'          => $this->ram->altura_mm !== null ? (int) $this->ram->altura_mm : null,
                'tiene_rgb'          => (bool) $this->ram->tiene_rgb,
                'ecc'                => (bool) $this->ram->ecc,
                'xmp'                => (bool) $this->ram->xmp,
                'expo'               => (bool) $this->ram->expo,
            ],
            $this->categoria === 'placa_base' && $this->placaBase !== null => [
                'socket'                     => $this->placaBase->socket?->nombre,
                'chipset'                    => $this->placaBase->chipset?->nombre,
                'factor_forma'               => $this->placaBase->factorForma?->nombre,
                'factor_forma_id'            => $this->placaBase->factor_forma_id,
                'tipo_memoria'               => $this->placaBase->tipoMemoria?->nombre,
                'version_pcie'               => $this->placaBase->versionPcie?->nombre,
                'slots_memoria'              => (int) $this->placaBase->slots_memoria,
                'memoria_max_gb'             => (int) $this->placaBase->memoria_max_gb,
                'frecuencia_memoria_max_mhz' => (int) $this->placaBase->frecuencia_memoria_max_mhz,
                'slots_pcie_x16'             => (int) $this->placaBase->slots_pcie_x16,
                'slots_pcie_x4'              => (int) $this->placaBase->slots_pcie_x4,
                'slots_pcie_x1'              => (int) $this->placaBase->slots_pcie_x1,
                'slots_m2'                   => (int) $this->placaBase->slots_m2,
                'puertos_sata'               => (int) $this->placaBase->puertos_sata,
                'puertos_usb_traseros'       => $this->placaBase->puertos_usb_traseros,
                'conector_atx'               => $this->placaBase->conector_atx,
                'conector_cpu'               => $this->placaBase->conector_cpu,
                'wifi'                       => (bool) $this->placaBase->wifi,
                'bluetooth'                  => (bool) $this->placaBase->bluetooth,
                'thunderbolt'                => (bool) $this->placaBase->thunderbolt,
                'audio_chipset'              => $this->placaBase->audio_chipset,
                'lan_chipset'                => $this->placaBase->lan_chipset,
                'lan_velocidad_gbps'         => (float) $this->placaBase->lan_velocidad_gbps,
            ],
            $this->categoria === 'almacenamiento' && $this->almacenamiento !== null => [
                'tipo'                    => $this->almacenamiento->tipo,
                'interfaz'                => $this->almacenamiento->interfaz?->nombre,
                'factor_forma'            => $this->almacenamiento->factorForma?->nombre,
                'tipo_nand'               => $this->almacenamiento->tipoNand?->nombre,
                'capacidad_gb'            => (int) $this->almacenamiento->capacidad_gb,
                'velocidad_lectura_mbs'   => $this->almacenamiento->velocidad_lectura_mbs !== null ? (int) $this->almacenamiento->velocidad_lectura_mbs : null,
                'velocidad_escritura_mbs' => $this->almacenamiento->velocidad_escritura_mbs !== null ? (int) $this->almacenamiento->velocidad_escritura_mbs : null,
                'rpm'                     => $this->almacenamiento->rpm !== null ? (int) $this->almacenamiento->rpm : null,
                'cache_mb'                => $this->almacenamiento->cache_mb !== null ? (int) $this->almacenamiento->cache_mb : null,
                'tbw'                     => $this->almacenamiento->tbw !== null ? (int) $this->almacenamiento->tbw : null,
                'cifrado'                 => (bool) $this->almacenamiento->cifrado,
                'dram'                    => (bool) $this->almacenamiento->dram,
            ],
            $this->categoria === 'psu' && $this->psu !== null => [
                'certificacion'         => $this->psu->certificacion?->nombre,
                'tipo_psu'              => $this->psu->tipoPsu?->nombre,
                'tipo_psu_id'           => $this->psu->tipo_psu_id,
                'vatios'                => (int) $this->psu->vatios,
                'modular'               => $this->psu->modular,
                'version_atx'           => $this->psu->version_atx,
                'conectores_pcie_16pin' => (int) $this->psu->conectores_pcie_16pin,
                'conectores_pcie_8pin'  => (int) $this->psu->conectores_pcie_8pin,
                'conectores_sata'       => (int) $this->psu->conectores_sata,
                'conectores_molex'      => (int) $this->psu->conectores_molex,
                'largo_mm'              => $this->psu->largo_mm !== null ? (int) $this->psu->largo_mm : null,
                'ventilador_mm'         => $this->psu->ventilador_mm !== null ? (int) $this->psu->ventilador_mm : null,
                'ventilador_zero_rpm'   => (bool) $this->psu->ventilador_zero_rpm,
            ],
            $this->categoria === 'gabinete' && $this->gabinete !== null => [
                'tipo_gabinete'                     => $this->gabinete->tipoGabinete?->nombre,
                'estructura'                        => $this->gabinete->estructuraGabinete?->nombre,
                'factores_forma'                    => $this->gabinete->factoresForma->pluck('nombre')->values(),
                'tipos_psu'                          => $this->gabinete->tiposPsu->pluck('nombre')->values(),
                'longitud_gpu_max_mm'               => $this->gabinete->longitud_gpu_max_mm !== null ? (int) $this->gabinete->longitud_gpu_max_mm : null,
                'altura_cooler_max_mm'               => $this->gabinete->altura_cooler_max_mm !== null ? (int) $this->gabinete->altura_cooler_max_mm : null,
                'largo_psu_max_mm'                   => $this->gabinete->largo_psu_max_mm !== null ? (int) $this->gabinete->largo_psu_max_mm : null,
                'bahias_35'                           => $this->gabinete->bahias_35 !== null ? (int) $this->gabinete->bahias_35 : null,
                'bahias_25'                           => $this->gabinete->bahias_25 !== null ? (int) $this->gabinete->bahias_25 : null,
                'ventiladores_frontales'             => $this->gabinete->ventiladores_frontales !== null ? (int) $this->gabinete->ventiladores_frontales : null,
                'ventiladores_traseros'               => $this->gabinete->ventiladores_traseros !== null ? (int) $this->gabinete->ventiladores_traseros : null,
                'ventiladores_superiores'             => $this->gabinete->ventiladores_superiores !== null ? (int) $this->gabinete->ventiladores_superiores : null,
                'ventiladores_incluidos'             => $this->gabinete->ventiladores_incluidos !== null ? (int) $this->gabinete->ventiladores_incluidos : null,
                'tam_ventilador_frontal_mm'          => $this->gabinete->tam_ventilador_frontal_mm !== null ? (int) $this->gabinete->tam_ventilador_frontal_mm : null,
                'tam_ventilador_superior_mm'         => $this->gabinete->tam_ventilador_superior_mm !== null ? (int) $this->gabinete->tam_ventilador_superior_mm : null,
                'tam_ventilador_trasero_mm'          => $this->gabinete->tam_ventilador_trasero_mm !== null ? (int) $this->gabinete->tam_ventilador_trasero_mm : null,
                'soporte_radiadores'                  => $this->gabinete->soporte_radiadores,
                'puertos_usb_frontales'               => $this->gabinete->puertos_usb_frontales,
                'montaje_vertical_pcie'                => (bool) $this->gabinete->montaje_vertical_pcie,
                'panel_frontal'                        => $this->gabinete->panel_frontal,
                'ancho_mm'                              => (int) $this->gabinete->ancho_mm,
                'alto_mm'                               => (int) $this->gabinete->alto_mm,
                'profundidad_mm'                        => (int) $this->gabinete->profundidad_mm,
            ],
            $this->categoria === 'refrigeracion_aire' && $this->refrigeracionAire !== null => [
                'tipo_refrigeracion'  => $this->refrigeracionAire->tipoRefrigeracion?->nombre,
                'sockets_compatibles' => $this->refrigeracionAire->socketsCompatibles->pluck('nombre')->values(),
                'tdp_max_watts'       => (int) $this->refrigeracionAire->tdp_max_watts,
                'altura_mm'           => $this->refrigeracionAire->altura_mm !== null ? (int) $this->refrigeracionAire->altura_mm : null,
                'rpm_min'             => $this->refrigeracionAire->rpm_min !== null ? (int) $this->refrigeracionAire->rpm_min : null,
                'rpm_max'             => $this->refrigeracionAire->rpm_max !== null ? (int) $this->refrigeracionAire->rpm_max : null,
                'ruido_db_min'        => $this->refrigeracionAire->ruido_db_min !== null ? (float) $this->refrigeracionAire->ruido_db_min : null,
                'ruido_db_max'        => $this->refrigeracionAire->ruido_db_max !== null ? (float) $this->refrigeracionAire->ruido_db_max : null,
                'num_ventiladores'    => $this->refrigeracionAire->num_ventiladores !== null ? (int) $this->refrigeracionAire->num_ventiladores : null,
                'tam_ventilador_mm'   => $this->refrigeracionAire->tam_ventilador_mm !== null ? (int) $this->refrigeracionAire->tam_ventilador_mm : null,
                'tiene_rgb'           => (bool) $this->refrigeracionAire->tiene_rgb,
            ],
            $this->categoria === 'refrigeracion_liquida' && $this->refrigeracionLiquida !== null => [
                'tipo_refrigeracion'    => $this->refrigeracionLiquida->tipoRefrigeracion?->nombre,
                'sockets_compatibles'   => $this->refrigeracionLiquida->socketsCompatibles->pluck('nombre')->values(),
                'tdp_max_watts'         => (int) $this->refrigeracionLiquida->tdp_max_watts,
                'tam_radiador_mm'       => (int) $this->refrigeracionLiquida->tam_radiador_mm,
                'ancho_radiador_mm'     => $this->refrigeracionLiquida->ancho_radiador_mm !== null ? (int) $this->refrigeracionLiquida->ancho_radiador_mm : null,
                'alto_radiador_mm'      => $this->refrigeracionLiquida->alto_radiador_mm !== null ? (int) $this->refrigeracionLiquida->alto_radiador_mm : null,
                'grosor_radiador_mm'    => $this->refrigeracionLiquida->grosor_radiador_mm !== null ? (int) $this->refrigeracionLiquida->grosor_radiador_mm : null,
                'num_ventiladores'      => (int) $this->refrigeracionLiquida->num_ventiladores,
                'tam_ventilador_mm'     => (int) $this->refrigeracionLiquida->tam_ventilador_mm,
                'pantalla_cabezal'      => (bool) $this->refrigeracionLiquida->pantalla_cabezal,
                'flujo_personalizable'  => (bool) $this->refrigeracionLiquida->flujo_personalizable,
                'incluye_pasta_termica' => (bool) $this->refrigeracionLiquida->incluye_pasta_termica,
                'tiene_rgb'             => (bool) $this->refrigeracionLiquida->tiene_rgb,
            ],
            $this->categoria === 'ventilador' && $this->ventilador !== null => [
                'tipo'                   => $this->ventilador->tipoVentilador?->nombre,
                'rpm_min'                => $this->ventilador->rpm_min !== null ? (int) $this->ventilador->rpm_min : null,
                'rpm_max'                => $this->ventilador->rpm_max !== null ? (int) $this->ventilador->rpm_max : null,
                'ruido_db_min'           => $this->ventilador->ruido_db_min !== null ? (float) $this->ventilador->ruido_db_min : null,
                'ruido_db_max'           => $this->ventilador->ruido_db_max !== null ? (float) $this->ventilador->ruido_db_max : null,
                'flujo_aire_cfm'         => $this->ventilador->flujo_aire_cfm !== null ? (float) $this->ventilador->flujo_aire_cfm : null,
                'static_pressure_mmh2o'  => $this->ventilador->static_pressure_mmh2o !== null ? (float) $this->ventilador->static_pressure_mmh2o : null,
                'num_ventiladores'       => (int) $this->ventilador->num_ventiladores,
                'tiene_rgb'              => (bool) $this->ventilador->tiene_rgb,
                'pwm'                    => (bool) $this->ventilador->pwm,
                'tam_mm'                 => $this->ventilador->tam_mm !== null ? (int) $this->ventilador->tam_mm : null,
            ],
            default => null,
        };
    }
}

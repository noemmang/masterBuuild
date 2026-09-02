<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Forma de un componente para vistas de listado (GET /api/v1/componentes).
 * No incluye el histórico de precios ni las specs "de detalle" completas
 * (fabricante, conectores, todas las columnas...): eso solo se pide
 * cuando el usuario abre el detalle de un componente concreto, vía
 * /componentes/{uuid} y /componentes/{uuid}/precios.
 *
 * Sí incluye un bloque "specs" con lo esencial de la categoría —
 * socket/chipset/factor de forma, tipo de memoria, tipo de fuente, tipos
 * de PSU que admite un gabinete, etc. Antes esto no viajaba en el listado
 * y la tarjeta de cada componente solo tenía nombre/marca/precio: ni se
 * podía saber a simple vista si un gabinete era ITX o ATX sin abrir su
 * ficha aparte.
 *
 * Los campos precio_min, precio_max, num_tiendas, en_stock y
 * precio_min_stock vienen ya calculados desde el controller con
 * withMin/withMax/withCount/withExists, así que aquí solo se formatean.
 */
class ComponenteListadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Preferimos el precio más bajo ENTRE LAS TIENDAS CON STOCK. Si
        // ninguna tienda tiene stock ahora mismo, caemos de vuelta al
        // precio_min "a secas" (el último visto, aunque esté agotado) en
        // vez de dejar la tarjeta sin precio: el front decide qué hacer
        // con eso mostrando el badge de "Agotado" según en_stock.
        $precioMin = $this->precio_min_stock ?? $this->precio_min;

        return [
            'uuid'         => $this->uuid,
            'nombre'       => $this->nombre,
            'categoria'    => $this->categoria,
            'imagen_url'   => $this->imagen_url,
            'descripcion'  => $this->descripcion,
            'marca'        => $this->marca ? ['nombre' => $this->marca->nombre] : null,
            'precio_min'   => $precioMin !== null ? (float) $precioMin : null,
            'precio_max'   => $this->precio_max !== null ? (float) $this->precio_max : null,
            'num_tiendas'  => (int) $this->num_tiendas,
            // true si AL MENOS una tienda tiene stock ahora mismo. Si el
            // componente no tiene ninguna tienda todavía (num_tiendas=0),
            // esto viene false, pero el front no debe pintar "Agotado" en
            // ese caso (no hay dato, no es que esté agotado); distínguelo
            // con num_tiendas > 0.
            'en_stock'     => (bool) $this->en_stock,
            'specs'        => $this->specs(),
        ];
    }

    /**
     * Bloque de especificaciones según la categoría real del componente.
     * Los números se convierten explícitamente (float/int) en vez de
     * dejar que viajen tal cual: las columnas decimal (frecuencia_base_ghz,
     * voltaje...) llegan de Eloquent como string ("3.70") cuando no tienen
     * un cast declarado, y el frontend las trata como número (barras de
     * comparación, ordenación) — mandarlas como string las dejaba rotas
     * en silencio ahí también.
     */
    private function specs(): ?array
    {
        return match (true) {
            $this->categoria === 'cpu' && $this->cpu !== null => [
                'socket'               => $this->cpu->socket?->nombre,
                'arquitectura'         => $this->cpu->arquitectura?->nombre,
                'tipo_memoria'         => $this->cpu->tipoMemoria?->nombre,
                'nucleos'              => (int) $this->cpu->nucleos,
                'hilos'                => (int) $this->cpu->hilos,
                'frecuencia_base_ghz'  => (float) $this->cpu->frecuencia_base_ghz,
                'frecuencia_boost_ghz' => $this->cpu->frecuencia_boost_ghz !== null ? (float) $this->cpu->frecuencia_boost_ghz : null,
                'tdp_watts'            => (int) $this->cpu->tdp_watts,
                'grafica_integrada'    => (bool) $this->cpu->grafica_integrada,
            ],
            $this->categoria === 'gpu' && $this->gpu !== null => [
                'vram_gb'          => (int) $this->gpu->vram_gb,
                'tipo_vram'        => $this->gpu->tipoVram?->nombre,
                'arquitectura'     => $this->gpu->arquitectura?->nombre,
                'version_pcie'     => $this->gpu->versionPcie?->nombre,
                'tdp_watts'        => (int) $this->gpu->tdp_watts,
                'longitud_mm'      => (int) $this->gpu->longitud_mm,
                'psu_minima_watts' => (int) $this->gpu->psu_minima_watts,
                'ray_tracing'      => (bool) $this->gpu->ray_tracing,
            ],
            $this->categoria === 'ram' && $this->ram !== null => [
                'tipo_memoria'       => $this->ram->tipoMemoria?->nombre,
                'capacidad_total_gb' => (int) $this->ram->capacidad_total_gb,
                'modulos'            => (int) $this->ram->modulos,
                'velocidad_mhz'      => (int) $this->ram->velocidad_mhz,
                'latencia_cas'       => $this->ram->latencia_cas,
                'tiene_rgb'          => (bool) $this->ram->tiene_rgb,
            ],
            $this->categoria === 'placa_base' && $this->placaBase !== null => [
                'socket'          => $this->placaBase->socket?->nombre,
                'chipset'         => $this->placaBase->chipset?->nombre,
                'factor_forma'    => $this->placaBase->factorForma?->nombre,
                'factor_forma_id' => $this->placaBase->factor_forma_id,
                'tipo_memoria'    => $this->placaBase->tipoMemoria?->nombre,
                'slots_memoria'   => (int) $this->placaBase->slots_memoria,
                'slots_m2'        => (int) $this->placaBase->slots_m2,
                'wifi'            => (bool) $this->placaBase->wifi,
            ],
            $this->categoria === 'almacenamiento' && $this->almacenamiento !== null => [
                'tipo'                  => $this->almacenamiento->tipo,
                'interfaz'              => $this->almacenamiento->interfaz?->nombre,
                'factor_forma'          => $this->almacenamiento->factorForma?->nombre,
                'capacidad_gb'          => (int) $this->almacenamiento->capacidad_gb,
                'velocidad_lectura_mbs' => $this->almacenamiento->velocidad_lectura_mbs !== null ? (int) $this->almacenamiento->velocidad_lectura_mbs : null,
            ],
            $this->categoria === 'psu' && $this->psu !== null => [
                'vatios'        => (int) $this->psu->vatios,
                'certificacion' => $this->psu->certificacion?->nombre,
                'tipo_psu'      => $this->psu->tipoPsu?->nombre,
                'tipo_psu_id'   => $this->psu->tipo_psu_id,
                'modular'       => $this->psu->modular,
                'largo_mm'      => $this->psu->largo_mm !== null ? (int) $this->psu->largo_mm : null,
            ],
            $this->categoria === 'gabinete' && $this->gabinete !== null => [
                'tipo_gabinete'        => $this->gabinete->tipoGabinete?->nombre,
                'estructura'           => $this->gabinete->estructuraGabinete?->nombre,
                'factores_forma'       => $this->gabinete->factoresForma->pluck('nombre')->values(),
                'tipos_psu'            => $this->gabinete->tiposPsu->pluck('nombre')->values(),
                'longitud_gpu_max_mm'  => $this->gabinete->longitud_gpu_max_mm !== null ? (int) $this->gabinete->longitud_gpu_max_mm : null,
                'altura_cooler_max_mm' => $this->gabinete->altura_cooler_max_mm !== null ? (int) $this->gabinete->altura_cooler_max_mm : null,
                'largo_psu_max_mm'     => $this->gabinete->largo_psu_max_mm !== null ? (int) $this->gabinete->largo_psu_max_mm : null,
                'soporte_radiadores'   => $this->gabinete->soporte_radiadores,
                'ancho_mm'             => (int) $this->gabinete->ancho_mm,
                'alto_mm'              => (int) $this->gabinete->alto_mm,
                'profundidad_mm'       => (int) $this->gabinete->profundidad_mm,
            ],
            $this->categoria === 'refrigeracion_aire' && $this->refrigeracionAire !== null => [
                'tipo_refrigeracion'  => $this->refrigeracionAire->tipoRefrigeracion?->nombre,
                'tdp_max_watts'       => (int) $this->refrigeracionAire->tdp_max_watts,
                'altura_mm'           => $this->refrigeracionAire->altura_mm !== null ? (int) $this->refrigeracionAire->altura_mm : null,
                'sockets_compatibles' => $this->refrigeracionAire->socketsCompatibles->pluck('nombre')->values(),
            ],
            $this->categoria === 'refrigeracion_liquida' && $this->refrigeracionLiquida !== null => [
                'tipo_refrigeracion'  => $this->refrigeracionLiquida->tipoRefrigeracion?->nombre,
                'tdp_max_watts'       => (int) $this->refrigeracionLiquida->tdp_max_watts,
                'tam_radiador_mm'     => (int) $this->refrigeracionLiquida->tam_radiador_mm,
                'sockets_compatibles' => $this->refrigeracionLiquida->socketsCompatibles->pluck('nombre')->values(),
            ],
            $this->categoria === 'ventilador' && $this->ventilador !== null => [
                'tam_mm'    => $this->ventilador->tam_mm !== null ? (int) $this->ventilador->tam_mm : null,
                'tipo'      => $this->ventilador->tipoVentilador?->nombre,
                'tiene_rgb' => (bool) $this->ventilador->tiene_rgb,
            ],
            default => null,
        };
    }
}
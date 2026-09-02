<?php

namespace App\Services\Configurador;

use App\Models\Componentes\Componente;
use Illuminate\Database\Eloquent\Builder;

/**
 * Único sitio donde viven las reglas de compatibilidad física/eléctrica
 * entre categorías de componentes.
 *
 * Antes cada regla de compatibilidad se reinventaba en el sitio que la
 * necesitaba: el listado del configurador calculaba un puñado de filtros
 * sueltos en el frontend (y solo a partir de 4 de las 9 categorías:
 * cpu/placa/gpu/gabinete — psu y refrigeración nunca filtraban nada),
 * ConfiguradorController::validar() tenía su propia versión en PHP para
 * el aviso final, y RecomendadorController tenía una tercera versión
 * simplificada para el modo "constrúyeme un PC". Con tres copias
 * desincronizadas, corregir una no arreglaba las otras — por eso, por
 * ejemplo, elegir un gabinete Mini-ITX (que solo admite fuentes SFX/SFX-L)
 * seguía enseñando fuentes ATX en el listado: esa regla en concreto no
 * existía en NINGUNA de las tres copias.
 *
 * Este servicio centraliza esas reglas para que solo haya que arreglarlas
 * (o añadir una nueva) una vez. Se usa desde:
 *  - ComponenteController::index(): filtra el LISTADO de una categoría a
 *    solo lo compatible con lo que el usuario ya tiene elegido en las
 *    demás categorías, sea cual sea el orden en que las fue eligiendo
 *    (por eso recibe la selección completa, no solo "lo anterior en el
 *    formulario").
 *  - ConfiguradorController::validar(): usa consumoTotal() para que la
 *    cifra de consumo estimado no diverja entre el listado y el aviso
 *    final.
 *
 * Filosofía de qué es una regla DURA (filtra/bloquea) y qué es blanda
 * (solo advierte, en ConfiguradorController):
 *  - DURA = imposibilidad física o eléctrica: el socket no encaja, la
 *    placa no cabe en el gabinete, la fuente no llega a los vatios
 *    mínimos, el radiador no tiene dónde atornillarse...
 *  - BLANDA = recomendación de rendimiento: el disipador aguanta menos
 *    TDP del que la CPU puede alcanzar en boost, la fuente cubre el
 *    consumo pero con poco margen... Estas NO se usan para ocultar
 *    productos del listado — preferimos que el usuario vea la opción y
 *    decida, no que desaparezca sin explicación. Se quedan como
 *    advertencias en ConfiguradorController::validar().
 *
 * Cuando falta un dato (columna a null, tabla pivote sin filas) la regla
 * NO filtra: es preferible mostrar de más que ocultar por un dato que no
 * se llegó a rellenar en el seeder.
 */
class CompatibilidadService
{
    /** Vatios fijos de "resto del sistema" (placa, almacenamiento, ventiladores, RGB...) que no vienen desglosados en ninguna tabla. */
    public const CONSUMO_RESTO_SISTEMA_WATTS = 50;

    /** Margen de potencia recomendado (no obligatorio) sobre el consumo estimado. */
    public const MARGEN_PSU_RECOMENDADO = 1.2;

    /**
     * Carga los modelos de especificaciones (cpu, placaBase, ram...) a
     * partir de los uuids seleccionados en cada slot del configurador.
     * Cualquier slot vacío, con un uuid que no existe, o de un componente
     * ya no activo, queda como null: cada regla de restringir() ya sabe
     * ignorar los que sean null.
     *
     * @param array<string,string|null> $uuids ej. ['cpu_uuid' => '...', 'gabinete_uuid' => '...']
     */
    public function cargarSeleccion(array $uuids): array
    {
        $cpuComp = $this->cargar($uuids['cpu_uuid'] ?? null, ['cpu']);
        $placaComp = $this->cargar($uuids['placa_base_uuid'] ?? null, ['placaBase']);
        $ramComp = $this->cargar($uuids['ram_uuid'] ?? null, ['ram']);
        $gpuComp = $this->cargar($uuids['gpu_uuid'] ?? null, ['gpu']);
        $psuComp = $this->cargar($uuids['psu_uuid'] ?? null, ['psu']);
        $gabineteComp = $this->cargar($uuids['gabinete_uuid'] ?? null, ['gabinete.factoresForma', 'gabinete.tiposPsu']);
        $refrigComp = $this->cargar($uuids['refrigeracion_uuid'] ?? null, [
            'refrigeracionAire.socketsCompatibles',
            'refrigeracionLiquida.socketsCompatibles',
        ]);
        $almacenamientoComp = $this->cargar($uuids['almacenamiento_uuid'] ?? null, ['almacenamiento']);
        $ventiladorComp = $this->cargar($uuids['ventilador_uuid'] ?? null, ['ventilador']);

        $refrigTipo = match ($refrigComp?->categoria) {
            'refrigeracion_liquida' => 'liquida',
            'refrigeracion_aire'    => 'aire',
            default                 => null,
        };
        $refrig = match ($refrigTipo) {
            'liquida' => $refrigComp?->refrigeracionLiquida,
            'aire'    => $refrigComp?->refrigeracionAire,
            default   => null,
        };

        return [
            'cpu'            => $cpuComp?->cpu,
            'placa'          => $placaComp?->placaBase,
            'ram'            => $ramComp?->ram,
            'gpu'            => $gpuComp?->gpu,
            'psu'            => $psuComp?->psu,
            'gabinete'       => $gabineteComp?->gabinete,
            'refrigTipo'     => $refrigTipo,
            'refrig'         => $refrig,
            'almacenamiento' => $almacenamientoComp?->almacenamiento,
            'ventilador'     => $ventiladorComp?->ventilador,
        ];
    }

    private function cargar(?string $uuid, array $relaciones): ?Componente
    {
        if (!$uuid) {
            return null;
        }

        return Componente::where('uuid', $uuid)->activo()->with($relaciones)->first();
    }

    /**
     * Aplica sobre $query (un Builder de Componente ya filtrado por
     * categoria = $categoria) las restricciones de compatibilidad frente
     * a lo que haya en $seleccion (el array que devuelve
     * cargarSeleccion()). Las claves de $seleccion que no le interesan a
     * $categoria simplemente no se usan.
     */
    public function restringir(Builder $query, string $categoria, array $seleccion): Builder
    {
        $cpu       = $seleccion['cpu']            ?? null;
        $placa     = $seleccion['placa']          ?? null;
        $ram       = $seleccion['ram']            ?? null;
        $gpu       = $seleccion['gpu']            ?? null;
        $psu       = $seleccion['psu']            ?? null;
        $gabinete  = $seleccion['gabinete']       ?? null;
        $refrigTipo= $seleccion['refrigTipo']     ?? null;
        $refrig    = $seleccion['refrig']         ?? null;

        match ($categoria) {
            'cpu'                   => $this->restringirCpu($query, $placa, $ram, $refrig),
            'placa_base'            => $this->restringirPlacaBase($query, $cpu, $ram, $gabinete),
            'ram'                   => $this->restringirRam($query, $placa, $cpu),
            'gpu'                   => $this->restringirGpu($query, $gabinete, $psu),
            'psu'                   => $this->restringirPsu($query, $gabinete, $cpu, $gpu),
            'gabinete'              => $this->restringirGabinete($query, $placa, $gpu, $psu, $refrigTipo, $refrig),
            'refrigeracion_aire'    => $this->restringirRefrigAire($query, $cpu, $gabinete),
            'refrigeracion_liquida' => $this->restringirRefrigLiquida($query, $cpu, $gabinete),
            'almacenamiento'        => $this->restringirAlmacenamiento($query, $placa),
            'ventilador'            => $this->restringirVentilador($query, $gabinete),
            default                 => null,
        };

        return $query;
    }

    // ── CPU ──────────────────────────────────────────────────────────────

    private function restringirCpu(Builder $query, $placa, $ram, $refrig): void
    {
        if ($placa) {
            $query->whereHas('cpu', fn ($q) => $q
                ->where('socket_id', $placa->socket_id)
                ->where('tipo_memoria_id', $placa->tipo_memoria_id));
        } elseif ($ram) {
            $query->whereHas('cpu', fn ($q) => $q->where('tipo_memoria_id', $ram->tipo_memoria_id));
        }

        if ($refrig) {
            $socketIds = $refrig->socketsCompatibles->pluck('id');
            if ($socketIds->isNotEmpty()) {
                $query->whereHas('cpu', fn ($q) => $q->whereIn('socket_id', $socketIds));
            }
        }
    }

    // ── Placa base ───────────────────────────────────────────────────────

    private function restringirPlacaBase(Builder $query, $cpu, $ram, $gabinete): void
    {
        if ($cpu) {
            $query->whereHas('placaBase', fn ($q) => $q
                ->where('socket_id', $cpu->socket_id)
                ->where('tipo_memoria_id', $cpu->tipo_memoria_id));
        } elseif ($ram) {
            $query->whereHas('placaBase', fn ($q) => $q->where('tipo_memoria_id', $ram->tipo_memoria_id));
        }

        if ($gabinete) {
            $formaIds = $gabinete->factoresForma->pluck('id');
            if ($formaIds->isNotEmpty()) {
                $query->whereHas('placaBase', fn ($q) => $q->whereIn('factor_forma_id', $formaIds));
            }
        }
    }

    // ── RAM ──────────────────────────────────────────────────────────────

    private function restringirRam(Builder $query, $placa, $cpu): void
    {
        $tipoMemoriaId = $placa->tipo_memoria_id ?? $cpu->tipo_memoria_id ?? null;
        if ($tipoMemoriaId) {
            $query->whereHas('ram', fn ($q) => $q->where('tipo_memoria_id', $tipoMemoriaId));
        }
    }

    // ── GPU ──────────────────────────────────────────────────────────────

    private function restringirGpu(Builder $query, $gabinete, $psu): void
    {
        if ($gabinete && $gabinete->longitud_gpu_max_mm) {
            $query->whereHas('gpu', fn ($q) => $q->where('longitud_mm', '<=', $gabinete->longitud_gpu_max_mm));
        }

        if ($psu && $psu->vatios) {
            // La GPU indica la potencia mínima recomendada por el
            // fabricante para el conjunto del sistema (psu_minima_watts),
            // no solo para ella misma, así que compararla directamente
            // contra los vatios de la fuente ya elegida es correcto.
            $query->whereHas('gpu', fn ($q) => $q->where(fn ($q2) => $q2
                ->whereNull('psu_minima_watts')
                ->orWhere('psu_minima_watts', '<=', $psu->vatios)));
        }
    }

    // ── PSU ──────────────────────────────────────────────────────────────

    private function restringirPsu(Builder $query, $gabinete, $cpu, $gpu): void
    {
        if ($gabinete) {
            $tipoIds = $gabinete->tiposPsu->pluck('id');
            if ($tipoIds->isNotEmpty()) {
                // EL FIX del caso Fractal Terra: un gabinete Mini-ITX que
                // solo tiene SFX/SFX-L en su pivote gabinete_tipo_psu ya
                // no debe dejar pasar fuentes ATX.
                $query->whereHas('psu', fn ($q) => $q->whereIn('tipo_psu_id', $tipoIds));
            }

            if ($gabinete->largo_psu_max_mm) {
                $query->whereHas('psu', fn ($q) => $q->where(fn ($q2) => $q2
                    ->whereNull('largo_mm')
                    ->orWhere('largo_mm', '<=', $gabinete->largo_psu_max_mm)));
            }
        }

        $consumoMinimo = max($gpu->psu_minima_watts ?? 0, self::consumoTotal($cpu, $gpu));
        if ($consumoMinimo > 0) {
            $query->whereHas('psu', fn ($q) => $q->where('vatios', '>=', $consumoMinimo));
        }
    }

    // ── Gabinete ─────────────────────────────────────────────────────────

    private function restringirGabinete(Builder $query, $placa, $gpu, $psu, ?string $refrigTipo, $refrig): void
    {
        if ($placa) {
            $query->whereHas('gabinete', fn ($q) => $q
                ->whereHas('factoresForma', fn ($q2) => $q2->where('factores_forma.id', $placa->factor_forma_id)));
        }

        if ($gpu && $gpu->longitud_mm) {
            $query->whereHas('gabinete', fn ($q) => $q->where(fn ($q2) => $q2
                ->whereNull('longitud_gpu_max_mm')
                ->orWhere('longitud_gpu_max_mm', '>=', $gpu->longitud_mm)));
        }

        if ($psu) {
            if ($psu->tipo_psu_id) {
                // Espejo de restringirPsu(): si el gabinete no tiene filas
                // en gabinete_tipo_psu se trata como "sin restricción
                // documentada" y no se filtra (mismo criterio que ya usa
                // validarFactorForma() en ConfiguradorController).
                $query->whereHas('gabinete', fn ($q) => $q->where(fn ($q2) => $q2
                    ->whereDoesntHave('tiposPsu')
                    ->orWhereHas('tiposPsu', fn ($q3) => $q3->where('tipos_psu.id', $psu->tipo_psu_id))));
            }

            if ($psu->largo_mm) {
                $query->whereHas('gabinete', fn ($q) => $q->where(fn ($q2) => $q2
                    ->whereNull('largo_psu_max_mm')
                    ->orWhere('largo_psu_max_mm', '>=', $psu->largo_mm)));
            }
        }

        if ($refrigTipo === 'aire' && $refrig?->altura_mm) {
            $query->whereHas('gabinete', fn ($q) => $q->where(fn ($q2) => $q2
                ->whereNull('altura_cooler_max_mm')
                ->orWhere('altura_cooler_max_mm', '>=', $refrig->altura_mm)));
        }

        if ($refrigTipo === 'liquida' && $refrig?->tam_radiador_mm) {
            $tam = $refrig->tam_radiador_mm;
            $query->whereHas('gabinete', fn ($q) => $q->where(fn ($q2) => $q2
                ->whereNull('soporte_radiadores')
                ->orWhereJsonContains('soporte_radiadores', $tam)
                ->orWhereJsonContains('soporte_radiadores', (string) $tam)));
        }
    }

    // ── Refrigeración de aire ────────────────────────────────────────────

    private function restringirRefrigAire(Builder $query, $cpu, $gabinete): void
    {
        if ($cpu) {
            $query->whereHas('refrigeracionAire.socketsCompatibles', fn ($q) => $q->where('sockets.id', $cpu->socket_id));
        }

        if ($gabinete && $gabinete->altura_cooler_max_mm) {
            $query->whereHas('refrigeracionAire', fn ($q) => $q->where(fn ($q2) => $q2
                ->whereNull('altura_mm')
                ->orWhere('altura_mm', '<=', $gabinete->altura_cooler_max_mm)));
        }
    }

    // ── Refrigeración líquida (AIO) ──────────────────────────────────────

    private function restringirRefrigLiquida(Builder $query, $cpu, $gabinete): void
    {
        if ($cpu) {
            $query->whereHas('refrigeracionLiquida.socketsCompatibles', fn ($q) => $q->where('sockets.id', $cpu->socket_id));
        }

        if ($gabinete) {
            $tamanos = collect($gabinete->soporte_radiadores ?? [])->filter()->values();
            if ($tamanos->isNotEmpty()) {
                $query->whereHas('refrigeracionLiquida', fn ($q) => $q->whereIn('tam_radiador_mm', $tamanos));
            }
        }
    }

    // ── Almacenamiento ───────────────────────────────────────────────────

    private function restringirAlmacenamiento(Builder $query, $placa): void
    {
        if (!$placa) {
            return;
        }

        // Solo excluye cuando la placa declara explícitamente 0 puertos
        // de ese tipo (columna not-nullable con default 0 en el seeder),
        // no cuando el dato simplemente falta.
        if ((int) $placa->slots_m2 === 0) {
            $query->whereHas('almacenamiento.interfaz', fn ($q) => $q->where('nombre', 'not like', 'NVMe%'));
        }

        if ((int) $placa->puertos_sata === 0) {
            $query->whereHas('almacenamiento.interfaz', fn ($q) => $q->where('nombre', '!=', 'SATA III'));
        }
    }

    // ── Ventiladores de caja ─────────────────────────────────────────────

    private function restringirVentilador(Builder $query, $gabinete): void
    {
        if (!$gabinete) {
            return;
        }

        $tamanos = collect([
            $gabinete->tam_ventilador_frontal_mm,
            $gabinete->tam_ventilador_superior_mm,
            $gabinete->tam_ventilador_trasero_mm,
        ])->filter(fn ($v) => $v > 0)->unique()->values();

        if ($tamanos->isNotEmpty()) {
            $query->whereHas('ventilador', fn ($q) => $q->whereIn('tam_mm', $tamanos));
        }
    }

    // ── Consumo estimado ─────────────────────────────────────────────────

    /**
     * TDP de pico de CPU y GPU (el "peor caso": tdp_max_watts si existe,
     * si no tdp_watts) más el margen fijo de resto del sistema. Único
     * sitio donde se calcula para que el listado (filtro de vatios de la
     * PSU) y el aviso final de ConfiguradorController::validar() den
     * siempre la misma cifra.
     */
    public static function consumoTotal(?object $cpu, ?object $gpu): int
    {
        if (!$cpu && !$gpu) {
            return 0;
        }

        $tdpCpu = $cpu ? ($cpu->tdp_max_watts ?? $cpu->tdp_watts ?? 0) : 0;
        $tdpGpu = $gpu->tdp_watts ?? 0;
        $resto  = $cpu ? self::CONSUMO_RESTO_SISTEMA_WATTS : 0;

        return (int) round($tdpCpu + $tdpGpu + $resto);
    }
}
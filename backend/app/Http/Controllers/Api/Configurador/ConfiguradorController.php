<?php

namespace App\Http\Controllers\Api\Configurador;

use App\Http\Controllers\Controller;
use App\Models\Componentes\Componente;
use Illuminate\Http\Request;

/**
 * POST /v1/configurador/validar
 *
 * Recibe los uuid de los componentes seleccionados en cada slot del
 * configurador (algunos pueden venir a null si el slot todavía está vacío)
 * y devuelve un veredicto de compatibilidad: errores que bloquean el build
 * (socket distinto, no cabe físicamente...), advertencias que no lo impiden
 * pero conviene saber (poco margen de vatios, RAM por encima de la
 * frecuencia certificada...) y notas informativas neutras.
 *
 * Este archivo estaba corrupto: por un despiste al editar, todo su
 * contenido había quedado sustituido por una copia de
 * Api\Negocio\ConfiguracionController (con su mismo namespace y nombre de
 * clase), así que la ruta que lo usa fallaba con "Class not found" y el
 * panel de compatibilidad del configurador nunca llegaba a recibir datos
 * reales (el frontend traga ese error en silencio, así que no se notaba
 * a simple vista).
 *
 * El formato de la respuesta (compatible/errores/advertencias/notas/
 * consumo_total_watts) viene fijado por lo que ya espera
 * configurator.component.ts — no es un diseño nuevo, es reconstruir lo que
 * el frontend llevaba tiempo intentando consumir.
 */
class ConfiguradorController extends Controller
{
    // Margen de potencia recomendado sobre el consumo estimado. Por debajo
    // de esto la PSU "llega" pero sin holgura para picos de carga o para
    // ampliar el equipo más adelante. Mismo criterio que ya usaba
    // PSU::scopeSuficienteParaSistema() para no inventar un número nuevo.
    private const MARGEN_PSU_RECOMENDADO = 1.2;

    // Vatios fijos de "resto del sistema" (placa base, almacenamiento,
    // ventiladores, RGB...) que no viene desglosado en ninguna tabla.
    // Mismo valor que ya usa RecomendadorController::recomendar() para el
    // presupuesto de PSU, así que el consumo estimado no varía según qué
    // endpoint lo calcule.
    private const CONSUMO_RESTO_SISTEMA_WATTS = 50;

    public function validar(Request $request)
    {
        $data = $request->validate([
            'cpu_uuid'           => 'nullable|string',
            'gpu_uuid'           => 'nullable|string',
            'ram_uuid'           => 'nullable|string',
            'placa_base_uuid'    => 'nullable|string',
            'psu_uuid'           => 'nullable|string',
            'gabinete_uuid'      => 'nullable|string',
            'refrigeracion_uuid' => 'nullable|string',
            // No nos fiamos de este campo para decidir aire/líquida (ver
            // más abajo, se recalcula desde la categoría real del
            // componente); solo se valida el formato por si en el futuro
            // se usa para algo más.
            'tipo_refrigeracion' => 'nullable|in:aire,liquida',
        ]);

        // ── Cargar componentes + specs ──────────────────────────────────
        // activo(): igual que en GuardadoController/AlertaController, un
        // uuid que ya no está activo se trata como "no seleccionado" en
        // vez de reventar con un 404 en mitad de la sesión del usuario.

        $cpuComponente = $this->cargarComponente($data['cpu_uuid'] ?? null, [
            'cpu.socket', 'cpu.tipoMemoria',
        ]);
        $cpu = $cpuComponente?->cpu;

        $placaComponente = $this->cargarComponente($data['placa_base_uuid'] ?? null, [
            'placaBase.socket', 'placaBase.tipoMemoria', 'placaBase.factorForma',
        ]);
        $placa = $placaComponente?->placaBase;

        $ramComponente = $this->cargarComponente($data['ram_uuid'] ?? null, [
            'ram.tipoMemoria',
        ]);
        $ram = $ramComponente?->ram;

        $gpuComponente = $this->cargarComponente($data['gpu_uuid'] ?? null, ['gpu']);
        $gpu = $gpuComponente?->gpu;

        $psuComponente = $this->cargarComponente($data['psu_uuid'] ?? null, ['psu']);
        $psu = $psuComponente?->psu;

        $gabineteComponente = $this->cargarComponente($data['gabinete_uuid'] ?? null, [
            'gabinete.factoresForma',
        ]);
        $gabinete = $gabineteComponente?->gabinete;

        // La refrigeración puede ser de aire o líquida; se decide por la
        // categoría real del componente (no por lo que mande el frontend
        // en tipo_refrigeracion) para que el resultado no dependa de que
        // el cliente infiera bien esa categoría.
        $refrigComponente = $this->cargarComponente($data['refrigeracion_uuid'] ?? null, [
            'refrigeracionAire.socketsCompatibles',
            'refrigeracionLiquida.socketsCompatibles',
        ]);
        $refrigTipo = $refrigComponente?->categoria === 'refrigeracion_liquida' ? 'liquida'
            : ($refrigComponente?->categoria === 'refrigeracion_aire' ? 'aire' : null);
        $refrig = $refrigTipo === 'liquida' ? $refrigComponente?->refrigeracionLiquida
            : ($refrigTipo === 'aire' ? $refrigComponente?->refrigeracionAire : null);

        // ── Comprobaciones ───────────────────────────────────────────────

        $errores      = [];
        $advertencias = [];
        $notas        = [];

        $this->validarSocketYMemoria($cpu, $placa, $errores);
        $this->validarRam($ram, $cpu, $placa, $errores, $advertencias);
        $this->validarGpu($gpu, $gabinete, $psu, $errores);
        $this->validarRefrigeracion($refrig, $refrigTipo, $cpu, $gabinete, $errores, $advertencias);
        $this->validarFactorForma($placa, $gabinete, $errores);
        $this->validarPsu($psu, $gabinete, $cpu, $gpu, $errores, $advertencias, $notas);
        $this->anadirNotasInformativas($cpu, $gpu, $refrig, $notas);

        $consumoTotal = $this->calcularConsumoTotal($cpu, $gpu);

        return response()->json([
            'compatible'          => count($errores) === 0,
            'errores'             => $errores,
            'advertencias'        => $advertencias,
            'notas'               => $notas,
            'consumo_total_watts' => $consumoTotal,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function cargarComponente(?string $uuid, array $relaciones): ?Componente
    {
        if (!$uuid) {
            return null;
        }

        return Componente::where('uuid', $uuid)->activo()->with($relaciones)->first();
    }

    private function msg(string $tipo, string $mensaje): array
    {
        return ['tipo' => $tipo, 'mensaje' => $mensaje];
    }

    // CPU ↔ Placa base: mismo socket y mismo tipo de memoria. Sin esto lo
    // demás (RAM, refrigeración...) no tiene ni sentido comprobarlo, pero
    // se sigue haciendo igualmente por si el usuario corrige esto después.
    private function validarSocketYMemoria(?object $cpu, ?object $placa, array &$errores): void
    {
        if (!$cpu || !$placa) {
            return;
        }

        if ((int) $cpu->socket_id !== (int) $placa->socket_id) {
            $errores[] = $this->msg('socket', sprintf(
                'La CPU usa socket %s y la placa base es %s: no son compatibles.',
                $cpu->socket->nombre ?? 'desconocido',
                $placa->socket->nombre ?? 'desconocido',
            ));
        }

        if ((int) $cpu->tipo_memoria_id !== (int) $placa->tipo_memoria_id) {
            $errores[] = $this->msg('memoria_placa', sprintf(
                'La CPU trabaja con memoria %s pero la placa base solo admite %s.',
                $cpu->tipoMemoria->nombre ?? 'desconocida',
                $placa->tipoMemoria->nombre ?? 'desconocida',
            ));
        }
    }

    // RAM ↔ CPU/placa base: tipo de memoria (prioridad a la placa, que es
    // la que de verdad tiene los slots), velocidad certificada, capacidad
    // máxima soportada y número de módulos frente a slots disponibles.
    private function validarRam(?object $ram, ?object $cpu, ?object $placa, array &$errores, array &$advertencias): void
    {
        if (!$ram) {
            return;
        }

        $referencia       = $placa ?: $cpu;
        $nombreReferencia = $placa ? 'la placa base' : 'la CPU';

        if ($referencia && (int) $ram->tipo_memoria_id !== (int) $referencia->tipo_memoria_id) {
            $errores[] = $this->msg('memoria_ram', sprintf(
                'La RAM es %s y %s necesita %s.',
                $ram->tipoMemoria->nombre ?? 'de un tipo desconocido',
                $nombreReferencia,
                $referencia->tipoMemoria->nombre ?? 'otro tipo',
            ));
        }

        if ($placa && $placa->frecuencia_memoria_max_mhz && $ram->velocidad_mhz > $placa->frecuencia_memoria_max_mhz) {
            $advertencias[] = $this->msg('ram_velocidad', sprintf(
                'La RAM va a %d MHz pero la placa base solo certifica hasta %d MHz; funcionará, pero a menor velocidad de la que anuncia el módulo.',
                $ram->velocidad_mhz,
                $placa->frecuencia_memoria_max_mhz,
            ));
        }

        if ($placa && $placa->memoria_max_gb && $ram->capacidad_total_gb > $placa->memoria_max_gb) {
            $errores[] = $this->msg('ram_capacidad', sprintf(
                'El kit de RAM es de %d GB y la placa base soporta como máximo %d GB.',
                $ram->capacidad_total_gb,
                $placa->memoria_max_gb,
            ));
        }

        if ($placa && $ram->modulos && $placa->slots_memoria && $ram->modulos > $placa->slots_memoria) {
            $errores[] = $this->msg('ram_slots', sprintf(
                'El kit trae %d módulos y la placa base solo tiene %d slots de memoria.',
                $ram->modulos,
                $placa->slots_memoria,
            ));
        }
    }

    // GPU ↔ gabinete (longitud física) y GPU ↔ PSU (vatios mínimos que
    // pide el fabricante de la tarjeta, no el consumo total del sistema:
    // eso se comprueba aparte en validarPsu()).
    private function validarGpu(?object $gpu, ?object $gabinete, ?object $psu, array &$errores): void
    {
        if (!$gpu) {
            return;
        }

        if ($gabinete && $gabinete->longitud_gpu_max_mm && $gpu->longitud_mm > $gabinete->longitud_gpu_max_mm) {
            $errores[] = $this->msg('gpu_gabinete', sprintf(
                'La GPU mide %d mm y el gabinete admite hasta %d mm de longitud: no cabe.',
                $gpu->longitud_mm,
                $gabinete->longitud_gpu_max_mm,
            ));
        }

        if ($psu && $gpu->psu_minima_watts && $psu->vatios < $gpu->psu_minima_watts) {
            $errores[] = $this->msg('gpu_psu', sprintf(
                'El fabricante de la GPU recomienda una fuente de al menos %d W y la elegida es de %d W.',
                $gpu->psu_minima_watts,
                $psu->vatios,
            ));
        }
    }

    // Refrigeración (aire o líquida, según la categoría real del
    // componente) ↔ CPU: socket soportado y TDP máximo que aguanta.
    // Refrigeración ↔ gabinete: altura del disipador de aire, o tamaño de
    // radiador soportado en el caso de una AIO.
    private function validarRefrigeracion(
        ?object $refrig,
        ?string $refrigTipo,
        ?object $cpu,
        ?object $gabinete,
        array &$errores,
        array &$advertencias,
    ): void {
        if (!$refrig) {
            return;
        }

        if ($cpu) {
            $socketsCompatibles = $refrig->socketsCompatibles->pluck('id');

            if (!$socketsCompatibles->contains($cpu->socket_id)) {
                $errores[] = $this->msg('refrigeracion_socket', sprintf(
                    'La refrigeración elegida no tiene soporte para el socket %s de tu CPU.',
                    $cpu->socket->nombre ?? 'de tu CPU',
                ));
            }

            // tdp_max_watts (pico bajo carga/boost) y no tdp_watts (base):
            // para saber si el disipador "aguanta" interesa el peor caso,
            // no el consumo medio.
            $tdpCpu = $cpu->tdp_max_watts ?? $cpu->tdp_watts;
            if ($tdpCpu && $refrig->tdp_max_watts && $refrig->tdp_max_watts < $tdpCpu) {
                $advertencias[] = $this->msg('refrigeracion_tdp', sprintf(
                    'La refrigeración está pensada para hasta %d W y tu CPU puede llegar a %d W en carga sostenida; podría no mantenerla a buena temperatura en boost.',
                    $refrig->tdp_max_watts,
                    $tdpCpu,
                ));
            }
        }

        if (!$gabinete) {
            return;
        }

        if ($refrigTipo === 'aire' && $gabinete->altura_cooler_max_mm && $refrig->altura_mm > $gabinete->altura_cooler_max_mm) {
            $errores[] = $this->msg('refrigeracion_altura', sprintf(
                'El disipador mide %d mm de alto y el gabinete admite hasta %d mm: no cierra el panel lateral.',
                $refrig->altura_mm,
                $gabinete->altura_cooler_max_mm,
            ));
        }

        if ($refrigTipo === 'liquida') {
            $soporteRadiadores = collect($gabinete->soporte_radiadores ?? []);

            if ($soporteRadiadores->isNotEmpty() && !$soporteRadiadores->contains($refrig->tam_radiador_mm)) {
                $errores[] = $this->msg('refrigeracion_radiador', sprintf(
                    'El radiador es de %d mm y el gabinete solo tiene soporte para: %s mm.',
                    $refrig->tam_radiador_mm,
                    $soporteRadiadores->implode(', '),
                ));
            }
        }
    }

    // Placa base ↔ gabinete: el factor de forma de la placa (ATX, mATX,
    // ITX...) tiene que estar entre los que el gabinete admite.
    private function validarFactorForma(?object $placa, ?object $gabinete, array &$errores): void
    {
        if (!$placa || !$gabinete) {
            return;
        }

        $formasSoportadas = $gabinete->factoresForma->pluck('id');

        if ($formasSoportadas->isNotEmpty() && !$formasSoportadas->contains($placa->factor_forma_id)) {
            $errores[] = $this->msg('factor_forma', sprintf(
                'El gabinete no admite placas %s.',
                $placa->factorForma->nombre ?? 'de este factor de forma',
            ));
        }
    }

    // PSU ↔ consumo estimado del sistema, y PSU ↔ gabinete (longitud
    // física, para fuentes largas en gabinetes compactos).
    private function validarPsu(
        ?object $psu,
        ?object $gabinete,
        ?object $cpu,
        ?object $gpu,
        array &$errores,
        array &$advertencias,
        array &$notas,
    ): void {
        if (!$psu) {
            return;
        }

        $consumoTotal = $this->calcularConsumoTotal($cpu, $gpu);

        if ($consumoTotal > 0) {
            $margenRecomendado = (int) ceil($consumoTotal * self::MARGEN_PSU_RECOMENDADO);

            if ($psu->vatios < $consumoTotal) {
                $errores[] = $this->msg('psu_consumo', sprintf(
                    'La fuente es de %d W pero el sistema puede llegar a consumir unos %d W: el equipo podría no arrancar o apagarse bajo carga.',
                    $psu->vatios,
                    $consumoTotal,
                ));
            } elseif ($psu->vatios < $margenRecomendado) {
                $advertencias[] = $this->msg('psu_margen', sprintf(
                    'La fuente cubre el consumo estimado (%d W) pero con poco margen. Se recomiendan al menos %d W para tener un %d%% de holgura.',
                    $consumoTotal,
                    $margenRecomendado,
                    (int) round((self::MARGEN_PSU_RECOMENDADO - 1) * 100),
                ));
            } else {
                $notas[] = $this->msg('psu_margen_ok', sprintf(
                    'Buen margen de potencia: %d W de fuente frente a un consumo estimado de %d W.',
                    $psu->vatios,
                    $consumoTotal,
                ));
            }
        }

        if ($gabinete && $gabinete->largo_psu_max_mm && $psu->largo_mm && $psu->largo_mm > $gabinete->largo_psu_max_mm) {
            $errores[] = $this->msg('psu_gabinete', sprintf(
                'La fuente mide %d mm de largo y el gabinete admite hasta %d mm.',
                $psu->largo_mm,
                $gabinete->largo_psu_max_mm,
            ));
        }
    }

    // Consumo estimado del sistema: TDP de pico de CPU y GPU (el mismo
    // criterio "peor caso" que en validarRefrigeracion) más un margen fijo
    // para el resto de componentes que no tienen TDP en su ficha (placa,
    // almacenamiento, ventiladores...). El +50 W es el mismo valor que ya
    // usa RecomendadorController::recomendar() al presupuestar la PSU, así
    // que ambos endpoints coinciden en la misma estimación.
    private function calcularConsumoTotal(?object $cpu, ?object $gpu): int
    {
        if (!$cpu && !$gpu) {
            return 0;
        }

        $tdpCpu = $cpu ? ($cpu->tdp_max_watts ?? $cpu->tdp_watts ?? 0) : 0;
        $tdpGpu = $gpu->tdp_watts ?? 0;
        $resto  = $cpu ? self::CONSUMO_RESTO_SISTEMA_WATTS : 0;

        return (int) round($tdpCpu + $tdpGpu + $resto);
    }

    // Notas neutras (no son errores ni advertencias, solo información útil
    // mientras se arma el build).
    private function anadirNotasInformativas(?object $cpu, ?object $gpu, ?object $refrig, array &$notas): void
    {
        if ($cpu && $cpu->grafica_integrada && !$gpu) {
            $notas[] = $this->msg('grafica_integrada', 'Esta CPU incluye gráficos integrados: el equipo puede arrancar y usarse sin una GPU dedicada.');
        }

        if ($cpu && $cpu->incluye_cooler && !$refrig) {
            $notas[] = $this->msg('cooler_incluido', 'Esta CPU incluye disipador de fábrica: no es obligatorio añadir uno, aunque siempre puedes montar uno mejor.');
        }
    }
}
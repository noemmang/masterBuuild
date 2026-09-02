<?php

namespace App\Http\Controllers\Api\Componentes;

use App\Http\Controllers\Controller;
use App\Http\Resources\ComponenteDetalleResource;
use App\Http\Resources\ComponenteListadoResource;
use App\Models\Componentes\Componente;
use App\Services\Configurador\CompatibilidadService;
use Illuminate\Http\Request;

class ComponenteController extends Controller
{
    public function __construct(private CompatibilidadService $compatibilidad)
    {
    }

    public function index(Request $request)
    {
        // visible() (y no disponible()) para que los componentes agotados
        // no desaparezcan del listado: se siguen mostrando con su último
        // precio conocido. disponible() exige en_stock=true y es el
        // criterio de negocio que usa NotificacionesVerificar, no el de
        // "se muestra en el front".
        $query = Componente::query()->activo()->visible();

        // Filtro "ver agotados": por defecto se incluyen (mostrar_agotados
        // ausente o "true"); si el usuario lo desactiva desde el front
        // solo se listan los que tienen alguna tienda con stock ahora.
        if (!$request->boolean('mostrar_agotados', true)) {
            $query->disponible();
        }

        // ── Filtros generales ────────────────────────────────────────────────

        if ($request->filled('categoria')) {
            $query->categoria($request->categoria);
        }

        if ($request->filled('marca')) {
            $query->whereHas('marca', fn($q) =>
                $q->where('nombre', 'ilike', "%{$request->marca}%")
            );
        }

        if ($request->filled('buscar')) {
            $query->where('nombre', 'ilike', "%{$request->buscar}%");
        }

        if ($request->filled('precio_min') || $request->filled('precio_max')) {
            $precioMin = $request->filled('precio_min') ? (float) $request->precio_min : null;
            $precioMax = $request->filled('precio_max') ? (float) $request->precio_max : null;

            // precios_actuales ya tiene una única fila por (componente,
            // tienda): esto era antes un whereIn anidado con un segundo
            // MAX(id) GROUP BY sobre entradas_precio; ahora es un GROUP
            // BY directo sobre la tabla de estado actual.
            $query->whereIn('id', function ($sub) use ($precioMin, $precioMax) {
                $sub->select('componente_id')
                    ->from('precios_actuales')
                    ->groupBy('componente_id')
                    ->havingRaw('MIN(precio) ' . ($precioMin ? ">= {$precioMin}" : '>= 0'))
                    ->when($precioMax, fn($q) => $q->havingRaw("MIN(precio) <= {$precioMax}"));
            });
        }

        // ── Filtros específicos por categoría ────────────────────────────────

        if ($request->filled('capacidad_gb')) {
            $valores = explode(',', $request->capacidad_gb);
            $query->whereHas('ram', fn($q) => $q->whereIn('capacidad_gb', $valores));
        }

        if ($request->filled('vram_gb')) {
            $valores = explode(',', $request->vram_gb);
            $query->whereHas('gpu', fn($q) => $q->whereIn('vram_gb', $valores));
        }

        if ($request->filled('serie_cpu')) {
            $series = explode(',', $request->serie_cpu);
            $query->where(function($q) use ($series) {
                foreach ($series as $s) {
                    $q->orWhere('nombre', 'ilike', "% {$s} %")
                      ->orWhere('nombre', 'ilike', "%-{$s} %")
                      ->orWhere('nombre', 'ilike', "%i{$s}-%")
                      ->orWhere('nombre', 'ilike', "% {$s}-%");
                }
            });
        }

        if ($request->filled('capacidad_ssd')) {
            $valores = explode(',', $request->capacidad_ssd);
            $query->whereHas('almacenamiento', fn($q) => $q->whereIn('capacidad_gb', $valores));
        }

        if ($request->filled('mm_radiador')) {
            $valores = explode(',', $request->mm_radiador);
            // La columna real es tam_radiador_mm; "mm_radiador" no existe
            // en la tabla y esto reventaba con un error SQL en cuanto se
            // usaba este filtro (categoría refrigeración líquida en el
            // buscador general, filtro "tamaño de radiador").
            $query->whereHas('refrigeracionLiquida', fn($q) => $q->whereIn('tam_radiador_mm', $valores));
        }

        // ── Compatibilidad con la selección actual del configurador ─────────
        //
        // El front manda el uuid de lo que el usuario ya tiene elegido en
        // cada slot (los que estén vacíos simplemente no vienen). No
        // importa en qué orden los fue eligiendo: aquí siempre se parte de
        // "esto es lo que hay elegido ahora mismo" y se filtra la
        // categoría pedida contra TODO lo demás a la vez, así que elegir
        // primero el gabinete o elegirlo el último da el mismo resultado.
        //
        // Toda la lógica de qué combinaciones son compatibles vive en
        // CompatibilidadService (compartida con ConfiguradorController)
        // para que no haya una versión distinta del mismo criterio en cada
        // sitio — eso era justo lo que hacía que arreglar una regla (p.ej.
        // "el gabinete filtra la placa") no arreglara las demás (p.ej. "el
        // gabinete filtra la fuente").
        if ($request->filled('categoria')) {
            $uuidsSeleccion = $request->only([
                'cpu_uuid', 'placa_base_uuid', 'ram_uuid', 'gpu_uuid',
                'psu_uuid', 'gabinete_uuid', 'refrigeracion_uuid',
                'almacenamiento_uuid', 'ventilador_uuid',
            ]);

            if (array_filter($uuidsSeleccion)) {
                $seleccion = $this->compatibilidad->cargarSeleccion($uuidsSeleccion);
                $this->compatibilidad->restringir($query, $request->categoria, $seleccion);
            }
        }

        // ── Agregados para el listado ──────────────────────────────────────
        //
        // Antes se cargaban las relaciones completas (preciosActuales.tienda)
        // solo para que el frontend calculara el mínimo/máximo/nº de tiendas.
        // Eso traía objetos enteros (tienda...) que no se usan en la card del
        // listado. Con withMin/withMax/withCount Postgres calcula esos
        // valores en la misma query y solo viajan los escalares que
        // realmente hacen falta.

        $query
            ->withMin('preciosActuales as precio_min', 'precio')
            ->withMax('preciosActuales as precio_max', 'precio')
            ->withCount('preciosActuales as num_tiendas')
            ->withExists(['preciosActuales as en_stock' => fn ($q) => $q->where('en_stock', true)])
            ->withMin(['preciosActuales as precio_min_stock' => fn ($q) => $q->where('en_stock', true)], 'precio');

        // ── Ordenación ───────────────────────────────────────────────────────

        $ordenar = $request->get('ordenar', 'nombre');
        match($ordenar) {
            'precio_asc'  => $query->orderBy('precio_min', 'asc'),
            'precio_desc' => $query->orderBy('precio_min', 'desc'),
            default       => $query->orderBy('nombre', 'asc'),
        };

        // ── Paginación ───────────────────────────────────────────────────────
        //
        // Se precargan las relaciones de especificaciones de TODAS las
        // categorías (no solo la filtrada, porque "categoria" es opcional
        // — el buscador general lista sin filtrar por categoría) para que
        // ComponenteListadoResource pueda incluir las specs de cada
        // tarjeta sin disparar una query por fila (N+1): un componente
        // solo pertenece a una categoría, así que el resto de relaciones
        // simplemente no encuentra nada que cargar para esa fila, pero
        // sigue siendo una única query por relación (WHERE componente_id
        // IN (...)) para toda la página, no una por componente.
        $componentes = $query->with($this->relacionesListado())
            ->paginate($request->get('por_pagina', 20));

        return response()->json([
            'data'         => ComponenteListadoResource::collection($componentes->items()),
            'current_page' => $componentes->currentPage(),
            'last_page'    => $componentes->lastPage(),
            'per_page'     => $componentes->perPage(),
            'total'        => $componentes->total(),
        ]);
    }

    // Relaciones específicas por categoría — un componente solo pertenece a
    // UNA categoría, así que solo hace falta precargar las relaciones de esa
    // categoría. Antes se cargaban las ~25-30 relaciones de TODAS las
    // categorías en cada petición (una query por relación aunque no
    // aplicara), lo que hacía lento cualquier sitio que pidiera el detalle
    // de un componente (configurador al seleccionar, comparador de specs al
    // añadir una tarjeta, buscador al abrir una ficha).
    // NOTA sobre nombres: tipoVRAM/versionPCIe/tipoPSU/tipoNAND/tiposPSU se
    // renombraron a tipoVram/versionPcie/tipoPsu/tipoNand/tiposPsu en sus
    // modelos. No es cosmético: Laravel serializa las relaciones cargadas
    // usando Str::snake() sobre el nombre EXACTO del método, y
    // Str::snake('tipoVRAM') da "tipo_v_r_a_m" (separa cada mayúscula
    // suelta), no "tipo_vram". Con el nombre antiguo, cualquier sitio que
    // serializara el modelo tal cual (como hacía este show() antes de
    // pasar a ComponenteDetalleResource) mandaba esa clave rota al
    // frontend y el dato de VRAM/PCIe/tipo de fuente/NAND llegaba
    // silenciosamente vacío.
    //
    // También se han añadido aquí las relaciones auxiliares que faltaban
    // por cargar (gabinete.tiposPsu, placaBase.versionPcie,
    // almacenamiento.tipoNand, *.tipoRefrigeracion): existían como tabla y
    // como relación en el modelo, pero nunca se precargaban, así que la
    // ficha de un componente no llegaba a mostrar ese dato aunque sí
    // estuviera en la base de datos.
    private const RELACIONES_POR_CATEGORIA = [
        'cpu'                   => ['cpu.socket', 'cpu.arquitectura', 'cpu.tipoMemoria'],
        'gpu'                   => ['gpu.arquitectura', 'gpu.tipoVram', 'gpu.versionPcie'],
        'ram'                   => ['ram.tipoMemoria'],
        'placa_base'            => ['placaBase.socket', 'placaBase.chipset', 'placaBase.factorForma', 'placaBase.tipoMemoria', 'placaBase.versionPcie'],
        'almacenamiento'        => ['almacenamiento.interfaz', 'almacenamiento.factorForma', 'almacenamiento.tipoNand'],
        'psu'                   => ['psu.certificacion', 'psu.tipoPsu'],
        'gabinete'              => ['gabinete.tipoGabinete', 'gabinete.estructuraGabinete', 'gabinete.factoresForma', 'gabinete.tiposPsu'],
        'refrigeracion_aire'    => ['refrigeracionAire.socketsCompatibles', 'refrigeracionAire.tipoRefrigeracion'],
        'refrigeracion_liquida' => ['refrigeracionLiquida.socketsCompatibles', 'refrigeracionLiquida.tipoRefrigeracion'],
        'ventilador'            => ['ventilador.tipoVentilador'],
    ];

    private function relacionesListado(): array
    {
        return array_merge(['marca'], ...array_values(self::RELACIONES_POR_CATEGORIA));
    }

    public function show(string $uuid)
    {
        // visible() en vez de disponible(): el detalle de un componente
        // agotado debe poder abrirse igualmente (panel de precios en
        // search/configurador, specs para compatibilidad, etc.).
        $componente = Componente::where('uuid', $uuid)
            ->activo()
            ->visible()
            ->firstOrFail();

        $relaciones = array_merge(
            [
                'marca',
                'fabricante',
                'preciosActuales.tienda',
            ],
            self::RELACIONES_POR_CATEGORIA[$componente->categoria] ?? []
        );

        $componente->load($relaciones);

        // Resource en vez de devolver el modelo tal cual: así la forma del
        // JSON (nombres de campo, tipos numéricos reales en vez de strings
        // de decimal, claves de relación) la decide explícitamente este
        // archivo y no queda a merced de cómo Eloquent decida serializar
        // cada columna o relación.
        return new ComponenteDetalleResource($componente);
    }

    public function porCategoria(string $categoria)
    {
        if (!in_array($categoria, Componente::CATEGORIAS)) {
            return response()->json(['message' => 'Categoría no válida'], 422);
        }

        $componentes = Componente::activo()
            ->visible()
            ->categoria($categoria)
            ->with(['marca', 'preciosActuales.tienda'])
            ->paginate(20);

        return response()->json($componentes);
    }
}
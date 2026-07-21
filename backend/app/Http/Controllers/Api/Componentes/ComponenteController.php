<?php

namespace App\Http\Controllers\Api\Componentes;

use App\Http\Controllers\Controller;
use App\Http\Resources\ComponenteListadoResource;
use App\Models\Componentes\Componente;
use Illuminate\Http\Request;

class ComponenteController extends Controller
{
    public function index(Request $request)
    {
        $query = Componente::query()->activo();

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
        
            $query->whereIn('id', function ($sub) use ($precioMin, $precioMax) {
                $sub->select('componente_id')
                    ->from('entradas_precio')
                    ->whereIn('id', function ($inner) {
                        // Precio más bajo actual por componente (última entrada por tienda)
                        $inner->selectRaw('MAX(id)')
                              ->from('entradas_precio')
                              ->groupBy('componente_id', 'tienda_id');
                    })
                    ->groupBy('componente_id')
                    ->havingRaw('MIN(precio) ' . ($precioMin ? ">= {$precioMin}" : '>= 0'))
                    ->when($precioMax, fn($q) => $q->havingRaw("MIN(precio) <= {$precioMax}"));
            });
        }

        if ($request->boolean('con_cupon')) {
            $query->whereHas('cuponesActivos');
        }

        if ($request->boolean('con_regalo')) {
            $query->whereHas('regalosActivos');
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
            $query->whereHas('refrigeracionLiquida', fn($q) => $q->whereIn('mm_radiador', $valores));
        }

        // ── Filtros de compatibilidad ────────────────────────────────────────

        // CPU y Placa Base: mismo socket
        if ($request->filled('socket_id')) {
            $socketId = (int) $request->socket_id;
            $categoria = $request->get('categoria');

            if ($categoria === 'cpu') {
                $query->whereHas('cpu', fn($q) =>
                    $q->where('socket_id', $socketId)
                );
            } elseif ($categoria === 'placa_base') {
                $query->whereHas('placaBase', fn($q) =>
                    $q->where('socket_id', $socketId)
                );
            } elseif (in_array($categoria, ['refrigeracion_aire'])) {
                // El cooler debe soportar este socket
                $query->whereHas('refrigeracionAire.socketsCompatibles', fn($q) =>
                    $q->where('sockets.id', $socketId)
                );
            } elseif ($categoria === 'refrigeracion_liquida') {
                $query->whereHas('refrigeracionLiquida.socketsCompatibles', fn($q) =>
                    $q->where('sockets.id', $socketId)
                );
            }
        }

        // CPU, Placa Base y RAM: mismo tipo de memoria
        if ($request->filled('tipo_memoria_id')) {
            $tipoMemoriaId = (int) $request->tipo_memoria_id;
            $categoria = $request->get('categoria');

            if ($categoria === 'cpu') {
                $query->whereHas('cpu', fn($q) =>
                    $q->where('tipo_memoria_id', $tipoMemoriaId)
                );
            } elseif ($categoria === 'placa_base') {
                $query->whereHas('placaBase', fn($q) =>
                    $q->where('tipo_memoria_id', $tipoMemoriaId)
                );
            } elseif ($categoria === 'ram') {
                $query->whereHas('ram', fn($q) =>
                    $q->where('tipo_memoria_id', $tipoMemoriaId)
                );
            }
        }

        // GPU: debe caber en el gabinete (longitud_mm <= longitud_gpu_max_mm del gabinete)
        if ($request->filled('longitud_max_mm')) {
            $query->whereHas('gpu', fn($q) =>
                $q->where('longitud_mm', '<=', (int) $request->longitud_max_mm)
            );
        }

        // Gabinete: debe admitir la GPU seleccionada (longitud_gpu_max_mm >= longitud GPU)
        if ($request->filled('longitud_gpu_min_mm')) {
            $query->whereHas('gabinete', fn($q) =>
                $q->where('longitud_gpu_max_mm', '>=', (int) $request->longitud_gpu_min_mm)
            );
        }

        // Gabinete: debe soportar el factor forma de la placa base
        if ($request->filled('factor_forma_soportado_id')) {
            $factorFormaId = (int) $request->factor_forma_soportado_id;
            $query->whereHas('gabinete', fn($q) =>
                $q->whereHas('factoresForma', fn($q2) =>
                    $q2->where('factores_forma.id', $factorFormaId)
                )
            );
        }

        // PSU: potencia mínima requerida por la GPU
        if ($request->filled('potencia_min')) {
            $query->whereHas('psu', fn($q) =>
                $q->where('vatios', '>=', (int) $request->potencia_min)
            );
        }

        // Cooler aire: altura máxima que admite el gabinete
        if ($request->filled('altura_max_mm')) {
            $query->whereHas('refrigeracionAire', fn($q) =>
                $q->where('altura_mm', '<=', (int) $request->altura_max_mm)
            );
        }

        // Cooler aire: TDP mínimo que debe aguantar
        if ($request->filled('tdp_min')) {
            $query->whereHas('refrigeracionAire', fn($q) =>
                $q->where('tdp_max_watts', '>=', (int) $request->tdp_min)
            );
        }

        // AIO: tamaños de radiador admitidos por el gabinete (csv: "240,280,360")
        if ($request->filled('radiador_mm') && $request->get('categoria') === 'refrigeracion_liquida') {
            $tamanios = array_map('intval', explode(',', $request->radiador_mm));
            $query->whereHas('refrigeracionLiquida', fn($q) =>
                $q->whereIn('tam_radiador_mm', $tamanios)
            );
        }

        // ── Agregados para el listado ──────────────────────────────────────
        //
        // Antes se cargaban las relaciones completas (preciosActuales.tienda,
        // cuponesActivos, regalosActivos) solo para que el frontend calculara
        // el mínimo/máximo/nº de tiendas y comprobara si había algún cupón o
        // regalo. Eso traía objetos enteros (tienda, cupón, regalo...) que no
        // se usan en la card del listado. Con withMin/withMax/withCount/
        // withExists Postgres calcula esos valores en la misma query y solo
        // viajan los escalares que realmente hacen falta.

        $query
            ->withMin('preciosActuales as precio_min', 'precio')
            ->withMax('preciosActuales as precio_max', 'precio')
            ->withCount('preciosActuales as num_tiendas')
            ->withExists('cuponesActivos as tiene_cupon')
            ->withExists('regalosActivos as tiene_regalo');

        // ── Ordenación ───────────────────────────────────────────────────────

        $ordenar = $request->get('ordenar', 'nombre');
        match($ordenar) {
            'precio_asc'  => $query->orderBy('precio_min', 'asc'),
            'precio_desc' => $query->orderBy('precio_min', 'desc'),
            default       => $query->orderBy('nombre', 'asc'),
        };

        // ── Paginación ───────────────────────────────────────────────────────

        $componentes = $query->with(['marca'])
            ->paginate($request->get('por_pagina', 20));

        return response()->json([
            'data'         => ComponenteListadoResource::collection($componentes->items()),
            'current_page' => $componentes->currentPage(),
            'last_page'    => $componentes->lastPage(),
            'per_page'     => $componentes->perPage(),
            'total'        => $componentes->total(),
        ]);
    }

    public function show(string $uuid)
    {
        $componente = Componente::where('uuid', $uuid)
            ->activo()
            ->with([
                'marca',
                'fabricante',
                'preciosActuales.tienda',
                'preciosActuales.cupon',
                'cuponesActivos.tienda',
                'regalosActivos',
                'cpu.socket',
                'cpu.arquitectura',
                'cpu.tipoMemoria',
                'gpu.arquitectura',
                'gpu.tipoVRAM',
                'gpu.versionPCIe',
                'ram.tipoMemoria',
                'placaBase.socket',
                'placaBase.chipset',
                'placaBase.factorForma',
                'placaBase.tipoMemoria',
                'almacenamiento.interfaz',
                'almacenamiento.factorForma',
                'psu.certificacion',
                'psu.tipoPSU',
                'gabinete.tipoGabinete',
                'gabinete.estructuraGabinete',
                'gabinete.factoresForma',
                'refrigeracionAire.socketsCompatibles',
                'refrigeracionLiquida.socketsCompatibles',
                'ventilador.tipoVentilador',
            ])
            ->firstOrFail();

        return response()->json($componente);
    }

    public function porCategoria(string $categoria)
    {
        if (!in_array($categoria, Componente::CATEGORIAS)) {
            return response()->json(['message' => 'Categoría no válida'], 422);
        }

        $componentes = Componente::activo()
            ->categoria($categoria)
            ->with(['marca', 'preciosActuales.tienda'])
            ->paginate(20);

        return response()->json($componentes);
    }
}
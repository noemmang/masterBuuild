<?php

namespace App\Http\Controllers\Api\Componentes;

use App\Http\Controllers\Controller;
use App\Models\Componentes\Componente;
use Illuminate\Http\Request;

class ComponenteController extends Controller
{
    public function index(Request $request)
    {
        $query = Componente::query()->activo();

        // Filtro por categoría
        if ($request->filled('categoria')) {
            $query->categoria($request->categoria);
        }

        // Filtro por marca
        if ($request->filled('marca')) {
            $query->whereHas('marca', fn($q) =>
                $q->where('nombre', 'ilike', "%{$request->marca}%")
            );
        }

        // Búsqueda por nombre
        if ($request->filled('buscar')) {
            $query->where('nombre', 'ilike', "%{$request->buscar}%");
        }

        // Filtro por precio máximo
        if ($request->filled('precio_max')) {
            $query->whereHas('preciosActuales', fn($q) =>
                $q->where('precio', '<=', $request->precio_max)
                  ->where('en_stock', true)
            );
        }

        // Filtro solo con cupón
        if ($request->boolean('con_cupon')) {
            $query->whereHas('cuponesActivos');
        }

        // Filtro solo con regalo
        if ($request->boolean('con_regalo')) {
            $query->whereHas('regalosActivos');
        }

        // Ordenación
        $ordenar = $request->get('ordenar', 'nombre');
        match($ordenar) {
            'precio_asc'  => $query->withMin('preciosActuales', 'precio')->orderBy('precios_actuales_min_precio', 'asc'),
            'precio_desc' => $query->withMin('preciosActuales', 'precio')->orderBy('precios_actuales_min_precio', 'desc'),
            default       => $query->orderBy('nombre', 'asc'),
        };

        $componentes = $query->with([
            'marca',
            'fabricante',
            'preciosActuales.tienda',
            'cuponesActivos',
            'regalosActivos',
        ])->paginate($request->get('por_pagina', 20));

         // ── Filtros específicos por categoría ───────────────────────────────

        // RAM: capacidad_gb (8, 16, 32, 64)
        if ($request->filled('capacidad_gb')) {
            $valores = explode(',', $request->capacidad_gb);
            $query->whereHas('ram', fn($q) => $q->whereIn('capacidad_gb', $valores));
        }

        // GPU: vram_gb (4, 8, 12, 16, 20, 24)
        if ($request->filled('vram_gb')) {
            $valores = explode(',', $request->vram_gb);
            $query->whereHas('gpu', fn($q) => $q->whereIn('vram_gb', $valores));
        }

        // CPU: serie (3, 5, 7, 9) — filtrado por nombre con LIKE OR
        if ($request->filled('serie_cpu')) {
            $series = explode(',', $request->serie_cpu);
            $query->where(function($q) use ($series) {
                foreach ($series as $s) {
                    $q->orWhere('nombre', 'ilike', "% {$s} %")
                    ->orWhere('nombre', 'ilike', "%-{$s} %")
                    ->orWhere('nombre', 'ilike', "%i{$s}-%")   // Intel iX-XXXX
                    ->orWhere('nombre', 'ilike', "% {$s}-%");   // Ryzen X XXXX
                }
            });
        }

        // Almacenamiento: capacidad_gb (256, 512, 1000, 2000, 4000)
        if ($request->filled('capacidad_ssd')) {
            $valores = explode(',', $request->capacidad_ssd);
            $query->whereHas('almacenamiento', fn($q) => $q->whereIn('capacidad_gb', $valores));
        }

        // PSU: potencia mínima
        if ($request->filled('potencia_min')) {
            $query->whereHas('psu', fn($q) => $q->where('potencia_w', '>=', (int)$request->potencia_min));
        }

        // Gabinete: factor forma soportado (ATX, mATX, ITX)
        if ($request->filled('factor_forma_soportado')) {
            $valores = explode(',', $request->factor_forma_soportado);
            // Filtro por nombre del tipo de gabinete (Mid Tower soporta ATX/mATX, Mini-ITX solo ITX)
            $query->whereHas('gabinete.tipoGabinete', fn($q) => $q->whereIn('nombre', $valores));
        }

        // AIO: tamaño de radiador en mm
        if ($request->filled('mm_radiador')) {
            $valores = explode(',', $request->mm_radiador);
            $query->whereHas('refrigeracionLiquida', fn($q) => $q->whereIn('mm_radiador', $valores));
        }

        return response()->json($componentes);
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
            return response()->json([
                'message' => 'Categoría no válida',
            ], 422);
        }

        $componentes = Componente::activo()
            ->categoria($categoria)
            ->with(['marca', 'preciosActuales.tienda'])
            ->paginate(20);

        return response()->json($componentes);
    }
}
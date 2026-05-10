<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Negocio\Cupon;
use App\Models\Negocio\Tienda;
use App\Models\Componentes\Componente;

class CuponesSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Cargar tiendas ─────────────────────────────────────────────────────
        $t = [];
        foreach (Tienda::all() as $tienda) {
            $t[$tienda->nombre] = $tienda;
        }

        // ── 2. Definición de cupones (una entrada por tienda) ─────────────────────
        //
        // Criterios de diseño:
        //   · Cada tienda tiene entre 1 y 3 cupones con tipos y descuentos realistas.
        //   · Los cupones de porcentaje aplican normalmente a categorías específicas.
        //   · Los cupones de importe fijo suelen tener mínimo de compra.
        //   · Los vencimientos varían para simular campañas solapadas.
        //
        $definiciones = [

            // ── PCComponentes ──────────────────────────────────────────────────────
            [
                'tienda_id'            => $t['PCComponentes']->id,
                'codigo'               => 'PCCOM10',
                'descripcion'          => '10% de descuento en GPUs seleccionadas',
                'tipo'                 => 'porcentaje',
                'porcentaje_descuento' => 10.00,
                'descuento_fijo'       => null,
                'minimo_compra'        => 300.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(1),
                'activo'               => true,
            ],
            [
                'tienda_id'            => $t['PCComponentes']->id,
                'codigo'               => 'PCCOM30',
                'descripcion'          => '30€ de descuento en compras superiores a 500€',
                'tipo'                 => 'fijo',
                'porcentaje_descuento' => null,
                'descuento_fijo'       => 30.00,
                'minimo_compra'        => 500.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(2),
                'activo'               => true,
            ],
            [
                'tienda_id'            => $t['PCComponentes']->id,
                'codigo'               => 'PCCOM5CPU',
                'descripcion'          => '5% de descuento en CPUs AMD y Intel',
                'tipo'                 => 'porcentaje',
                'porcentaje_descuento' => 5.00,
                'descuento_fijo'       => null,
                'minimo_compra'        => 150.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addWeeks(3),
                'activo'               => true,
            ],

            // ── Amazon España ──────────────────────────────────────────────────────
            [
                'tienda_id'            => $t['Amazon España']->id,
                'codigo'               => 'AMZCPU15',
                'descripcion'          => '15% de descuento en CPUs Intel seleccionadas',
                'tipo'                 => 'porcentaje',
                'porcentaje_descuento' => 15.00,
                'descuento_fijo'       => null,
                'minimo_compra'        => 200.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addWeeks(2),
                'activo'               => true,
            ],
            [
                'tienda_id'            => $t['Amazon España']->id,
                'codigo'               => 'AMZ25REFRIG',
                'descripcion'          => '25€ de descuento en sistemas de refrigeración',
                'tipo'                 => 'fijo',
                'porcentaje_descuento' => null,
                'descuento_fijo'       => 25.00,
                'minimo_compra'        => 80.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(1),
                'activo'               => true,
            ],

            // ── Coolmod ────────────────────────────────────────────────────────────
            [
                'tienda_id'            => $t['Coolmod']->id,
                'codigo'               => 'COOL20',
                'descripcion'          => '20€ de descuento sin mínimo de compra',
                'tipo'                 => 'fijo',
                'porcentaje_descuento' => null,
                'descuento_fijo'       => 20.00,
                'minimo_compra'        => null,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(3),
                'activo'               => true,
            ],
            [
                'tienda_id'            => $t['Coolmod']->id,
                'codigo'               => 'COOL8GPU',
                'descripcion'          => '8% de descuento en GPUs NVIDIA y AMD',
                'tipo'                 => 'porcentaje',
                'porcentaje_descuento' => 8.00,
                'descuento_fijo'       => null,
                'minimo_compra'        => 350.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(2),
                'activo'               => true,
            ],

            // ── MediaMarkt ─────────────────────────────────────────────────────────
            [
                'tienda_id'            => $t['MediaMarkt']->id,
                'codigo'               => 'MM50GAMING',
                'descripcion'          => '50€ de descuento en componentes gaming superiores a 600€',
                'tipo'                 => 'fijo',
                'porcentaje_descuento' => null,
                'descuento_fijo'       => 50.00,
                'minimo_compra'        => 600.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(1),
                'activo'               => true,
            ],
            [
                'tienda_id'            => $t['MediaMarkt']->id,
                'codigo'               => 'MM7PSU',
                'descripcion'          => '7% de descuento en fuentes de alimentación',
                'tipo'                 => 'porcentaje',
                'porcentaje_descuento' => 7.00,
                'descuento_fijo'       => null,
                'minimo_compra'        => 100.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addWeeks(6),
                'activo'               => true,
            ],

            // ── Alternate ──────────────────────────────────────────────────────────
            [
                'tienda_id'            => $t['Alternate']->id,
                'codigo'               => 'ALT12CPU',
                'descripcion'          => '12% de descuento en CPUs AMD Ryzen',
                'tipo'                 => 'porcentaje',
                'porcentaje_descuento' => 12.00,
                'descuento_fijo'       => null,
                'minimo_compra'        => 250.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(2),
                'activo'               => true,
            ],
            [
                'tienda_id'            => $t['Alternate']->id,
                'codigo'               => 'ALT15REFRIG',
                'descripcion'          => '15€ de descuento en refrigeraciones de aire',
                'tipo'                 => 'fijo',
                'porcentaje_descuento' => null,
                'descuento_fijo'       => 15.00,
                'minimo_compra'        => 50.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(1),
                'activo'               => true,
            ],

            // ── Neobyte ────────────────────────────────────────────────────────────
            [
                'tienda_id'            => $t['Neobyte']->id,
                'codigo'               => 'NEO10COOLING',
                'descripcion'          => '10% de descuento en refrigeración líquida',
                'tipo'                 => 'porcentaje',
                'porcentaje_descuento' => 10.00,
                'descuento_fijo'       => null,
                'minimo_compra'        => 70.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(2),
                'activo'               => true,
            ],
            [
                'tienda_id'            => $t['Neobyte']->id,
                'codigo'               => 'NEO20MB',
                'descripcion'          => '20€ de descuento en placas base superiores a 200€',
                'tipo'                 => 'fijo',
                'porcentaje_descuento' => null,
                'descuento_fijo'       => 20.00,
                'minimo_compra'        => 200.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addWeeks(5),
                'activo'               => true,
            ],

            // ── PcBox ──────────────────────────────────────────────────────────────
            [
                'tienda_id'            => $t['PcBox']->id,
                'codigo'               => 'PCBOX6VENT',
                'descripcion'          => '6% de descuento en packs de ventiladores ARGB',
                'tipo'                 => 'porcentaje',
                'porcentaje_descuento' => 6.00,
                'descuento_fijo'       => null,
                'minimo_compra'        => 40.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(3),
                'activo'               => true,
            ],
            [
                'tienda_id'            => $t['PcBox']->id,
                'codigo'               => 'PCBOX10GAB',
                'descripcion'          => '10% de descuento en gabinetes',
                'tipo'                 => 'porcentaje',
                'porcentaje_descuento' => 10.00,
                'descuento_fijo'       => null,
                'minimo_compra'        => 80.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(2),
                'activo'               => true,
            ],

            // ── Red Computer ───────────────────────────────────────────────────────
            [
                'tienda_id'            => $t['Red Computer']->id,
                'codigo'               => 'RED15HIGHEND',
                'descripcion'          => '15% de descuento en componentes de gama alta',
                'tipo'                 => 'porcentaje',
                'porcentaje_descuento' => 15.00,
                'descuento_fijo'       => null,
                'minimo_compra'        => 500.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(1),
                'activo'               => true,
            ],

            // ── Info Computer ──────────────────────────────────────────────────────
            [
                'tienda_id'            => $t['Info Computer']->id,
                'codigo'               => 'INFO10RAM',
                'descripcion'          => '10% de descuento en kits de memoria RAM DDR5',
                'tipo'                 => 'porcentaje',
                'porcentaje_descuento' => 10.00,
                'descuento_fijo'       => null,
                'minimo_compra'        => 80.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(2),
                'activo'               => true,
            ],
            [
                'tienda_id'            => $t['Info Computer']->id,
                'codigo'               => 'INFO30SSD',
                'descripcion'          => '30€ de descuento en SSDs NVMe Gen 4 y Gen 5',
                'tipo'                 => 'fijo',
                'porcentaje_descuento' => null,
                'descuento_fijo'       => 30.00,
                'minimo_compra'        => 150.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addWeeks(4),
                'activo'               => true,
            ],

            // ── Life Informática ───────────────────────────────────────────────────
            [
                'tienda_id'            => $t['Life Informática']->id,
                'codigo'               => 'LIFE8COOLING',
                'descripcion'          => '8% de descuento en refrigeración aire y líquida',
                'tipo'                 => 'porcentaje',
                'porcentaje_descuento' => 8.00,
                'descuento_fijo'       => null,
                'minimo_compra'        => 60.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(2),
                'activo'               => true,
            ],

            // ── FNAC ───────────────────────────────────────────────────────────────
            [
                'tienda_id'            => $t['FNAC']->id,
                'codigo'               => 'FNAC10PC',
                'descripcion'          => '10% de descuento en componentes PC seleccionados',
                'tipo'                 => 'porcentaje',
                'porcentaje_descuento' => 10.00,
                'descuento_fijo'       => null,
                'minimo_compra'        => 100.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(1),
                'activo'               => true,
            ],
            [
                'tienda_id'            => $t['FNAC']->id,
                'codigo'               => 'FNAC20CPU',
                'descripcion'          => '20€ de descuento en procesadores superiores a 300€',
                'tipo'                 => 'fijo',
                'porcentaje_descuento' => null,
                'descuento_fijo'       => 20.00,
                'minimo_compra'        => 300.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addWeeks(3),
                'activo'               => true,
            ],

            // ── Worten ─────────────────────────────────────────────────────────────
            [
                'tienda_id'            => $t['Worten']->id,
                'codigo'               => 'WORT5COMP',
                'descripcion'          => '5% de descuento general en componentes PC',
                'tipo'                 => 'porcentaje',
                'porcentaje_descuento' => 5.00,
                'descuento_fijo'       => null,
                'minimo_compra'        => 50.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(3),
                'activo'               => true,
            ],
            [
                'tienda_id'            => $t['Worten']->id,
                'codigo'               => 'WORT40GPU',
                'descripcion'          => '40€ de descuento en GPUs superiores a 500€',
                'tipo'                 => 'fijo',
                'porcentaje_descuento' => null,
                'descuento_fijo'       => 40.00,
                'minimo_compra'        => 500.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(1),
                'activo'               => true,
            ],

            // ── CaseKing ───────────────────────────────────────────────────────────
            [
                'tienda_id'            => $t['CaseKing']->id,
                'codigo'               => 'CKGAB12',
                'descripcion'          => '12% de descuento en gabinetes Full Tower y Mid Tower',
                'tipo'                 => 'porcentaje',
                'porcentaje_descuento' => 12.00,
                'descuento_fijo'       => null,
                'minimo_compra'        => 100.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(2),
                'activo'               => true,
            ],
            [
                'tienda_id'            => $t['CaseKing']->id,
                'codigo'               => 'CKFAN10',
                'descripcion'          => '10% de descuento en packs de ventiladores',
                'tipo'                 => 'porcentaje',
                'porcentaje_descuento' => 10.00,
                'descuento_fijo'       => null,
                'minimo_compra'        => 30.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addWeeks(8),
                'activo'               => true,
            ],

            // ── APP Informática ────────────────────────────────────────────────────
            [
                'tienda_id'            => $t['APP Informática']->id,
                'codigo'               => 'APP10INTEL',
                'descripcion'          => '10% de descuento en procesadores Intel Core Ultra',
                'tipo'                 => 'porcentaje',
                'porcentaje_descuento' => 10.00,
                'descuento_fijo'       => null,
                'minimo_compra'        => 300.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(2),
                'activo'               => true,
            ],

            // ── Aussar ─────────────────────────────────────────────────────────────
            [
                'tienda_id'            => $t['Aussar']->id,
                'codigo'               => 'AUSS8RAM',
                'descripcion'          => '8% de descuento en memoria RAM DDR4 y DDR5',
                'tipo'                 => 'porcentaje',
                'porcentaje_descuento' => 8.00,
                'descuento_fijo'       => null,
                'minimo_compra'        => 60.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(2),
                'activo'               => true,
            ],
            [
                'tienda_id'            => $t['Aussar']->id,
                'codigo'               => 'AUSS25CPU',
                'descripcion'          => '25€ de descuento en CPUs superiores a 400€',
                'tipo'                 => 'fijo',
                'porcentaje_descuento' => null,
                'descuento_fijo'       => 25.00,
                'minimo_compra'        => 400.00,
                'fecha_inicio'         => now(),
                'fecha_expiracion'     => now()->addMonths(1),
                'activo'               => true,
            ],
        ];

        // ── 3. Persistir cupones y construir índice por código ────────────────────
        $cupones = [];
        foreach ($definiciones as $datos) {
            $cupones[$datos['codigo']] = Cupon::create($datos);
        }

        // ── 4. Seleccionar el 20 % de los componentes de forma determinista ───────
        //
        // values() resetea las claves a índices ordinales (0, 1, 2…) antes del
        // filter; sin él la clave sería el ID del modelo (arbitrario) y % 5 no
        // garantizaría el 20 % esperado.
        //
        $seleccionados = Componente::orderBy('id')->get()->values()
            ->filter(fn ($c, $index) => $index % 5 === 0);

        // ── 5. Asociar cupones a componentes según su categoría ───────────────────
        //
        // Cada categoría recibe los cupones temáticamente más coherentes.
        // Un componente puede estar asociado a más de un cupón si ambos son
        // aplicables (p. ej., uno de porcentaje y uno de importe fijo de la
        // misma o distinta tienda).
        //
        $mapa = [
            // categoría => [códigos de cupón aplicables]
            'gpu' => [
                'PCCOM10',    // PCComponentes 10% GPUs
                'PCCOM30',    // PCComponentes 30€ (gama alta)
                'COOL8GPU',   // Coolmod 8% GPUs
                'WORT40GPU',  // Worten 40€ GPUs
                'RED15HIGHEND', // Red Computer 15% gama alta
            ],
            'cpu' => [
                'PCCOM5CPU',  // PCComponentes 5% CPUs
                'PCCOM30',    // PCComponentes 30€ (gama alta)
                'AMZCPU15',   // Amazon 15% CPUs Intel
                'ALT12CPU',   // Alternate 12% CPUs AMD
                'APP10INTEL', // APP Informática 10% Intel Ultra
                'FNAC20CPU',  // FNAC 20€ CPUs
                'AUSS25CPU',  // Aussar 25€ CPUs
                'RED15HIGHEND',
            ],
            'refrigeracion_liquida' => [
                'AMZ25REFRIG',  // Amazon 25€ refrigeración
                'NEO10COOLING', // Neobyte 10% refrigeración líquida
                'LIFE8COOLING', // Life Informática 8% cooling
            ],
            'refrigeracion_aire' => [
                'ALT15REFRIG',  // Alternate 15€ aire
                'LIFE8COOLING', // Life Informática 8% cooling
            ],
            'ventilador' => [
                'PCBOX6VENT', // PcBox 6% ventiladores
                'CKFAN10',    // CaseKing 10% ventiladores
            ],
            'gabinete' => [
                'PCBOX10GAB', // PcBox 10% gabinetes
                'CKGAB12',    // CaseKing 12% gabinetes
            ],
            'psu' => [
                'MM7PSU',    // MediaMarkt 7% PSU
                'WORT5COMP', // Worten 5% general
            ],
            'placa_base' => [
                'NEO20MB',   // Neobyte 20€ placas base
                'WORT5COMP', // Worten 5% general
                'FNAC10PC',  // FNAC 10% general
            ],
            'ram' => [
                'INFO10RAM', // Info Computer 10% RAM
                'AUSS8RAM',  // Aussar 8% RAM
            ],
            'almacenamiento' => [
                'INFO30SSD', // Info Computer 30€ SSDs
                'MM50GAMING', // MediaMarkt 50€ (gama alta)
            ],
        ];

        foreach ($seleccionados as $componente) {
            $codigos = $mapa[$componente->categoria] ?? ['WORT5COMP'];

            // Filtramos solo los cupones que realmente existen (por si alguno
            // no se creó por algún motivo) antes de hacer el attach.
            $ids = collect($codigos)
                ->filter(fn ($codigo) => isset($cupones[$codigo]))
                ->map(fn ($codigo) => $cupones[$codigo]->id)
                ->unique()
                ->values()
                ->all();

            if (empty($ids)) {
                continue;
            }

            foreach ($ids as $cuponId) {
                $componente->cupones()->syncWithoutDetaching([$cuponId]);
            }
        }
    }
}
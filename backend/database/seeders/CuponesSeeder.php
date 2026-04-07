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
        $pccomponentes = Tienda::where('nombre', 'PCComponentes')->first();
        $amazon        = Tienda::where('nombre', 'Amazon España')->first();
        $coolmod       = Tienda::where('nombre', 'Coolmod')->first();

        $cupones = [
            // PCComponentes — descuento porcentaje
            [
                'tienda_id'            => $pccomponentes->id,
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
            // PCComponentes — descuento fijo
            [
                'tienda_id'            => $pccomponentes->id,
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
            // Amazon — porcentaje
            [
                'tienda_id'            => $amazon->id,
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
            // Coolmod — descuento fijo
            [
                'tienda_id'            => $coolmod->id,
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
            // Cupón expirado (para probar historial)
            [
                'tienda_id'            => $pccomponentes->id,
                'codigo'               => 'EXPIRADO',
                'descripcion'          => 'Cupón expirado de prueba',
                'tipo'                 => 'porcentaje',
                'porcentaje_descuento' => 5.00,
                'descuento_fijo'       => null,
                'minimo_compra'        => null,
                'fecha_inicio'         => now()->subMonths(2),
                'fecha_expiracion'     => now()->subMonth(),
                'activo'               => false,
            ],
        ];

        foreach ($cupones as $cuponData) {
            $cupon = Cupon::create($cuponData);
        }

        // Asociar cupones a componentes
        $rtx4070tiSuper = Componente::where('nombre', 'ASUS TUF Gaming RTX 4070 Ti Super OC 16GB')->first();
        $rtx5080        = Componente::where('nombre', 'MSI Gaming Trio RTX 5080 16GB')->first();
        $i9             = Componente::where('nombre', 'Intel Core i9-14900K')->first();
        $ultra9         = Componente::where('nombre', 'Intel Core Ultra 9 285K')->first();

        $cuponPccom10  = Cupon::where('codigo', 'PCCOM10')->first();
        $cuponPccom30  = Cupon::where('codigo', 'PCCOM30')->first();
        $cuponAmz15    = Cupon::where('codigo', 'AMZCPU15')->first();
        $cuponCool20   = Cupon::where('codigo', 'COOL20')->first();

        // PCCOM10 aplica a GPUs
        $cuponPccom10->componentes()->attach([
            $rtx4070tiSuper->id,
            $rtx5080->id,
        ]);

        // PCCOM30 aplica a todo
        $cuponPccom30->componentes()->attach([
            $rtx4070tiSuper->id,
            $rtx5080->id,
            $i9->id,
            $ultra9->id,
        ]);

        // AMZCPU15 aplica a CPUs Intel
        $cuponAmz15->componentes()->attach([
            $i9->id,
            $ultra9->id,
        ]);

        // COOL20 aplica a todo
        $cuponCool20->componentes()->attach([
            $rtx4070tiSuper->id,
            $rtx5080->id,
            $i9->id,
            $ultra9->id,
        ]);
    }
}
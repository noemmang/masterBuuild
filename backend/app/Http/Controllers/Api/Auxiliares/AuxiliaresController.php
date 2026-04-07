<?php

namespace App\Http\Controllers\Api\Auxiliares;

use App\Http\Controllers\Controller;
use App\Models\Auxiliares\Socket;
use App\Models\Auxiliares\Marca;
use App\Models\Auxiliares\TipoMemoria;
use App\Models\Auxiliares\TipoVRAM;
use App\Models\Auxiliares\Arquitectura;
use App\Models\Auxiliares\Chipset;
use App\Models\Auxiliares\FactorForma;
use App\Models\Auxiliares\TipoGabinete;
use App\Models\Auxiliares\EstructuraGabinete;
use App\Models\Auxiliares\InterfazAlmacenamiento;
use App\Models\Auxiliares\FactorFormaAlmacenamiento;
use App\Models\Auxiliares\CertificacionPSU;
use App\Models\Auxiliares\TipoPSU;
use App\Models\Auxiliares\TipoNAND;
use App\Models\Auxiliares\VersionPCIe;
use App\Models\Auxiliares\TipoRefrigeracion;
use App\Models\Auxiliares\TipoVentilador;

class AuxiliaresController extends Controller
{
    // Devuelve todos los catálogos de una sola vez
    // Angular los carga al iniciar la app y los cachea
    public function index()
    {
        return response()->json([
            'sockets'                    => Socket::where('activo', true)->get(['id', 'uuid', 'nombre', 'fabricante']),
            'marcas'                     => Marca::get(['id', 'uuid', 'nombre', 'tipo']),
            'tipos_memoria'              => TipoMemoria::where('activo', true)->get(['id', 'uuid', 'nombre']),
            'tipos_vram'                 => TipoVRAM::where('activo', true)->get(['id', 'uuid', 'nombre']),
            'arquitecturas'              => Arquitectura::where('activo', true)->get(['id', 'uuid', 'nombre', 'fabricante']),
            'chipsets'                   => Chipset::where('activo', true)->with('socket')->get(),
            'factores_forma'             => FactorForma::where('activo', true)->get(['id', 'uuid', 'nombre', 'ancho_mm', 'largo_mm']),
            'tipos_gabinete'             => TipoGabinete::where('activo', true)->get(['id', 'uuid', 'nombre']),
            'estructuras_gabinete'       => EstructuraGabinete::where('activo', true)->get(),
            'interfaces_almacenamiento'  => InterfazAlmacenamiento::where('activo', true)->get(['id', 'uuid', 'nombre']),
            'factores_forma_almacenamiento' => FactorFormaAlmacenamiento::where('activo', true)->get(['id', 'uuid', 'nombre']),
            'certificaciones_psu'        => CertificacionPSU::where('activo', true)->get(['id', 'uuid', 'nombre', 'eficiencia_minima']),
            'tipos_psu'                  => TipoPSU::where('activo', true)->get(['id', 'uuid', 'nombre', 'largo_max_mm']),
            'tipos_nand'                 => TipoNAND::where('activo', true)->get(['id', 'uuid', 'nombre']),
            'versiones_pcie'             => VersionPCIe::where('activo', true)->get(['id', 'uuid', 'nombre', 'ancho_banda_gbs']),
            'tipos_refrigeracion'        => TipoRefrigeracion::where('activo', true)->get(['id', 'uuid', 'nombre']),
            'tipos_ventilador'           => TipoVentilador::where('activo', true)->get(['id', 'uuid', 'nombre', 'grosor_mm']),
        ]);
    }
}
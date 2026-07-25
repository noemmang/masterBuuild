<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Componentes\Componente;
use App\Models\Negocio\UrlProductoTienda;
use App\Models\Negocio\Tienda;

/**
 * Vincula cada componente con la URL real de su ficha de producto en cada
 * tienda. Esta tabla es la "configuración" que usa scrape:precios para saber
 * qué página descargar; no confundir con entradas_precio, que es el
 * histórico de resultados de cada scrape.
 *
 * Este seeder debe ejecutarse DESPUÉS de ComponentesSeeder y del seeder
 * de Tienda, porque busca sus ids por nombre.
 *
 * ESTRUCTURA:
 * $productos = [
 *     'Nombre EXACTO del componente' => [
 *         'NombreTienda' => 'https://url-del-producto...',
 *         'OtraTienda'   => 'https://url-en-otra-tienda...',
 *     ],
 * ];
 *
 * Este fichero ya trae los ~300 componentes definidos en ComponentesSeeder,
 * cada uno con sus 3 tiendas (PCComponentes, Coolmod, Neobyte) y la url en
 * blanco (''). Solo tienes que rellenar las urls reales; las que dejes
 * vacías se guardan igualmente en la tabla pero conviene marcarlas luego
 * como 'activo' => false o completarlas antes de lanzar el scraper, porque
 * una url vacía hará fallar el scrape de ese componente/tienda.
 *
 * Si un componente/tienda no lo vas a usar, simplemente borra esa línea
 * (o el bloque completo del componente) para no dejar urls vacías sueltas.
 */
class UrlProductoTiendaSeeder extends Seeder
{
    public function run(): void
    {
        // Cacheamos las tiendas una sola vez, igual que hace ComponentesSeeder
        $tiendas = Tienda::pluck('id', 'nombre');

        $productos = [

            // ---------- CPU AMD ----------
            'AMD Ryzen 5 5600X' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-5-5600x-3-7ghz',
                'Coolmod'       => 'https://www.coolmod.com/amd-ryzen-5-5600x-46ghz-socket-am4-boxed-procesador',
                'Neobyte'       => 'https://www.neobyte.es/amd-ryzen-5-5600x-procesador-am4-7702.html',
            ],
            'AMD Ryzen 7 5800X' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-7-5800x-3-8ghz',
                'Coolmod'       => 'https://www.coolmod.com/amd-ryzen-7-5800x-47ghz-socket-am4-boxed-procesador',
                'Neobyte'       => 'https://www.neobyte.es/amd-ryzen-7-5800x-procesador-am4-7699.html',
            ],
            'AMD Ryzen 9 5900X' => [
                'PCComponentes' => 'https://www.pccomponentes.com/amd-ryzen-9-5900x-37-ghz',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'AMD Ryzen 9 5950X' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-9-5950x-3-4-ghz',
                'Coolmod'       => 'https://www.coolmod.com/amd-ryzen-9-5950x-49ghz-socket-am4-boxed-procesador',
                'Neobyte'       => 'https://www.neobyte.es/procesador-amd-ryzen-9-5950x-socket-am4-7700.html',
            ],
            'AMD Ryzen 5 5600G' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-5-5600g-4-40ghz',
                'Coolmod'       => 'https://www.coolmod.com/amd-ryzen-7-5600g-4-40ghz-socket-am4-boxed-6-core-sktchi',
                'Neobyte'       => 'https://www.neobyte.es/procesador-amd-ryzen-5-5600g-socket-am4-9942.html',
            ],
            'AMD Ryzen 7 5800X3D' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-7-5800x3d-8-nucleos-3-4-ghz-base-4-5-ghz-turbo-96-mb-cache-l3',
                'Coolmod'       => 'https://www.coolmod.com/amd-ryzen-7-5800x3d-4-5ghz-socket-am4-boxed-10th-aniversary',
                'Neobyte'       => 'https://www.neobyte.es/amd-ryzen-7-5800x3d-edicion-10-aniversario-procesador-am4-38263.html',
            ],
            'AMD Ryzen 5 7600X' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-5-7600x-4-7-ghz-box-sin-ventilador',
                'Coolmod'       => 'https://www.coolmod.com/amd-ryzen-5-7600x-5-3ghz-socket-am5-boxed',
                'Neobyte'       => 'https://www.neobyte.es/amd-ryzen-5-7600x-procesador-am5-15297.html',
            ],
            'AMD Ryzen 5 7600' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-5-7600-3-8-5-1-ghz-box',
                'Coolmod'       => 'https://www.coolmod.com/amd-ryzen-5-7600-5-1ghz-socket-am5-boxed',
                'Neobyte'       => 'https://www.neobyte.es/amd-ryzen-5-7600x-procesador-am5-15297.html',
            ],
            'AMD Ryzen 7 7700X' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-7-7700x-4-5-ghz-box-sin-ventilador',
                'Coolmod'       => 'https://www.coolmod.com/amd-ryzen-7-7700x-5-4ghz-socket-am5-boxed',
                'Neobyte'       => 'https://www.neobyte.es/amd-ryzen-7-7700x-procesador-am5-15298.html',
            ],
            'AMD Ryzen 7 7700' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-7-7700-3-8-5-3-ghz-box',
                'Coolmod'       => 'https://www.coolmod.com/amd-ryzen-7-7700-5-3ghz-socket-am5-boxed',
                'Neobyte'       => 'https://www.neobyte.es/amd-ryzen-7-7700-procesador-am5-16670.html',
            ],
            'AMD Ryzen 9 7900X' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-9-7900x-4-7-ghz-box-sin-ventilador',
                'Coolmod'       => 'https://www.coolmod.com/amd-ryzen-9-7900x-5-6ghz-socket-am5-boxed',
                'Neobyte'       => 'https://www.neobyte.es/amd-ryzen-9-7900x-procesador-am5-15299.html',
            ],
            'AMD Ryzen 7 7800X3D' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-7-7800x3d-4-2-ghz-5-ghz',
                'Coolmod'       => 'https://www.coolmod.com/amd-ryzen-7-7800x-3d-5-0ghz-socket-am5-boxed',
                'Neobyte'       => 'https://www.neobyte.es/amd-ryzen-7-7800x3d-procesador-am5-17301.html',
            ],
            'AMD Ryzen 9 7900X3D' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-9-7900x3d-4-4ghz-5-6ghz',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'AMD Ryzen 5 8600G' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-5-8600g-ia-integrada-4-3-5ghz-box',
                'Coolmod'       => 'https://www.coolmod.com/amd-ryzen-5-8600g-5-0ghz-socket-am5-boxed',
                'Neobyte'       => 'https://www.neobyte.es/amd-ryzen-5-8600g-procesador-am5-20605.html',
            ],
            'AMD Ryzen 5 9600X' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-5-9600x-3-9-5-4ghz',
                'Coolmod'       => 'https://www.coolmod.com/amd-ryzen-5-9600x-5-4-ghz-socket-am5',
                'Neobyte'       => 'https://www.neobyte.es/amd-ryzen-5-9600x-procesador-am5-22173.html',
            ],
            'AMD Ryzen 7 9700X' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-7-9700x-3-8-5-5ghz',
                'Coolmod'       => 'https://www.coolmod.com/amd-ryzen-7-9700x-5-5-ghz-socket-am5',
                'Neobyte'       => 'https://www.neobyte.es/amd-ryzen-7-9700x-procesador-am5-22171.html',
            ],
            'AMD Ryzen 9 9900X' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-9-9900x-4-4-5-6ghz',
                'Coolmod'       => 'https://www.coolmod.com/amd-ryzen-9-9900x-5-6-ghz-socket-am5',
                'Neobyte'       => 'https://www.neobyte.es/amd-ryzen-9-9900x-procesador-am5-22167.html',
            ],
            'AMD Ryzen 9 9950X' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-9-9950x-4-3-5-7ghz',
                'Coolmod'       => 'https://www.coolmod.com/amd-ryzen-9-9950x-5-7-ghz-socket-am5',
                'Neobyte'       => 'https://www.neobyte.es/amd-ryzen-9-9950x-procesador-am5-22166.html',
            ],
            'AMD Ryzen 7 9800X3D' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-7-9800x3d-4-7-5-2ghz',
                'Coolmod'       => 'https://www.coolmod.com/amd-ryzen-7-9800x3d-5-2ghz-socket-am5-boxed',
                'Neobyte'       => 'https://www.neobyte.es/amd-ryzen-7-9800x3d-procesador-am5-25474.html',
            ],
            'AMD Ryzen 9 9900X3D' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-9-9900x3d-4-4-5-5ghz-box',
                'Coolmod'       => 'https://www.coolmod.com/amd-ryzen-9-9900x3d-5-5ghz-socket-am5-boxed',
                'Neobyte'       => 'https://www.neobyte.es/amd-ryzen-9-9900x3d-procesador-am5-27719.html',
            ],
            'AMD Ryzen 9 9950X3D' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-9-9950x3d-4-3-5-7ghz-box',
                'Coolmod'       => 'https://www.coolmod.com/amd-ryzen-9-9950x3d-5-7ghz-socket-am5-boxed',
                'Neobyte'       => 'https://www.neobyte.es/amd-ryzen-9-9950x3d-procesador-am5-27720.html',
            ],
            'AMD Ryzen 5 5500' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-amd-ryzen-5-5500-3-6ghz-box',
                'Coolmod'       => 'https://www.coolmod.com/amd-ryzen-5-5500-4-2ghz-socket-am4-boxed',
                'Neobyte'       => 'https://www.neobyte.es/amd-ryzen-5-5500-procesador-am4-13306.html',
            ],

            // ---------- CPU Intel ----------
            'Intel Core i5-12600K' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-intel-core-i5-12600k-3-7-ghz',
                'Coolmod'       => 'https://www.coolmod.com/intel-core-i5-12600k-4-90ghz-socket-1700-boxed-procesador',
                'Neobyte'       => 'https://www.neobyte.es/intel-core-i5-12600k-procesador-1700-11137.html',
            ],
            'Intel Core i7-12700K' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-intel-core-i7-12700k-3-6-ghz',
                'Coolmod'       => 'https://www.coolmod.com/intel-core-i7-12700k-5-00ghz-socket-1700-boxed-procesador',
                'Neobyte'       => 'https://www.neobyte.es/intel-core-i7-12700k-procesador-1700-11135.html',
            ],
            'Intel Core i9-12900K' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-intel-core-i9-12900k-3-2-ghz',
                'Coolmod'       => 'https://www.coolmod.com/intel-core-i9-12900k-5-10ghz-socket-1700-boxed-procesador',
                'Neobyte'       => 'https://www.neobyte.es/intel-core-i9-12900k-procesador-1700-11132.html',
            ],
            'Intel Core i5-13400F' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-intel-core-i5-13400f-2-5-ghz-4-6-ghz',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/intel-core-i5-13400f-procesador-1700-16547.html',
            ],
            'Intel Core i9-13900KS' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-intel-core-i9-13900ks-2-40-ghz-6-00-ghz-box',
                'Coolmod'       => 'https://www.coolmod.com/intel-core-i9-13900ks-6-0ghz-socket-1700-boxed',
                'Neobyte'       => 'https://www.neobyte.es/intel-core-i9-13900ks-procesador-1700-17488.html',
            ],
            'Intel Core i5-14600K' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-intel-core-i5-14600k-3-5-5-4ghz-box',
                'Coolmod'       => 'https://www.coolmod.com/intel-core-i5-14600k-5-3ghz-socket-1700-boxed',
                'Neobyte'       => 'https://www.neobyte.es/intel-core-i5-14600k-procesador-1700-19505.html',
            ],
            'Intel Core i7-14700K' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-intel-core-i7-14700k-3-4-5-6ghz-box',
                'Coolmod'       => 'https://www.coolmod.com/intel-core-i7-14700k-5-6ghz-socket-1700-boxed/',
                'Neobyte'       => 'https://www.neobyte.es/intel-core-i7-14700k-procesador-1700-19503.html',
            ],
            'Intel Core i9-14900K' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-intel-core-i9-14900k-3-2-6ghz-box',
                'Coolmod'       => 'https://www.coolmod.com/intel-core-i9-14900k-6-0ghz-socket-1700-boxed',
                'Neobyte'       => 'https://www.neobyte.es/intel-core-i9-14900k-procesador-1700-19501.html',
            ],
            'Intel Core i5-14400F' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-intel-core-i5-14400f-2-5-4-7ghz-box',
                'Coolmod'       => 'https://www.coolmod.com/intel-core-i5-14400f-4-7ghz-socket-1700-boxed',
                'Neobyte'       => 'https://www.neobyte.es/intel-core-i5-14400f-procesador-1700-20220.html',
            ],
            'Intel Core i9-14900KS' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-intel-core-i9-14900ks-3-2-6-2ghz-box',
                'Coolmod'       => 'https://www.coolmod.com/intel-core-i9-14900ks-6-2ghz-socket-1700-boxed',
                'Neobyte'       => '',
            ],
            'Intel Core Ultra 5 245K' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-intel-core-ultra-5-245k-ia-integrada-4-2-5-2ghz-box',
                'Coolmod'       => 'https://www.coolmod.com/intel-core-ultra-5-245k-5-2ghz-socket-1851-boxed',
                'Neobyte'       => 'https://www.neobyte.es/intel-core-ultra-5-245k-procesador-1851-24400.html',
            ],
            'Intel Core Ultra 7 265K' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-intel-core-ultra-7-265k-ia-integrada-3-3-5-5ghz-box',
                'Coolmod'       => 'https://www.coolmod.com/intel-core-ultra-7-265k-5-5ghz-socket-1851-boxed',
                'Neobyte'       => 'https://www.neobyte.es/intel-core-ultra-7-265kf-procesador-1851-24399.html',
            ],
            'Intel Core Ultra 9 285K' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-intel-core-ultra-9-285k-ia-integrada-3-2-5-7ghz-box',
                'Coolmod'       => 'https://www.coolmod.com/intel-core-ultra-9-285k-5-7ghz-socket-1851-boxed',
                'Neobyte'       => 'https://www.neobyte.es/intel-core-ultra-9-285k-procesador-1851-24397.html',
            ],
            'Intel Core i5-12400F' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-intel-core-i5-12400f-2-5-ghz',
                'Coolmod'       => 'https://www.coolmod.com/intel-core-i5-12400f-4-4ghz-socket-1700-boxed-procesador',
                'Neobyte'       => 'https://www.neobyte.es/intel-core-i5-12400f-procesador-1700-11980.html',
            ],
            'Intel Core i7-12700' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-intel-core-i7-12700-2-1-ghz',
                'Coolmod'       => 'https://www.coolmod.com/intel-core-i7-12700-4-9ghz-socket-1700-boxed-procesador',
                'Neobyte'       => 'https://www.neobyte.es/intel-core-i7-12700-procesador-1700-11892.html',
            ],
            'Intel Core i7-13700F' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-intel-core-i7-13700f-2-1-ghz-5-2-ghz',
                'Coolmod'       => 'https://www.coolmod.com/intel-core-i7-13700f-5-2ghz-socket-1700-boxed',
                'Neobyte'       => '',
            ],
            'Intel Core i5-14600KF' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-intel-core-i5-14600kf-3-5-5-4ghz-box',
                'Coolmod'       => 'https://www.coolmod.com/intel-core-i5-14600kf-5-3ghz-socket-1700-boxed',
                'Neobyte'       => 'https://www.neobyte.es/intel-core-i5-14600kf-procesador-1700-19506.html',
            ],
            'Intel Core i7-14700F' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-intel-core-i7-14700f-2-1-5-4ghz-box',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/intel-core-i7-14700f-procesador-1700-20223.html',
            ],
            'Intel Core Ultra 5 245KF' => [
                'PCComponentes' => 'https://www.pccomponentes.com/procesador-intel-core-ultra-5-245kf-ia-integrada-4-2-5-2ghz-box',
                'Coolmod'       => 'https://www.coolmod.com/intel-core-ultra-5-245kf-5-2ghz-socket-1851-boxed',
                'Neobyte'       => 'https://www.neobyte.es/intel-core-ultra-5-245kf-procesador-1851-24401.html',
            ],

            // ---------- Placas base AM4 ----------
            'ASUS ROG Strix B550-F Gaming' => [
                'PCComponentes' => 'https://www.pccomponentes.com/asus-rog-strix-b550-f-gaming',
                'Coolmod'       => 'https://www.coolmod.com/asus-rog-strix-b550-f-gaming-socket-am4-placa-base',
                'Neobyte'       => 'https://www.neobyte.es/placa-base-asus-rog-strix-b550-f-gaming-6609.html',
            ],
            'MSI MAG B550 Tomahawk' => [
                'PCComponentes' => 'https://www.pccomponentes.com/msi-mag-b550-tomahawk',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte B550 Aorus Pro AX' => [
                'PCComponentes' => 'https://www.pccomponentes.com/gigabyte-b550i-aorus-pro-ax',
                'Coolmod'       => 'https://www.coolmod.com/gigabyte-b550i-aorus-pro-ax-socket-am4-placa-base',
                'Neobyte'       => '',
            ],
            'ASUS ROG Crosshair VIII Hero' => [
                'PCComponentes' => 'https://www.pccomponentes.com/asus-rog-crosshair-viii-hero',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte X570 Aorus Master' => [
                'PCComponentes' => 'https://www.pccomponentes.com/gigabyte-x570-aorus-master',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/placa-base-gigabyte-x570-aorus-master-am4-4349.html',
            ],
            'MSI B550M Pro-VDH WiFi' => [
                'PCComponentes' => 'https://www.pccomponentes.com/msi-b550m-pro-vdh-wifi',
                'Coolmod'       => 'https://www.coolmod.com/msi-b550m-pro-vdh-wifi-socket-am4-placa-base',
                'Neobyte'       => 'https://www.neobyte.es/placa-base-msi-b550m-pro-vdh-wifi-7001.html',
            ],
            'ASRock B550M Steel Legend' => [
                'PCComponentes' => 'https://www.pccomponentes.com/asrock-b550m-steel-legend',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte B550M Aorus Pro' => [
                'PCComponentes' => 'https://www.pccomponentes.com/gigabyte-b550m-aorus-pro',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS ROG Strix B550-I Gaming' => [
                'PCComponentes' => 'https://www.pccomponentes.com/asus-rog-strix-b550-i-gaming',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI MPG B550I Gaming Edge WiFi' => [
                'PCComponentes' => 'https://www.pccomponentes.com/msi-mpg-b550i-gaming-edge-wifi',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/placa-base-msi-am4-b550i-gaming-edge-wifi-8444.html',
            ],
            'ASRock B550 Phantom Gaming 4' => [
                'PCComponentes' => 'https://www.pccomponentes.com/asrock-b550-phantom-gaming-4',
                'Coolmod'       => 'https://www.coolmod.com/asrock-b550-phantom-gaming-4-socket-am4-placa-base',
                'Neobyte'       => 'https://www.neobyte.es/asrock-b550-phantom-gaming-4-placa-base-am4-atx-25939.html',
            ],
            'Biostar B550MH 3.0' => [
                'PCComponentes' => 'https://www.pccomponentes.com/biostar-b550mh',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS TUF Gaming B550M-Plus WiFi II' => [
                'PCComponentes' => 'https://www.pccomponentes.com/asus-tuf-gaming-b550m-plus-wifi-ii',
                'Coolmod'       => 'https://www.coolmod.com/asus-tuf-gaming-b550m-plus-wifi-ii-socket-am4',
                'Neobyte'       => 'https://www.neobyte.es/asus-tuf-gaming-b550m-plus-wifi-ii-amd-placa-base-am4-micro-atx-11617.html',
            ],

            // ---------- Placas base AM5 ----------
            'ASUS TUF Gaming B650-Plus WiFi' => [
                'PCComponentes' => 'https://www.pccomponentes.com/placa-base-asus-tuf-gaming-b650-plus-wifi-b650-am5-ddr5-atx-wifi-6-2-5gbe-m-2-rgb',
                'Coolmod'       => 'https://www.coolmod.com/asus-tuf-gaming-b650-plus-wifi-socket-am5',
                'Neobyte'       => '',
            ],
            'MSI MAG B650 Tomahawk WiFi' => [
                'PCComponentes' => 'https://www.pccomponentes.com/msi-mag-b650-tomahawk-wifi',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte B650 Aorus Elite AX' => [
                'PCComponentes' => 'https://www.pccomponentes.com/placa-base-gigabyte-b650-aorus-elite-ax-ice-b650-zocalo-am5-ddr5-atx-wifi-6e-2-5gbe-m-2-rgb',
                'Coolmod'       => 'https://www.coolmod.com/gigabyte-b650-aorus-elite-ax-ice-socket-am5',
                'Neobyte'       => '',
            ],
            'ASUS ROG Crosshair X670E Hero' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/asus-rog-crosshair-x670e-hero-placa-base-amd-am5-15303.html',
            ],
            'MSI MEG X670E Ace' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/msi-meg-x670e-ace-placa-base-am5-e-atx-18693.html',
            ],
            'Gigabyte X670E Aorus Master' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/gigabyte-x670e-aorus-master-placa-base-am5-e-atx-15340.html',
            ],
            'MSI PRO B650M-A WiFi' => [
                'PCComponentes' => 'https://www.pccomponentes.com/msi-pro-b650m-a-wifi',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/msi-pro-b650m-a-wifi-placa-base-am5-micro-atx-15379.html',
            ],
            'Gigabyte B650M Aorus Elite AX' => [
                'PCComponentes' => 'https://www.pccomponentes.com/placa-base-gigabyte-b650-aorus-elite-ax-ice-b650-zocalo-am5-ddr5-atx-wifi-6e-2-5gbe-m-2-rgb',
                'Coolmod'       => 'https://www.coolmod.com/gigabyte-b650m-aorus-elite-ax-ice-socket-am5',
                'Neobyte'       => '',
            ],
            'ASUS ROG Strix B650E-I Gaming WiFi' => [
                'PCComponentes' => 'https://www.pccomponentes.com/asus-rog-strix-b650e-i-gaming-wifi',
                'Coolmod'       => 'https://www.coolmod.com/asus-rog-strix-b650e-i-gaming-wifi-socket-am5',
                'Neobyte'       => 'https://www.neobyte.es/asus-rog-strix-b650e-i-gaming-wifi-placa-base-am5-mini-itx-15607.html',
            ],
            'MSI MPG B650I Edge WiFi' => [
                'PCComponentes' => 'https://www.pccomponentes.com/msi-mpg-b650i-edge-wifi',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS ProArt X670E-Creator WiFi' => [
                'PCComponentes' => 'https://www.pccomponentes.com/asus-proart-x670e-creator-wifi',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/asus-proart-x670e-creator-wi-fi-placa-base-amd-15313.html',
            ],
            'MSI MAG X870 Tomahawk WiFi' => [
                'PCComponentes' => 'https://www.pccomponentes.com/placa-base-msi-mag-x870-tomahawk-wifi',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/msi-mag-x870-tomahawk-wifi-placa-base-atx-am5-23975.html',
            ],
            'Gigabyte X870E Aorus Master' => [
                'PCComponentes' => 'https://www.pccomponentes.com/placa-base-gigabyte-x870e-aorus-master-x3d-ice-x870e-socket-am5-ddr5-atx-wifi-7-10gbe-pcie-5-0-raid-rgb',
                'Coolmod'       => 'https://www.coolmod.com/gigabyte-x870e-aorus-master-x3d-ice-socket-am5',
                'Neobyte'       => '',
            ],

            // ---------- Placas base LGA1700 ----------
            'ASUS ROG Maximus Z690 Hero' => [
                'PCComponentes' => 'https://www.pccomponentes.com/asus-rog-maximus-z690-hero',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/asus-rog-maximus-z690-hero-placa-base-1700-atx-11085.html',
            ],
            'Gigabyte Z690 Aorus Pro DDR4' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/gigabyte-z690-aorus-pro-placa-base-1700-atx-wifi-11105.html',
            ],
            'ASUS ROG Strix Z790-E Gaming WiFi II' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/asus-rog-strix-z790e-gaming-wifi-ii-placa-base-atx-19541.html',
            ],
            'MSI MAG Z790 Tomahawk WiFi DDR4' => [
                'PCComponentes' => 'https://www.pccomponentes.com/placa-base-msi-mag-z790-tomahawk-wifi-intel-z790-lga1700-ddr5-atx-wifi-6-2-5gbe-pcie-5-0-rgb',
                'Coolmod'       => 'https://www.coolmod.com/msi-mag-z790-tomahawk-wifi-socket-1700-1667899500',
                'Neobyte'       => 'https://www.neobyte.es/msi-mag-z790-tomahawk-wifi-placa-base-1700-atx-16654.html',
            ],
            'Gigabyte Z790 Aorus Elite AX' => [
                'PCComponentes' => 'https://www.pccomponentes.com/gigabyte-z790-aorus-elite-ax',
                'Coolmod'       => 'https://www.coolmod.com/gigabyte-z790-aorus-elite-ax-socket-1700',
                'Neobyte'       => '',
            ],
            'MSI PRO B660M-A DDR4' => [
                'PCComponentes' => 'https://www.pccomponentes.com/msi-pro-b660m-a-wifi-ddr4',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASRock B760M Pro RS' => [
                'PCComponentes' => 'https://www.pccomponentes.com/asrock-b760m-pro-rs',
                'Coolmod'       => 'https://www.coolmod.com/asrock-b760m-pro-rs-socket-1700',
                'Neobyte'       => 'https://www.neobyte.es/asrock-b760m-pro-rs-placa-base-1700-microatx-27209.html',
            ],
            'Gigabyte B760M Aorus Elite AX DDR4' => [
                'PCComponentes' => 'https://www.pccomponentes.com/gigabyte-b760m-aorus-elite-ax',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS ROG Strix Z690-I Gaming WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/asus-rog-strix-z690-i-gaming-wifi-placa-base-1700-mini-itx-11140.html',
            ],
            'MSI MPG Z790I Edge WiFi' => [
                'PCComponentes' => 'https://www.pccomponentes.com/placa-base-msi-z790-lga1700-mini-itx-mpg-z790i-edge-wifi-ddr5-pcie-5-0-wi-fi-6e-2-5gbe',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/msi-z790i-edge-wifi-placa-base-1700-mini-itx-23628.html',
            ],
            'ASUS TUF Gaming Z790-Plus WiFi' => [
                'PCComponentes' => 'https://www.pccomponentes.com/asus-tuf-gaming-z790-plus-wifi',
                'Coolmod'       => 'https://www.coolmod.com/asus-tuf-gaming-z790-plus-wifi-socket-1700',
                'Neobyte'       => '',
            ],

            // ---------- Placas base LGA1851 ----------
            'ASUS ROG Maximus Z890 Apex' => [
                'PCComponentes' => 'https://www.pccomponentes.com/placa-base-asus-rog-maximus-z890-apex',
                'Coolmod'       => 'https://www.coolmod.com/asus-rog-maximus-z890-apex-wifi-socket-1851',
                'Neobyte'       => 'https://www.neobyte.es/asus-rog-maximus-z890-apex-placa-base-1851-atx-24342.html',
            ],
            'MSI MEG Z890 Ace' => [
                'PCComponentes' => 'https://www.pccomponentes.com/placa-base-msi-meg-z890-ace',
                'Coolmod'       => 'https://www.coolmod.com/msi-meg-z890-ace-wifi-socket-1851',
                'Neobyte'       => '',
            ],
            'Gigabyte Z890 Aorus Master' => [
                'PCComponentes' => 'https://www.pccomponentes.com/placa-base-gigabyte-z890-aorus-master',
                'Coolmod'       => 'https://www.coolmod.com/gigabyte-z890-aorus-master-wifi7-socket-1851',
                'Neobyte'       => 'https://www.neobyte.es/gigabyte-z890-aorus-master-placa-base-1851-atx-24416.html',
            ],
            'ASUS TUF Gaming Z890-Plus WiFi' => [
                'PCComponentes' => 'https://www.pccomponentes.com/placa-base-asus-tuf-gaming-z890-plus-wifi',
                'Coolmod'       => 'https://www.coolmod.com/asus-tuf-gaming-z890-plus-wifi-socket-1851',
                'Neobyte'       => 'https://www.neobyte.es/asus-tuf-gaming-z890-plus-wifi-placa-base-1851-atx-24349.html',
            ],
            'MSI MAG Z890 Tomahawk WiFi' => [
                'PCComponentes' => 'https://www.pccomponentes.com/placa-base-msi-mag-z890-tomahawk-wifi',
                'Coolmod'       => 'https://www.coolmod.com/msi-mag-z890-tomahawk-wifi-socket-1851',
                'Neobyte'       => 'https://www.neobyte.es/msi-mag-z890-tomahawk-wifi-placa-base-1851-atx-24663.html',
            ],
            'ASRock Z890 Taichi' => [
                'PCComponentes' => 'https://www.pccomponentes.com/placa-base-asrock-z890-lga1851-atx-taichi-ddr5-pcie-5-0-multi-gpu-audio-premium',
                'Coolmod'       => 'https://www.coolmod.com/asrock-z890-taichi-socket-1851',
                'Neobyte'       => '',
            ],
            'MSI PRO B860M-A WiFi' => [
                'PCComponentes' => 'https://www.pccomponentes.com/placa-base-msi-pro-b860m-a-wifi',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/msi-pro-b860m-a-wifi-placa-base-1851-micro-atx-26643.html',
            ],
            'Gigabyte B860M Aorus Elite WiFi' => [
                'PCComponentes' => 'https://www.pccomponentes.com/placa-base-gigabyte-b860m-aorus-elite-wifi6e',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/gigabyte-b860m-aorus-elite-wifi6e-placa-base-1851-micro-atx-26487.html',
            ],
            'ASRock B860M Pro RS WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => 'https://www.coolmod.com/asrock-b860m-pro-rs-wifi-socket-1851',
                'Neobyte'       => '',
            ],
            'ASUS ROG Strix Z890-I Gaming WiFi' => [
                'PCComponentes' => 'https://www.pccomponentes.com/placa-base-asus-rog-strix-z890-i-gaming-wifi',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI MPG Z890I Edge WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => 'https://www.coolmod.com/msi-mpg-z890i-edge-ti-wifi-socket-1851',
                'Neobyte'       => '',
            ],
            'Gigabyte Z890I Aorus Ultra WiFi7' => [
                'PCComponentes' => 'https://www.pccomponentes.com/placa-base-gigabyte-z890i-aorus-ultra-lga-1851-mini-itx-thunderbolt-4-wi-fi-7-2-5gbe-ddr5',
                'Coolmod'       => 'https://www.coolmod.com/gigabyte-z890i-aorus-ultra-socket-1851',
                'Neobyte'       => '',
            ],

            // ---------- RAM DDR4 ----------
            'Corsair Vengeance LPX 16GB DDR4-3200 CL16' => [
                'PCComponentes' => 'https://www.pccomponentes.com/corsair-vengeance-lpx-ddr4-3200-pc4-25600-16gb-2x8gb-cl16-negro',
                'Coolmod'       => 'https://www.coolmod.com/corsair-vengance-lpx-16gb-2x8gb-3200mhz-pc4-25600-cl16-memoria-ddr4',
                'Neobyte'       => 'https://www.neobyte.es/memoria-corsair-16gb-ddr4-3200-vengeance-lpx-4647.html',
            ],
            'Corsair Vengeance LPX 128GB DDR4-3200 CL16' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/corsair-vengeance-lpx-128gb-4x32gb-ddr4-3200mhz-cl16-memoria-ram-28809.html',
            ],
            'G.Skill Trident Z RGB 16GB DDR4-3600 CL16' => [
                'PCComponentes' => 'https://www.pccomponentes.com/memoria-ram-g-skill-trident-z-neo-ddr4-3600-pc4-28800-16gb-2x8-gb-cl16',
                'Coolmod'       => 'https://www.coolmod.com/gskill-trident-z-neo-16gb-2x8gb-3600mhz-pc4-28800-cl16-memoria-ddr4',
                'Neobyte'       => '',
            ],
            'G.Skill Trident Z RGB 32GB DDR4-3600 CL16' => [
                'PCComponentes' => 'https://www.pccomponentes.com/memoria-ram-g-skill-trident-z-32gtzrc-rgb-ddr4-3600mhz-32gb-2x16gb-cl16',
                'Coolmod'       => 'https://www.coolmod.com/gskill-trident-z-rgb-32gb-2x16gb-3200-mhz-pc4-25600-cl16-led-rgb-memoria-ddr4',
                'Neobyte'       => 'https://www.neobyte.es/gskill-trident-z-rgb-32gb-2x16gb-ddr4-3200mhz-cl16-memoria-ram-34939.html',
            ],
            'Kingston Fury Beast 16GB DDR4-3200 CL16' => [
                'PCComponentes' => 'https://www.pccomponentes.com/kingston-fury-beast-rgb-ddr4-3200mhz-16gb-2x8gb-cl16',
                'Coolmod'       => 'https://www.coolmod.com/kingston-fury-beast-rgb-16gb-2x8gb-3200mhz-cl16-xmp',
                'Neobyte'       => 'https://www.neobyte.es/kingston-fury-beast-rgb-16gb-2x8gb-ddr4-3200mhz-cl16-memoria-ram-20403.html',
            ],
            'Kingston Fury Beast RGB 32GB DDR4-3600 CL18' => [
                'PCComponentes' => 'https://www.pccomponentes.com/memoria-ram-kingston-fury-beast-rgb-32gb-2x16gb-ddr4-3600mhz-cl18-intel-xmp-rgb-negro',
                'Coolmod'       => 'https://www.coolmod.com/kingston-fury-beast-rgb-32gb-2x16gb-3600mhz-cl18-xmp',
                'Neobyte'       => 'https://www.neobyte.es/kingston-fury-beast-rgb-32gb-2x16gb-ddr4-3600mhz-cl18-memoria-ram-20016.html',
            ],
            'TeamGroup T-Force Vulcan Z 16GB DDR4-3200 CL16' => [
                'PCComponentes' => 'https://www.pccomponentes.com/team-group-t-force-vulcan-z-ddr4-3200mhz-pc4-25600-16gb-2x8gb-cl16-gris',
                'Coolmod'       => 'https://www.coolmod.com/team-group-vulcan-z-16gb-1x16gb-3200mhz-cl16-gris/',
                'Neobyte'       => '',
            ],
            'G.Skill Ripjaws V 64GB DDR4-3600 CL18' => [
                'PCComponentes' => 'https://www.pccomponentes.com/memoria-ram-g-skill-ripjaws-v-64-gb-2x32-gb-ddr4-3600-mhz-cl18-intel-xmp-negro',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Silicon Power XPOWER Turbine 16GB DDR4-3200 CL16' => [
                'PCComponentes' => 'https://www.pccomponentes.com/memoria-ram-silicon-power-xpower-pulse-16gb-2x8gb-ddr4-3200mhz-cl16-intel-xmp-amd-expo-negro',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],

            // ---------- RAM DDR5 ----------
            'Corsair Vengeance DDR5 32GB 5600 CL36' => [
                'PCComponentes' => 'https://www.pccomponentes.com/corsair-vengeance-ddr5-5600mhz-32gb-2x16gb-cl36',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Corsair Vengeance DDR5 64GB 5600 CL36' => [
                'PCComponentes' => 'https://www.pccomponentes.com/memoria-ram-corsair-vengeance-cmk64gx5m4b5600z36-64gb-4x16gb-ddr5-5600mhz-cl36-amd-expo-negro',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'G.Skill Trident Z5 RGB 32GB DDR5-6000 CL30' => [
                'PCComponentes' => 'https://www.pccomponentes.com/memoria-ram-g-skill-trident-z5-neo-rgb-32gb-2x16gb-ddr5-6000mhz-cl30-amd-expo-negro',
                'Coolmod'       => 'https://www.coolmod.com/g-skill-trident-z5-rgb-32gb-2x16gb-6000mhz-cl30',
                'Neobyte'       => '',
            ],
            'G.Skill Trident Z5 RGB 64GB DDR5-6000 CL30' => [
                'PCComponentes' => '',
                'Coolmod'       => 'https://www.coolmod.com/g-skill-trident-z5-neo-rgb-64gb-2x32gb-6000mhz-cl30-expo',
                'Neobyte'       => '',
            ],
            'Kingston Fury Beast DDR5 32GB 5200 CL40' => [
                'PCComponentes' => 'https://www.pccomponentes.com/kingston-fury-beast-ddr5-5200mhz-32gb-2x16gb-cl40',
                'Coolmod'       => 'https://www.coolmod.com/kingston-fury-2x16gb-5200-mhz-pc5-41600-cl40-memoria-ddr5',
                'Neobyte'       => 'https://www.neobyte.es/kingston-fury-beast-32gb-2x16gb-ddr5-5200mhz-cl40-memoria-ram-11531.html',
            ],
            'Kingston Fury Renegade RGB DDR5 64GB 6400 CL32' => [
                'PCComponentes' => 'https://www.pccomponentes.com/kingston-fury-renegade-rgb-ddr5-6400mhz-32gb-2x16gb-cl32',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Crucial Pro DDR5 128GB 5600 CL46' => [
                'PCComponentes' => 'https://www.pccomponentes.com/memoria-ram-crucial-pro-cp2k64g56c46u5-128gb-2x64gb-ddr5-5600mhz-cl46-intel-xmp-amd-expo',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Crucial Pro DDR5 64GB 5600 CL46' => [
                'PCComponentes' => 'https://www.pccomponentes.com/crucial-pro-ddr5-5600mhz-64gb-2x32gb-cl46',
                'Coolmod'       => 'https://www.coolmod.com/crucial-pro-64gb-2x32gb-5600mhz-cl46-expo-xmp-negro',
                'Neobyte'       => '',
            ],
            'TeamGroup T-Force Delta RGB DDR5 32GB 6000 CL30' => [
                'PCComponentes' => 'https://www.pccomponentes.com/memoria-ram-team-group-t-force-delta-rgb-ddr5-6000mhz-32gb-2x16gb-cl30-dual-amd-expo-e-intel-xmp-negro',
                'Coolmod'       => 'https://www.coolmod.com/team-group-delta-rgb-32gb-2x16gb-6000mhz-cl30-xmp',
                'Neobyte'       => 'https://www.neobyte.es/teamgroup-t-force-vulcan-32gb-2x16gb-ddr5-6000mhz-cl30-rgb-memoria-ram-39613.html',
            ],
            'XPG Lancer RGB DDR5 32GB 6000 CL30' => [
                'PCComponentes' => '',
                'Coolmod'       => 'https://www.coolmod.com/adata-xpg-lancer-blade-32gb-2x16gb-6000mhz-cl30-xmp-negro',
                'Neobyte'       => 'https://www.neobyte.es/xpg-lancer-32gb-2x16gb-ddr5-6000mhz-cl30-memoria-ram-24759.html',
            ],
            'G.Skill Trident Z5 Neo RGB 32GB DDR5-6000 CL30' => [
                'PCComponentes' => 'https://www.pccomponentes.com/memoria-ram-g-skill-trident-z5-neo-rgb-ddr5-6000mhz-32gb-2x16gb-cl30',
                'Coolmod'       => 'https://www.coolmod.com/g-skill-trident-z5-neo-rgb-1x32gb-6000mhz-cl30-expo',
                'Neobyte'       => '',
            ],
            'Corsair Dominator Titanium RGB 32GB DDR5-6200 CL32' => [
                'PCComponentes' => 'https://www.pccomponentes.com/corsair-dominator-titanium-ddr5-6400mhz-32gb-2x16gb-cl32-xmp-negro',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Kingston Fury Beast DDR5 64GB 5200 CL40' => [
                'PCComponentes' => 'https://www.pccomponentes.com/kingston-fury-beast-ddr5-5200mhz-64gb-2x32gb-cl40',
                'Coolmod'       => 'https://www.coolmod.com/kingston-fury-beast-64gb-2x32gb-5200mhz-cl40-xmp-3-0',
                'Neobyte'       => '',
            ],
            'G.Skill Trident Z5 Neo 64GB DDR5-6000 CL26' => [
                'PCComponentes' => 'https://www.pccomponentes.com/memoria-ram-g-skill-trident-z5-neo-rgb-f5-6000j2636h32gx2-tz5nr-64gb-2x32gb-ddr5-6000mhz-cl26-amd-expo-rgb-multicolor',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],

            // ---------- GPU ----------
            'MSI GeForce RTX 3060 VENTUS 2X 12GB OC' => [
                'PCComponentes' => 'https://www.pccomponentes.com/msi-geforce-rtx-3060-ventus-2x-12gb-gddr6',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/msi-rtx-3060-ventus-oc-12gb-lhr-tarjeta-grafica-13252.html',
            ],
            'ASUS Dual GeForce RTX 3060 Ti OC 8GB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/asus-dual-geforce-rtx-3060-ti-oc-edition-8gb-gddr6x',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/asus-dual-rtx-3060-ti-oc-edition-8gb-gddr6x-tarjeta-grafica-16514.html',
            ],
            'Gigabyte GeForce RTX 3070 EAGLE OC 8GB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/gigabyte-geforce-rtx-3070-eagle-oc-8gb-gddr6-rev-20',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/gigabyte-rtx-3070-eagle-oc-8gb-tarjeta-grafica-10348.html',
            ],
            'Zotac Gaming GeForce RTX 3060 Twin Edge OC (SFF)' => [
                'PCComponentes' => 'https://www.pccomponentes.com/zotac-gaming-geforce-rtx-3060-twin-edge-lhr-12gb-gddr6',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/zotac-gaming-geforce-rtx-3060-twin-edge-12gb-gddr6-tarjeta-grafica-38389.html',
            ],
            'MSI GeForce RTX 4060 VENTUS 2X BLACK OC 8GB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/msi-geforce-rtx-4060-ventus-2x-black-oc-8gb-gddr6-dlss3',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/msi-rtx-4060-ventus-2x-black-8gb-oc-dlss3-tarjeta-grafica-18601.html',
            ],
            'Zotac Gaming GeForce RTX 4060 Twin Edge OC 8GB (SFF)' => [
                'PCComponentes' => 'https://www.pccomponentes.com/zotac-gaming-geforce-rtx-4060-twin-edge-8gb-gddr6-dlss3',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/zotac-gaming-rtx-4060-twin-edge-8gb-gddr6-dlss3-tarjeta-grafica-20635.html',
            ],
            'ASUS Dual GeForce RTX 4060 Ti OC 8GB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/asus-dual-geforce-rtx-4060-oc-edition-8gb-gddr6-dlss3',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/asus-dual-rtx-4060-ti-oc-16gb-gddr6-tarjeta-grafica-18732.html',
            ],
            'Gigabyte GeForce RTX 4060 Ti GAMING OC 16GB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/gigabyte-geforce-rtx-4060-ti-gaming-oc-16gb-gddr6-dlss3',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/gigabyte-rtx-4060-ti-gaming-oc-16gb-gddr6-dlss3-tarjeta-grafica-18741.html',
            ],
            'ASUS ROG Strix GeForce RTX 4070 Super OC 12GB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/asus-rog-strix-geforce-rtx-4070-super-oc-edition-12gb-gddr6x-dlss3',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/asus-rog-strix-rtx-4070-super-12gb-gddr6x-dlss3-tarjeta-grafica-20182.html',
            ],
            'Gigabyte GeForce RTX 4070 Ti Super AORUS MASTER 16GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/gigabyte-aorus-rtx-4070-ti-super-master-16gb-gddr6x-dlss3-tarjeta-grafica-20240.html',
            ],
            'PNY GeForce RTX 4080 Super XLR8 Gaming VERTO EPIC-X RGB 16GB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/pny-geforce-rtx-4080-verto-triple-fan-16gb-gddr6x-dlss3',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI GeForce RTX 4090 SUPRIM LIQUID X 24GB' => [
                'PCComponentes' => 'https://www.neobyte.es/msi-rtx-4090-suprim-liquid-x-24gb-tarjeta-grafica-15595.html',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS Dual GeForce RTX 5060 Ti OC 16GB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/tarjeta-grafica-asus-dual-geforce-rtx-5060-ti-oc-edition-16gb-gddr7-reflex-2-rtx-ai-dlss4',
                'Coolmod'       => 'https://www.coolmod.com/asus-dual-geforce-rtx-5060-ti-oc-16gb-gddr7-dlss4',
                'Neobyte'       => 'https://www.neobyte.es/asus-dual-rtx-5060-ti-oc-8gb-gddr7-dlss4-tarjeta-grafica-28548.html',
            ],
            'MSI GeForce RTX 5070 GAMING TRIO OC 12GB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/tarjeta-grafica-msi-geforce-rtx-5070-gaming-trio-oc-12gb-gddr7-reflex-2-rtx-ai-dlss4',
                'Coolmod'       => 'https://www.coolmod.com/msi-geforce-rtx-5070-gaming-trio-oc-12gb-gddr7-dlss4',
                'Neobyte'       => 'https://www.neobyte.es/msi-geforce-rtx-5070-gaming-trio-oc-12gb-gddr7-dlss4-tarjeta-grafica-27798.html',
            ],
            'Gigabyte GeForce RTX 5070 Ti AORUS MASTER 16GB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/tarjeta-grafica-gigabyte-geforce-rtx-5070-ti-aorus-master-16gb-gddr7-reflex-2-rtx-ai-dlss4',
                'Coolmod'       => 'https://www.coolmod.com/gigabyte-aorus-geforce-rtx-5070-ti-master-16gb-gddr7',
                'Neobyte'       => 'https://www.neobyte.es/gigabyte-aorus-rtx-5070-ti-master-16gb-gddr7-dlss4-tarjeta-grafica-26521.html',
            ],
            'ASUS ROG Astral GeForce RTX 5080 OC 16GB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/tarjeta-grafica-asus-rog-astral-geforce-rtx-5080-oc-16gb-gddr7-reflex-2-rtx-ai-dlss4',
                'Coolmod'       => 'https://www.coolmod.com/asus-rog-astral-geforce-rtx-5080-oc-gaming-16gb-gddr7',
                'Neobyte'       => '',
            ],
            'MSI GeForce RTX 5090 SUPRIM LIQUID X 32GB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/tarjeta-grafica-msi-geforce-rtx-5090-suprim-liquid-32gb-gddr7-reflex-2-rtx-ai-dlss4',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'INNO3D GeForce RTX 5060 TWIN X2 OC 8GB (SFF)' => [
                'PCComponentes' => 'https://www.pccomponentes.com/tarjeta-grafica-tarjeta-grafica-inno3d-geforce-rtx-5060-twin-x2-oc-v2-8gb-gddr7-reflex-2-rtx-ai-dlss4',
                'Coolmod'       => 'https://www.coolmod.com/inno3d-geforce-rtx-5060-twin-x2-oc-v2-8gb-gddr7-dlss4',
                'Neobyte'       => 'https://www.neobyte.es/inno3d-geforce-rtx-5060-twin-x2-oc-8gb-gddr7-dlss4-tarjeta-grafica-37501.html',
            ],
            'Sapphire Pulse Radeon RX 6600 8GB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/sapphire-pulse-amd-radeon-rx-6600-8gb-gddr6',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'XFX Speedster MERC319 Radeon RX 6800 XT 16GB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/xfx-speedster-merc319-amd-radeon-rx-6800-xt-core-16gb-gddr6?refurbished',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Sapphire Pulse Radeon RX 7600 8GB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/sapphire-pulse-amd-radeon-rx-7600-gaming-8gb-gddr6',
                'Coolmod'       => 'https://www.coolmod.com/sapphire-pulse-radeon-rx-7600-gaming-oc-8gb-gddr6',
                'Neobyte'       => '',
            ],
            'ASRock Radeon RX 7700 XT Challenger 12GB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/tarjeta-grafica-asrock-radeon-rx-9070-xt-challenger-16gb-gddr6-pcie-5-0',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'PowerColor Red Devil Radeon RX 7800 XT 16GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/powercolor-radeon-rx-7800xt-red-devil-16gb-gddr6-tarjeta-grafica-19029.html',
            ],
            'Sapphire Pulse Radeon RX 9070 16GB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/tarjeta-grafica-sapphire-pulse-amd-radeon-rx-9070-16gb-gddr6-fsr-4',
                'Coolmod'       => 'https://www.coolmod.com/sapphire-pulse-amd-radeon-rx-9070-16gb-gddr6',
                'Neobyte'       => '',
            ],
            'PowerColor Red Devil Radeon RX 9070 XT 16GB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/tarjeta-grafica-powercolor-red-devil-amd-radeon-rx-9070-xt-oc-16gb-gddr6-fsr-4',
                'Coolmod'       => 'https://www.coolmod.com/powercolor-red-devil-amd-radeon-rx-9070-xt-oc-16gb-gddr6',
                'Neobyte'       => '',
            ],
            'XFX Speedster MERC 310 Radeon RX 9070 XT 16GB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/tarjeta-grafica-xfx-radeon-rx-9070-xt-16gb-gddr6-pci-express-5-0',
                'Coolmod'       => 'https://www.coolmod.com/xfx-quicksilver-amd-radeon-rx-9070-xt-gaming-16gb-gddr6/',
                'Neobyte'       => '',
            ],     

            // ---------- Almacenamiento ----------
            'Samsung 970 EVO Plus 1TB NVMe' => [
                'PCComponentes' => 'https://www.pccomponentes.com/disco-duro-samsung-970-evo-plus-1tb-ssd-nvme-m-2',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/samsung-970-evo-plus-1tb-ssd-m2-pcie-30-3942.html',
            ],
            'Samsung 980 Pro 1TB NVMe PCIe 4.0' => [
                'PCComponentes' => 'https://www.pccomponentes.com/disco-duro-samsung-980-pro-1tb-disco-ssd-7000mb-s-nvme-pcie-4-0-m-2-gen4',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Samsung 990 Pro 2TB NVMe PCIe 4.0' => [
                'PCComponentes' => 'https://www.pccomponentes.com/disco-duro-samsung-990-pro-2tb-disco-ssd-7450mb-s-nvme-pcie-4-0-m-2-gen4',
                'Coolmod'       => 'https://www.coolmod.com/samsung-990-pro-2tb-pcie-x4-nvme',
                'Neobyte'       => 'https://www.neobyte.es/samsung-990-pro-2tb-ssd-m2-pci-express-40-16306.html',
            ],
            'Western Digital Black SN850X 1TB NVMe PCIe 4.0' => [
                'PCComponentes' => '',
                'Coolmod'       => 'https://www.coolmod.com/western-digital-black-sn850x-1tb-nvme-pcie-gen4',
                'Neobyte'       => 'https://www.neobyte.es/wd-black-sn850x-1tb-unidad-ssd-m2-16138.html',
            ],
            'Crucial P5 Plus 2TB NVMe PCIe 4.0' => [
                'PCComponentes' => 'https://www.pccomponentes.com/crucial-p5-plus-2tb-ssd-m2-2280-pcie-40',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/crucial-p5-plus-2tb-disco-ssd-nvme-pcie-4-0-15246.html',
            ],
            'Lexar NM790 4TB NVMe PCIe 4.0' => [
                'PCComponentes' => 'https://www.pccomponentes.com/disco-duro-lexar-nm790-4tb-ssd-m-2-2280-pcie-4-0-nvme-slc',
                'Coolmod'       => 'https://www.coolmod.com/lexar-nm790-4tb-pcie-gen4-x4-nvme-ssd',
                'Neobyte'       => 'https://www.neobyte.es/lexar-nm790-4tb-unidad-ssd-m2-21879.html',
            ],
            'ADATA XPG Gammix S70 Blade 1TB NVMe PCIe 4.0' => [
                'PCComponentes' => 'https://www.pccomponentes.com/disco-duro-xpg-gammix-s70-blade-ssd-1tb-m-2-2280-pcie-gen4x4-nvme',
                'Coolmod'       => 'https://www.coolmod.com/adata-xpg-gammix-s70-blade-1tb-gen4-pcie-x4-nvme',
                'Neobyte'       => 'https://www.neobyte.es/xpg-gammix-s70-blade-1tb-pcie-40-unidad-ssd-m2-24741.html',
            ],
            'Samsung 9100 Pro 2TB NVMe PCIe 5.0' => [
                'PCComponentes' => 'https://www.pccomponentes.com/disco-duro-samsung-9100-pro-mz-vap2t0bw-2tb-m-2-pcie-5-0-14700mb-s-plus-cifrado-avanzado',
                'Coolmod'       => 'https://www.coolmod.com/samsung-9100-pro-2tb-pcie-gen5-x4-nvme-2-0-ssd',
                'Neobyte'       => 'https://www.neobyte.es/samsung-9100-pro-2tb-pcie-50-unidad-ssd-m2-27955.html',
            ],
            'Crucial T705 2TB NVMe PCIe 5.0' => [
                'PCComponentes' => 'https://www.pccomponentes.com/disco-duro-crucial-t705-2tb-ssd-m-2-pcie-gen5-nvme',
                'Coolmod'       => 'https://www.coolmod.com/crucial-t705-2tb-pcie-gen5-x4-nvme-ssd',
                'Neobyte'       => '',
            ],
            'Western Digital Black SN850X 4TB NVMe PCIe 5.0' => [
                'PCComponentes' => '',
                'Coolmod'       => 'https://www.coolmod.com/western-digital-black-sn850x-4tb-nvme-pcie-gen4',
                'Neobyte'       => 'https://www.neobyte.es/western-digital-black-sn850x-4tb-ssd-m2-nvme-16393.html',
            ],
            'Samsung 870 EVO 1TB SATA SSD' => [
                'PCComponentes' => 'https://www.pccomponentes.com/disco-duro-samsung-870-evo-ssd-2-5-1tb-sata3-negro',
                'Coolmod'       => 'https://www.coolmod.com/samsung-870-evo-ssd-25-1tb-sata3-disco-duro-ssd',
                'Neobyte'       => 'https://www.neobyte.es/disco-ssd-samsung-1tb-870-evo-sata3-mz-77e1t0beu-8241.html',
            ],
            'Crucial MX500 2TB SATA SSD' => [
                'PCComponentes' => 'https://www.pccomponentes.com/crucial-mx500-ssd-2tb-sata3',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Kingston A400 480GB SATA SSD' => [
                'PCComponentes' => 'https://www.pccomponentes.com/disco-duro-kingston-a400-480gb-disco-ssd-sata3-500mb-s',
                'Coolmod'       => 'https://www.coolmod.com/kingston-ssdnow-a400-480gb-25-sata3-disco-ssd',
                'Neobyte'       => 'https://www.neobyte.es/disco-ssd-kingston-480gb-a400-sa400s37-480g-1709.html',
            ],
            'Corsair MP600 Core XT 4TB SATA SSD' => [
                'PCComponentes' => 'https://www.pccomponentes.com/disco-duro-corsair-mp600-core-xt-4-tb-gen4-pcie-x4-nvme-m-2',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/corsair-mp600-core-xt-4tb-pcie-40-unidad-ssd-m2-17119.html',
            ],
            'Seagate Barracuda 4TB HDD 3.5"' => [
                'PCComponentes' => 'https://www.pccomponentes.com/disco-duro-seagate-barracuda-4tb-disco-interno-hdd-3-5-sata3',
                'Coolmod'       => 'https://www.coolmod.com/seagate-barracuda-compute-4tb-35-disco-duro',
                'Neobyte'       => 'https://www.neobyte.es/seagate-barracuda-4tb-disco-duro-35-sata-174.html',
            ],
            'Western Digital Red Plus 8TB HDD 3.5"' => [
                'PCComponentes' => 'https://www.pccomponentes.com/disco-duro-wd-red-plus-nas-8tb-disco-interno-hdd-3-5-256mb-sata-3',
                'Coolmod'       => 'https://www.coolmod.com/western-digital-nas-plus-wd80efpx-8tb-3-5-sata3',
                'Neobyte'       => 'https://www.neobyte.es/western-digital-red-pro-8tb-disco-duro-35-sata-22569.html',
            ],

            // ---------- Gabinetes ----------
            'Cooler Master NR200P' => [
                'PCComponentes' => 'https://www.pccomponentes.com/cooler-master-nr200p-cristal-templado-usb-30-negra',
                'Coolmod'       => 'https://www.coolmod.com/cooler-master-nr200p-cristal-templado-negro-caja-torre',
                'Neobyte'       => 'https://www.neobyte.es/cooler-master-masterbox-nr200p-negro-caja-mini-itx-8616.html',
            ],
            'Fractal Design North' => [
                'PCComponentes' => 'https://www.pccomponentes.com/torre-pc-fractal-design-north-tg-atx-mid-tower-cristal-templado-usb-3-2-charcoal-black',
                'Coolmod'       => 'https://www.coolmod.com/fractal-design-north-negro',
                'Neobyte'       => 'https://www.neobyte.es/fractal-design-north-xl-charcoal-black-caja-e-atx-23552.html',
            ],
            'NZXT H510' => [
                'PCComponentes' => 'https://www.pccomponentes.com/nzxt-h510-cristal-templado-usb-31-blanco-mate',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Corsair 4000D' => [
                'PCComponentes' => 'https://www.pccomponentes.com/torre-pc-torre-corsair-4000d-airflow-blanco-atx-eatx-vidrio-templado-3x120mm-usb-c-sin-fuente',
                'Coolmod'       => 'https://www.coolmod.com/corsair-frame-4000d-blanco',
                'Neobyte'       => 'https://www.neobyte.es/corsair-frame-4000d-rs-blanca-caja-e-atx-29441.html',
            ],
            'Lian Li Lancool III' => [
                'PCComponentes' => 'https://www.pccomponentes.com/torre-pc-lian-li-lancool-iii-rgb-cristal-templado-usb-3-2-negra',
                'Coolmod'       => 'https://www.coolmod.com/lian-li-lancool-iii-argb-negro',
                'Neobyte'       => 'https://www.neobyte.es/lian-li-lancool-iii-rgb-caja-eatx-17763.html',
            ],
            'DeepCool CH560 Digital' => [
                'PCComponentes' => 'https://www.pccomponentes.com/torre-pc-deepcool-ch560-digital-argb-mid-tower-cristal-templado-usb-3-2-blanca',
                'Coolmod'       => 'https://www.coolmod.com/deepcool-ch560-digital-mesh-blanco',
                'Neobyte'       => 'https://www.neobyte.es/deepcool-ch560-digital-wh-blanca-caja-eatx-19491.html',
            ],
            'Thermaltake View 51 TG ARGB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/thermaltake-view-51-argb-edition-cristal-templado-usb-30',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Lian Li PC-O11 Vision' => [
                'PCComponentes' => 'https://www.pccomponentes.com/torre-pc-lian-li-o11-vision-compact-torre-e-atx-cristal-templado-usb-c-3-0-negra',
                'Coolmod'       => 'https://www.coolmod.com/lian-li-011-vision-compacta-negro',
                'Neobyte'       => 'https://www.neobyte.es/lian-li-o11-vision-compact-caja-eatx-25547.html',
            ],
            'Corsair 5000X RGB' => [
                'PCComponentes' => 'https://www.pccomponentes.com/torre-pc-corsair-icue-5000x-rgb-cristal-templado-usb-3-0-blanca',
                'Coolmod'       => 'https://www.coolmod.com/corsair-icue-5000x-rgb-white-cristal-templado-caja-torre',
                'Neobyte'       => 'https://www.neobyte.es/corsair-icue-5000x-smart-blanca-caja-atx-rgb-7949.html',
            ],
            'Jonsbo D31 Mesh' => [
                'PCComponentes' => 'https://www.pccomponentes.com/torre-pc-caja-pc-jonsbo-d31-mesh-negro-acero-ventana-vidrio-templado-micro-atx-itx-dtx-usb-c',
                'Coolmod'       => 'https://www.coolmod.com/jonsbo-d31-mesh-negro',
                'Neobyte'       => '',
            ],
            'ASUS Prime AP201' => [
                'PCComponentes' => 'https://www.pccomponentes.com/torre-pc-asus-ap201-prime-case-tg-cristal-templado-usb-3-2-negra',
                'Coolmod'       => 'https://www.coolmod.com/asus-prime-ap201-cristal-templado-negro',
                'Neobyte'       => 'https://www.neobyte.es/asus-prime-ap201-tempered-glass-caja-microatx-17385.html',
            ],
            'Antec Performance 1M' => [
                'PCComponentes' => 'https://www.pccomponentes.com/torre-pc-caja-mini-tower-antec-performance-1-m-aventurine-itx-negro-aluminio-plastico-acero',
                'Coolmod'       => 'https://www.coolmod.com/antec-performance-1m-aventurine',
                'Neobyte'       => 'https://www.neobyte.es/asus-prime-ap201-tempered-glass-caja-microatx-17385.html',
            ],
            'Jonsbo Z20' => [
                'PCComponentes' => 'https://www.pccomponentes.com/torre-pc-carcasa-jonsbo-z20-blanco-mini-tower-micro-atx',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/jonsbo-z20-blanca-caja-micro-atx-37363.html',
            ],
            'Fractal Design Pop Mini' => [
                'PCComponentes' => 'https://www.pccomponentes.com/torre-pc-fractal-design-pop-mini-air-rgb-mini-tower-cristal-templado-usb-3-2-blanca',
                'Coolmod'       => 'https://www.coolmod.com/fractal-design-pop-mini-air-argb-blanco',
                'Neobyte'       => 'https://www.neobyte.es/fractal-design-pop-mini-air-rgb-white-tg-clear-tint-caja-micro-atx-19023.html',
            ],
            'NZXT H5 Flow' => [
                'PCComponentes' => 'https://www.pccomponentes.com/torre-pc-nzxt-h5-flow-2024-midi-tower-e-atx-cristal-templado-usb-c-negra',
                'Coolmod'       => 'https://www.coolmod.com/nzxt-h5-flow-2024-negro',
                'Neobyte'       => 'https://www.neobyte.es/nzxt-h5-flow-2024-caja-e-atx-24719.html',
            ],
            'Lian Li PC-O11 Air Mini' => [
                'PCComponentes' => 'https://www.pccomponentes.com/torre-pc-lian-li-o11-air-mini-cristal-templado-usb-3-0-aluminio-negro',
                'Coolmod'       => 'https://www.coolmod.com/lian-li-o11-mini-air-negro',
                'Neobyte'       => 'https://www.neobyte.es/lian-li-o11-air-mini-negra-caja-eatx-17143.html',
            ],
            'Cooler Master NR200P V2' => [
                'PCComponentes' => 'https://www.pccomponentes.com/torre-pc-caja-cooler-master-masterbox-nr200p-v2-negro-mini-itx-vidrio-templado-acero-abs',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/cooler-master-masterbox-nr200p-v2-caja-mini-itx-26195.html',
            ],
            'Fractal Design Terra' => [
                'PCComponentes' => 'https://www.pccomponentes.com/fractal-design-terra-mini-tower-usb-c-grafito',
                'Coolmod'       => 'https://www.coolmod.com/fractal-design-terra-negro',
                'Neobyte'       => 'https://www.neobyte.es/fractal-design-terra-graphite-caja-mini-itx-21222.html',
            ],
            'Lian Li DAN A3-mATX' => [
                'PCComponentes' => 'https://www.pccomponentes.com/torre-pc-lian-li-y-dan-cases-a3-matx-mini-torre-matx-mesh-usb-c-negra',
                'Coolmod'       => 'https://www.coolmod.com/lian-li-a3-matx-negro',
                'Neobyte'       => 'https://www.neobyte.es/lian-li-a3-dan-caja-micro-atx-23836.html',
            ],
            'Jonsbo D31 STD' => [
                'PCComponentes' => 'https://www.pccomponentes.com/torre-pc-torre-jonsbo-d31-std-negro-factor-micro-atx-dtx-itx-frontal-atx-para-pc-gaming',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/jonsbo-d31-std-caja-micro-atx-37218.html',
            ],
            'Lian Li A4-H2O' => [
                'PCComponentes' => 'https://www.pccomponentes.com/torre-pc-caja-pc-lian-li-a4-h2o-mini-itx-con-refrigeracion-liquida-aio',
                'Coolmod'       => 'https://www.coolmod.com/lian-li-a4-h2o-pcie-5-0-negro',
                'Neobyte'       => 'https://www.neobyte.es/lian-li-a4h2o-caja-mini-atx-17765.html',
            ],

            // ---------- Fuentes de alimentación (PSU) ----------
            'Corsair CV550' => [
                'PCComponentes' => 'https://www.pccomponentes.com/corsair-cv550-cv-series-550w-80-plus-bronze',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Thermaltake Smart 600W' => [
                'PCComponentes' => 'https://www.pccomponentes.com/thermaltake-smart-rgb-600w-80-plus',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'DeepCool PQ650M' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/fuente-alimentacion-deepcool-pq650m-650w-13365.html',
            ],
            'Seasonic Focus GX-750' => [
                'PCComponentes' => 'https://www.pccomponentes.com/fuente-alimentacion-seasonic-focus-gx-750-white-atx-3-pcie-5-1-750w-80-plus-gold-modular',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/seasonic-focus-gx-750-atx-3-2024-pcie-50-blanca-fuente-de-alimentacion-750w-24387.html',
            ],
            'EVGA SuperNOVA 850 G6' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/evga-supernova-850-g6-fuente-de-alimentacion-850w-80-gold-14492.html',
            ],
            'MSI MAG A750GL PCIE5' => [
                'PCComponentes' => 'https://www.pccomponentes.com/fuente-alimentacion-msi-mag-a750gl-pcie5-ii-atx-3-1-750w-80-plus-gold-modular',
                'Coolmod'       => 'https://www.coolmod.com/msi-mag-a750gl-pcie5-ii-80-plus-gold-750w-atx-3-1-pcie-5-1-modular',
                'Neobyte'       => 'https://www.neobyte.es/msi-mag-a750gl-ii-atx-30-pcie-51-fuente-de-alimentacion-750w-27007.html',
            ],
            'Corsair RM850x' => [
                'PCComponentes' => 'https://www.pccomponentes.com/fuente-alimentacion-fuente-de-alimentacion-corsair-rm850x-shift-850w-gold-modular-atx-3-1-lateral',
                'Coolmod'       => 'https://www.coolmod.com/corsair-rm850x-shift-cybenetics-gold-850w-atx-3-1-pcie-5-1-modular',
                'Neobyte'       => 'https://www.neobyte.es/corsair-rm850x-atx-31-pcie-51-fuente-de-alimentacion-850w-21818.html',
            ],
            'be quiet! Pure Power 12 M 850W' => [
                'PCComponentes' => 'https://www.pccomponentes.com/be-quiet-pure-power-12-m-850w-80-plus-gold-modular',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Fractal Design Ion+ 3 850W Platinum' => [
                'PCComponentes' => 'https://www.pccomponentes.com/fuente-alimentacion-fuente-de-alimentacion-fractal-design-ion-3-gold-850w-80-plus-gold-modular-atx',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/fractal-design-ion-3-gold-850w-fuente-de-alimentacion-850w-32155.html',
            ],
            'Gigabyte UD1000GM PG5' => [
                'PCComponentes' => 'https://www.pccomponentes.com/gigabyte-ud1000gm-pg5-1000w-80-plus-gold-full-modular',
                'Coolmod'       => 'https://www.coolmod.com/gigabyte-ud1000gm-pg5-80-plus-gold-1000w-atx-3-0-pcie-5-0-modular',
                'Neobyte'       => 'https://www.neobyte.es/gigabyte-ud1000gm-pg5-atx-30-pcie-50-rev-2-fuente-de-alimentacion-1000w-16962.html',
            ],
            'Seasonic Prime TX-1000' => [
                'PCComponentes' => 'https://www.pccomponentes.com/seasonic-prime-tx-1000-1000w-80-plus-titanium-modular',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS ROG Thor 1000P2' => [
                'PCComponentes' => 'https://www.pccomponentes.com/asus-rog-thor-1000w-platinum-ii-80-plus-platinum-modular',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Corsair HX1000i' => [
                'PCComponentes' => 'https://www.pccomponentes.com/corsair-hxi-series-hx1000i-1000w-80-plus-platinum-modular',
                'Coolmod'       => 'https://www.coolmod.com/corsair-hx1000i-2023-80-plus-platinum-1000w-modular',
                'Neobyte'       => 'https://www.neobyte.es/corsair-hx1000i-1000w-80-platinum-atx-3-0-pcie-5-0-fuente-de-alimentacion-17594.html',
            ],
            'Thermaltake Toughpower GF3 1200W' => [
                'PCComponentes' => 'https://www.pccomponentes.com/fuente-alimentacion-thermaltake-toughpower-gf-3-1200w-pcie-gen-5-0-80-plus-gold-full-modular',
                'Coolmod'       => 'https://www.coolmod.com/thermaltake-toughpower-gf3-80-plus-gold-1200w-atx-3-0-pcie-5-0-modular',
                'Neobyte'       => 'https://www.neobyte.es/thermaltake-toughpower-gf3-atx-30-pcie-50-fuente-de-alimentacion-1200w-20300.html',
            ],
            'Seasonic Prime TX-1300' => [
                'PCComponentes' => 'https://www.pccomponentes.com/seasonic-prime-tx-1300-1300w-80-plus-titanium-modular',
                'Coolmod'       => 'https://www.coolmod.com/seasonic-prime-tx-80-plus-titanium-1300w-atx-3-0-pcie-5-0-modular',
                'Neobyte'       => '',
            ],
            'Corsair AX1600i' => [
                'PCComponentes' => 'https://www.pccomponentes.com/corsair-ax1600i-1600w-80-plus-titanium-modular',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'be quiet! Dark Power Pro 13 1600W' => [
                'PCComponentes' => 'https://www.pccomponentes.com/be-quiet-dark-power-pro-13-1600w-80-plus-titanium-modular',
                'Coolmod'       => 'https://www.coolmod.com/be-quiet-dark-power-pro-13-80-plus-titanium-1600w-atx-3-1-pcie-5-1-modular',
                'Neobyte'       => 'https://www.neobyte.es/be-quiet-dark-power-pro-13-fuente-de-alimentacion-1600w-18477.html',
            ],
            'Silverstone SX700-PT' => [
                'PCComponentes' => 'https://www.pccomponentes.com/silverstone-sx700-pt-700w-80-plus-platinum-modular',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Seasonic Focus SFX-L 650W' => [
                'PCComponentes' => 'https://www.pccomponentes.com/fuente-alimentacion-fuente-de-alimentacion-seasonic-650w-80-plus-platinum-focus-spx-650-sfx-modular-adaptador-sfx-atx',
                'Coolmod'       => 'https://www.coolmod.com/seasonic-focus-spx-80-plus-platinum-650w-modular',
                'Neobyte'       => '',
            ],

            // ---------- Refrigeración por aire ----------
            'DeepCool Assassin IV' => [
                'PCComponentes' => 'https://www.pccomponentes.com/deepcool-assassin-iv-ventilador-cpu-140mm-negro',
                'Coolmod'       => 'https://www.coolmod.com/deepcool-assassin-iv-negro',
                'Neobyte'       => 'https://www.neobyte.es/deepcool-assassin-iv-disipador-cpu-20084.html',
            ],
            'DeepCool AK620 G2' => [
                'PCComponentes' => 'https://www.pccomponentes.com/search/?query=DeepCool+AK620',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/deepcool-ak620-g2-refrigeracion-cpu-37700.html',
            ],
            'Thermalright Phantom Spirit 120 EVO' => [
                'PCComponentes' => 'https://www.pccomponentes.com/ventilador-cpu-refrigeracion-aire-thermalright-socket-am4-am5-lga1700-120mm-phantom-spirit-120-evo-argb',
                'Coolmod'       => 'https://www.coolmod.com/thermalright-phantom-spirit-120-evo-argb-negro',
                'Neobyte'       => 'https://www.neobyte.es/thermalright-phantom-spirit-120-evo-refrigeracion-cpu-36697.html',
            ],
            'Noctua NH-D15' => [
                'PCComponentes' => '',
                'Coolmod'       => 'https://www.coolmod.com/noctua-nh-d15-disipador-cpu',
                'Neobyte'       => 'https://www.neobyte.es/noctua-nh-d15-refrigeracion-cpu-4748.html',
            ],
            'Noctua NH-U12S redux' => [
                'PCComponentes' => '',
                'Coolmod'       => 'https://www.coolmod.com/noctua-nh-u12s-redux',
                'Neobyte'       => 'https://www.neobyte.es/noctua-nh-u12s-redux-refrigeracion-cpu-32271.html',
            ],
            'Noctua NH-D15S' => [
                'PCComponentes' => '',
                'Coolmod'       => 'https://www.coolmod.com/noctua-nh-d15s',
                'Neobyte'       => 'https://www.neobyte.es/noctua-nh-d15s-refrigeracion-cpu-3786.html',
            ],
            'Thermalright AXP120-X67' => [
                'PCComponentes' => 'https://www.pccomponentes.com/ventilador-cpu-refrigeracion-aire-thermalright-socket-am4-am5-lga-1200-1700-120mm-axp120-x67-bajo-perfil',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Noctua NH-L12S' => [
                'PCComponentes' => 'https://www.pccomponentes.com/noctua-nh-l12s-cpu-cooler',
                'Coolmod'       => 'https://www.coolmod.com/noctua-nh-l12s',
                'Neobyte'       => 'https://www.neobyte.es/noctua-nhl12s-ventilador-cpu-multisocket-low-profile-12439.html',
            ],
            'Arctic Freezer 36' => [
                'PCComponentes' => 'https://www.pccomponentes.com/ventilador-cpu-refrigeracion-aire-arctic-socket-lga1851-am5-120mm-freezer-36-push-pull-2-ventiladores',
                'Coolmod'       => 'https://www.coolmod.com/arctic-freezer-36',
                'Neobyte'       => 'https://www.neobyte.es/arctic-freezer-36-disipador-cpu-30923.html',
            ],
            'be quiet! Dark Rock Pro 5' => [
                'PCComponentes' => 'https://www.pccomponentes.com/be-quiet-dark-rock-pro-5-ventilador-cpu-7-pipes-135mm-negro',
                'Coolmod'       => 'https://www.coolmod.com/dark-rock-pro-5-negro',
                'Neobyte'       => 'https://www.neobyte.es/be-quiet-dark-rock-pro-5-disipador-cpu-22362.html',
            ],
            'Thermalright Peerless Assassin 120 SE' => [
                'PCComponentes' => 'https://www.pccomponentes.com/ventilador-cpu-refrigeracion-aire-thermalright-socket-intel-amd-120mm-peerless-assassin-120-se-blanco-argb-doble-torre',
                'Coolmod'       => 'https://www.coolmod.com/thermalright-peerless-assassin-120-se-argb-blanco',
                'Neobyte'       => '',
            ],
            'Noctua NH-L9i-17xx' => [
                'PCComponentes' => 'https://www.pccomponentes.com/noctua-nh-l9i-17xx-ventilador-cpu-92mm',
                'Coolmod'       => 'https://www.coolmod.com/noctua-nh-l9i-17xx',
                'Neobyte'       => 'https://www.neobyte.es/noctua-nhl9i17xx-refrigeracion-cpu-12241.html',
            ],

            // ---------- Refrigeración líquida ----------
            'Arctic Liquid Freezer III Pro 240' => [
                'PCComponentes' => 'https://www.pccomponentes.com/refrigeracion-liquida-arctic-liquid-freezer-iii-pro-240-2x120mm-fdb-131-m3-h-negro',
                'Coolmod'       => 'https://www.coolmod.com/arctic-liquid-freezer-iii-pro-240-negro',
                'Neobyte'       => 'https://www.neobyte.es/arctic-liquid-freezer-iii-pro-240-refrigeracion-liquida-240mm-33168.html',
            ],
            'Corsair iCUE H100i Elite LCD' => [
                'PCComponentes' => 'https://www.pccomponentes.com/corsair-icue-h100i-elite-lcd-xt-kit-de-refrigeracion-liquida-240mm-negro',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'NZXT Kraken 240' => [
                'PCComponentes' => 'https://www.pccomponentes.com/nzxt-kraken-240-kit-de-refrigeracion-liquida',
                'Coolmod'       => 'https://www.coolmod.com/nzxt-kraken-elite-240-lcd-display-negro',
                'Neobyte'       => 'https://www.neobyte.es/nzxt-kraken-elite-240-refrigeracion-liquida-240mm-26535.html',
            ],
            'DeepCool LT240' => [
                'PCComponentes' => 'https://www.pccomponentes.com/refrigerador-liquido-deepcool-lt240-argb-240mm-2x120mm-rgb-sockets-intel-amd',
                'Coolmod'       => 'https://www.coolmod.com/deepcool-lt240-argb-240mm-negro',
                'Neobyte'       => 'https://www.neobyte.es/deepcool-lt240-argb-refrigeracion-liquida-240mm-25308.html',
            ],
            'Arctic Liquid Freezer III 280' => [
                'PCComponentes' => 'https://www.pccomponentes.com/refrigeracion-liquida-arctic-liquid-freezer-iii-pro-280mm-2-ventiladores-vrm-premium',
                'Coolmod'       => 'https://www.coolmod.com/arctic-liquid-freezer-iii-pro-280-negro',
                'Neobyte'       => 'https://www.neobyte.es/arctic-liquid-freezer-iii-pro-240-refrigeracion-liquida-240mm-33168.html',
            ],
            'NZXT Kraken Elite 280' => [
                'PCComponentes' => 'https://www.pccomponentes.com/nzxt-kraken-elite-280-rgb-kit-refrigeracion-liquida-con-pantalla-ips-280mm-negro',
                'Coolmod'       => 'https://www.coolmod.com/nzxt-kraken-elite-280-rgb-2025-lcd-display-negro',
                'Neobyte'       => 'https://www.neobyte.es/nzxt-kraken-elite-280-rgb-refrigeracion-liquida-280mm-26527.html',
            ],
            'Corsair iCUE H115i RGB Elite' => [
                'PCComponentes' => 'https://www.pccomponentes.com/refrigeracion-liquida-corsair-icue-link-h115i-rgb-280mm-2-ventiladores-rgb',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Arctic Liquid Freezer III Pro 360' => [
                'PCComponentes' => 'https://www.pccomponentes.com/refrigeracion-liquida-arctic-liquid-freezer-iii-pro-360mm-3-ventiladores-vrm-integrado',
                'Coolmod'       => 'https://www.coolmod.com/arctic-liquid-freezer-iii-pro-360-negro',
                'Neobyte'       => 'https://www.neobyte.es/arctic-liquid-freezer-iii-pro-360-black-refrigeracion-liquida-360mm-34757.html',
            ],
            'Corsair iCUE H150i Elite LCD XT' => [
                'PCComponentes' => 'https://www.pccomponentes.com/corsair-icue-h150i-elite-lcd-xt-kit-de-refrigeracion-liquida-360mm-blanco',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'NZXT Kraken Elite 360' => [
                'PCComponentes' => 'https://www.pccomponentes.com/nzxt-kraken-elite-360-kit-refrigeracion-liquida-con-pantalla-ips-360mm-negro',
                'Coolmod'       => 'https://www.coolmod.com/nzxt-kraken-elite-360-2025-lcd-display-negro',
                'Neobyte'       => 'https://www.neobyte.es/nzxt-kraken-elite-360-refrigeracion-liquida-360mm-26534.html',
            ],
            'be quiet! Pure Loop 2 FX 360' => [
                'PCComponentes' => 'https://www.pccomponentes.com/refrigeracion-liquida-be-quiet-pure-loop-2-fx-360mm-3-ventiladores-argb-hub-pwm',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Arctic Liquid Freezer III Pro 420' => [
                'PCComponentes' => 'https://www.pccomponentes.com/refrigeracion-liquida-arctic-liquid-freezer-iii-pro-420mm-3-ventiladores-vrm-pwm',
                'Coolmod'       => 'https://www.coolmod.com/arctic-liquid-freezer-iii-pro-420-negro',
                'Neobyte'       => 'https://www.neobyte.es/arctic-liquid-freezer-iii-pro-360-black-refrigeracion-liquida-360mm-34757.html',
            ],
            'NZXT Kraken Elite 420' => [
                'PCComponentes' => 'https://www.pccomponentes.com/refrigeracion-liquida-nzxt-kraken-elite-420-rgb-v2-3x140mm-pantalla-lcd-ips-negra',
                'Coolmod'       => 'https://www.coolmod.com/nzxt-kraken-elite-420-rgb-lcd-display-negro',
                'Neobyte'       => 'https://www.neobyte.es/nzxt-kraken-elite-420-rgb-refrigeracion-liquida-420mm-29130.html',
            ],

            // ---------- Ventiladores ----------
            'Noctua NF-F12 PWM' => [
                'PCComponentes' => 'https://www.pccomponentes.com/noctua-nf-f12-pwm-120x120x25mm-1500rpm',
                'Coolmod'       => 'https://www.coolmod.com/noctua-nf-f12-1500-rpm-pwm-22dba-ventilador-12-cm',
                'Neobyte'       => 'https://www.neobyte.es/ventilador-noctua-120x120-nf-f12-pwm-6973.html',
            ],
            'Noctua NF-A14 PWM' => [
                'PCComponentes' => 'https://www.pccomponentes.com/ventilador-suplementario-noctua-nf-a14-pwm-140mm-1500rpm-con-kit-anti-vibracion-y-pwm',
                'Coolmod'       => 'https://www.coolmod.com/noctua-nf-a14-pwm-ventilador-14-cm',
                'Neobyte'       => 'https://www.neobyte.es/ventilador-noctua-caja-nf-a14-140mm-3785.html',
            ],
            'Arctic P12 PWM PST Value Pack (x5)' => [
                'PCComponentes' => 'https://www.pccomponentes.com/ventiladores-suplementarios-arctic-p12-pro-pwm-pst-low-noise-120mm-negros-pack-5-unidades',
                'Coolmod'       => 'https://www.coolmod.com/arctic-p12-pro-pwm-pst-120mm-negro-pack-5',
                'Neobyte'       => 'https://www.neobyte.es/arctic-p12-pro-pst-ln-pack-de-5-ventilador-120mm-37677.html',
            ],
            'Arctic P14 PWM PST Value Pack (x5)' => [
                'PCComponentes' => 'https://www.pccomponentes.com/arctic-p14-value-pack-de-5-ventiladores-140mm',
                'Coolmod'       => 'https://www.coolmod.com/arctic-p14-pro-pst-140mm-negro-pack-5',
                'Neobyte'       => 'https://www.neobyte.es/arctic-p14-pack-de-5-ventilador-140mm--35010.html',
            ],
            'Lian Li UNI FAN SL120 RGB (x3)' => [
                'PCComponentes' => '',
                'Coolmod'       => 'https://www.coolmod.com/lian-li-uni-fan-sl-inf-120-triple-pack-argb-12cm-blanco',
                'Neobyte'       => '',
            ],
            'Lian Li UNI FAN SL140 RGB (x2)' => [
                'PCComponentes' => 'https://www.pccomponentes.com/search/?query=Lian+Li+UNI+FAN+SL140+RGB',
                'Coolmod'       => 'https://www.coolmod.com/lian-li-uni-fan-sl140-rgb-pwm-dual-pack-blanco-ventilador-14-cm',
                'Neobyte'       => '',
            ],
            'Corsair LL120 RGB Triple Pack + Lighting Node Core' => [
                'PCComponentes' => 'https://www.pccomponentes.com/corsair-ll120-rgb-pack-3-ventiladores-120mm-con-lightning-node-pro',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Noctua NF-A12x25 PWM' => [
                'PCComponentes' => 'https://www.pccomponentes.com/ventilador-suplementario-noctua-nf-a12x25-120mm-2000rpm-pwm-5v-con-kit-anti-vibracion',
                'Coolmod'       => 'https://www.coolmod.com/noctua-nf-a12x25-pwm-ventilador-12-cm',
                'Neobyte'       => 'https://www.neobyte.es/noctua-nf-a12x25-pwm-ventilador-caja-120-mm-3784.html',
            ],
            'be quiet! Silent Wings 4 140mm PWM' => [
                'PCComponentes' => 'https://www.pccomponentes.com/be-quiet-silent-wings-4-pwm-highspeed-ventilador-140mm-negro',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/be-quiet-silent-wings-4-pwm-high-speed-ventilador-140mm-31204.html',
            ],
            'Noctua NF-A12x15 PWM' => [
                'PCComponentes' => 'https://www.pccomponentes.com/search/?query=Noctua+NF-A12x15+PWM',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/noctua-nf-a12x15-pwm-ventilador-120mm-12420.html',
            ],
            'Scythe Slip Stream 140 Slim PWM' => [
                'PCComponentes' => 'https://www.pccomponentes.com/refrigeracion-aire-arctic-140mm-p14-slim-pwm-pst-bajo-perfil-alta-presion-estatica',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Thermalright TL-C12 Pro ARGB (x3)' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => 'https://www.neobyte.es/thermalright-tlc12cs-argb-pack-de-3-ventilador-120mm-36731.html',
            ],
            'DeepCool FL12R ARGB (x3)' => [
                'PCComponentes' => 'https://www.pccomponentes.com/ventilador-suplementario-deepcool-fl12r-se-wh-120-mm-1900-rpm-argb-pack-3-unidades',
                'Coolmod'       => 'https://www.coolmod.com/deepcool-fl12r-argb-120mm-triple-pack-blanco',
                'Neobyte'       => '',
            ],
            'Phanteks D30 140mm DRGB (x3)' => [
                'PCComponentes' => 'https://www.pccomponentes.com/search/?query=Phanteks+D30+140mm+DRGB+%28x3%29',
                'Coolmod'       => 'https://www.coolmod.com/phanteks-d30-pack-3u-pwm-drgb-reverse-120mm-negro',
                'Neobyte'       => '',
            ],
        ];

        foreach ($productos as $nombreComponente => $tiendasUrls) {

            $componente = Componente::where('nombre', $nombreComponente)->first();

            if (!$componente) {
                $this->command?->warn("Componente no encontrado: {$nombreComponente}");
                continue;
            }

            foreach ($tiendasUrls as $nombreTienda => $url) {

                if ($url === '') {
                    // Sin url todavía: no la guardamos para no crear
                    // registros "activos" apuntando a nada.
                    continue;
                }

                $tiendaId = $tiendas[$nombreTienda] ?? null;

                if (!$tiendaId) {
                    $this->command?->warn("Tienda no encontrada: {$nombreTienda} (componente: {$nombreComponente})");
                    continue;
                }

                UrlProductoTienda::updateOrCreate(
                    [
                        'componente_id' => $componente->id,
                        'tienda_id'     => $tiendaId,
                    ],
                    [
                        'url'    => $url,
                        'activo' => true,
                    ]
                );
            }
        }
    }
}
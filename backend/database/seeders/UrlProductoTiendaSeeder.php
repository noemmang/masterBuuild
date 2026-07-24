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
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI MAG B550 Tomahawk' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte B550 Aorus Pro AX' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS ROG Crosshair VIII Hero' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI MEG X570 Unify' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte X570 Aorus Master' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI B550M Pro-VDH WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASRock B550M Steel Legend' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte B550M Aorus Pro' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS ROG Strix B550-I Gaming' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI MPG B550I Gaming Edge WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASRock X570 Phantom Gaming-ITX/TB3' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASRock B550 Phantom Gaming 4' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Biostar B550MH 3.0' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS TUF Gaming B550M-Plus WiFi II' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],

            // ---------- Placas base AM5 ----------
            'ASUS TUF Gaming B650-Plus WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI MAG B650 Tomahawk WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte B650 Aorus Elite AX' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS ROG Crosshair X670E Hero' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI MEG X670E Ace' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte X670E Aorus Master' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASRock B650M Pro RS WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI PRO B650M-A WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte B650M Aorus Elite AX' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS ROG Strix B650E-I Gaming WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI MPG B650I Edge WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASRock B650E PG-ITX WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS ProArt X670E-Creator WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI MAG X870 Tomahawk WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte X870E Aorus Master' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],

            // ---------- Placas base LGA1700 ----------
            'ASUS ROG Maximus Z690 Hero' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI MEG Z690 Unify-X' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte Z690 Aorus Pro DDR4' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS ROG Strix Z790-E Gaming WiFi II' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI MAG Z790 Tomahawk WiFi DDR4' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte Z790 Aorus Elite AX' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI PRO B660M-A DDR4' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASRock B760M Pro RS WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte B760M Aorus Elite AX DDR4' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS ROG Strix Z690-I Gaming WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI MPG Z790I Edge WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASRock Z790 PG-ITX/TB4' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS TUF Gaming Z790-Plus WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],

            // ---------- Placas base LGA1851 ----------
            'ASUS ROG Maximus Z890 Apex' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI MEG Z890 Ace' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte Z890 Aorus Master' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS TUF Gaming Z890-Plus WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI MAG Z890 Tomahawk WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASRock Z890 Taichi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI PRO B860M-A WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte B860M Aorus Elite WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASRock B860M Pro RS WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS ROG Strix Z890-I Gaming WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI MPG Z890I Edge WiFi' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASRock Z890M-ITX/ac' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte Z890I Aorus Ultra WiFi7' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],

            // ---------- RAM DDR4 ----------
            'Corsair Vengeance LPX 16GB DDR4-3200 CL16' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Corsair Vengeance LPX 128GB DDR4-3200 CL16' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'G.Skill Trident Z RGB 16GB DDR4-3600 CL16' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'G.Skill Trident Z RGB 32GB DDR4-3600 CL16' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Kingston Fury Beast 16GB DDR4-3200 CL16' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Kingston Fury Beast RGB 32GB DDR4-3600 CL18' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Crucial Ballistix 16GB DDR4-3600 CL16' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Crucial Ballistix MAX 32GB DDR4-4000 CL18' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'TeamGroup T-Force Vulcan Z 16GB DDR4-3200 CL16' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'G.Skill Ripjaws V 64GB DDR4-3600 CL18' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Corsair Dominator Platinum RGB 32GB DDR4-3600 CL16' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'XPG Spectrix D60G 128GB DDR4-3200 CL16' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Patriot Viper Steel 32GB DDR4-4400 CL19' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Silicon Power XPOWER Turbine 16GB DDR4-3200 CL16' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'G.Skill Trident Z 32GB DDR4-4000 CL15' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],

            // ---------- RAM DDR5 ----------
            'Corsair Vengeance DDR5 128GB 5600 CL36' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Corsair Vengeance DDR5 64GB 5600 CL36' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'G.Skill Trident Z5 RGB 32GB DDR5-6000 CL30' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'G.Skill Trident Z5 RGB 64GB DDR5-6000 CL30' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Kingston Fury Beast DDR5 32GB 5200 CL40' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Kingston Fury Renegade RGB DDR5 64GB 6400 CL32' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Crucial Pro DDR5 128GB 5600 CL46' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Crucial Pro DDR5 64GB 5600 CL46' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'TeamGroup T-Force Delta RGB DDR5 32GB 6000 CL38' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'XPG Lancer RGB DDR5 32GB 6000 CL30' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'G.Skill Trident Z5 Neo RGB 32GB DDR5-6000 CL30' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Corsair Dominator Titanium RGB 32GB DDR5-6200 CL32' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Kingston Fury Beast DDR5 64GB 5200 CL40' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'G.Skill Ripjaws S5 32GB DDR5-6000 CL30' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'G.Skill Trident Z5 Neo 64GB DDR5-6000 CL28' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],

            // ---------- GPU ----------
            'MSI GeForce RTX 3060 VENTUS 2X 12GB OC' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS Dual GeForce RTX 3060 Ti OC 8GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte GeForce RTX 3070 EAGLE OC 8GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS TUF Gaming GeForce RTX 3080 10GB OC' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gainward GeForce RTX 3090 Phantom 24GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Zotac Gaming GeForce RTX 3060 Twin Edge OC (SFF)' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI GeForce RTX 4060 VENTUS 2X BLACK OC 8GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Zotac Gaming GeForce RTX 4060 Twin Edge OC 8GB (SFF)' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS Dual GeForce RTX 4060 Ti OC 8GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte GeForce RTX 4060 Ti GAMING OC 16GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI GeForce RTX 4070 GAMING X TRIO 12GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS ROG Strix GeForce RTX 4070 Super OC 12GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte GeForce RTX 4070 Ti Super AORUS MASTER 16GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'PNY GeForce RTX 4080 Super XLR8 Gaming VERTO EPIC-X RGB 16GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI GeForce RTX 4090 SUPRIM LIQUID X 24GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS Dual GeForce RTX 5060 Ti OC 16GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI GeForce RTX 5070 GAMING TRIO OC 12GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte GeForce RTX 5070 Ti AORUS MASTER 16GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS ROG Astral GeForce RTX 5080 OC 16GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI GeForce RTX 5090 SUPRIM LIQUID X 32GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'INNO3D GeForce RTX 5060 TWIN X2 OC 8GB (SFF)' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Sapphire Pulse Radeon RX 6600 8GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'PowerColor Fighter Radeon RX 6700 XT 12GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'XFX Speedster MERC319 Radeon RX 6800 XT 16GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Sapphire Pulse Radeon RX 7600 8GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASRock Radeon RX 7700 XT Challenger 12GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'PowerColor Red Devil Radeon RX 7800 XT 16GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Sapphire Nitro+ Radeon RX 7900 GRE 16GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Sapphire Nitro+ Radeon RX 7900 XTX 24GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Sapphire Pulse Radeon RX 9070 16GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'PowerColor Red Devil Radeon RX 9070 XT 16GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'XFX Speedster MERC 310 Radeon RX 9070 XT 16GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASRock Intel Arc A380 Challenger ITX 6GB (SFF)' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte Intel Arc A750 Eagle OC 8GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASRock Intel Arc A770 Phantom Gaming OC 16GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte Intel Arc B580 Gaming OC 12GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASRock Intel Arc B580 Steel Legend OC 12GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte Intel Arc B770 Gaming OC 16GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS Dual Intel Arc B580 OC 12GB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],

            // ---------- Almacenamiento ----------
            'Samsung 970 EVO Plus 1TB NVMe' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Western Digital Blue SN570 1TB NVMe' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Kingston NV2 2TB NVMe PCIe 3.0' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Samsung 980 Pro 1TB NVMe PCIe 4.0' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Samsung 990 Pro 2TB NVMe PCIe 4.0' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Western Digital Black SN850X 1TB NVMe PCIe 4.0' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Crucial P5 Plus 2TB NVMe PCIe 4.0' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'SK Hynix Platinum P41 1TB NVMe PCIe 4.0' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Sabrent Rocket 4 Plus 2TB NVMe PCIe 4.0' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Lexar NM790 4TB NVMe PCIe 4.0' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ADATA XPG Gammix S70 Blade 1TB NVMe PCIe 4.0' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Samsung 9100 Pro 2TB NVMe PCIe 5.0' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Crucial T705 2TB NVMe PCIe 5.0' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Western Digital Black SN850X 4TB NVMe PCIe 5.0' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Samsung 870 EVO 1TB SATA SSD' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Crucial MX500 2TB SATA SSD' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Kingston A400 480GB SATA SSD' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Corsair MP600 Core XT 4TB SATA SSD' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Seagate Barracuda 4TB HDD 3.5"' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Western Digital Red Plus 8TB HDD 3.5"' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],

            // ---------- Gabinetes ----------
            'Cooler Master NR200P' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Fractal Design North' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'NZXT H510' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Corsair 4000D TG' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Lian Li Lancool III' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'DeepCool CH560 Digital' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Phanteks Eclipse G500A DRGB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Thermaltake View 51 TG ARGB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Lian Li PC-O11 Vision' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Corsair 5000X RGB' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Jonsbo D31 Mesh' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS Prime AP201' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Antec Performance 1M' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Jonsbo Z20' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Fractal Design Pop Mini' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Cooler Master MasterBox Q300L' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'NZXT H5 Flow' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Silverstone FARA R1 Pro' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Lian Li PC-O11 Air Mini' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Thermaltake V200 TG' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Cooler Master NR200P V2' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'NCASE M2' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Fractal Design Terra' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Lian Li DAN A3-mATX' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Jonsbo D31 STD' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Silverstone SG15' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'InWin A1 Plus' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Phanteks Evolv Shift 2 Air' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Cooler Master Elite 110' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'FormD T1 v2' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Lian Li A4-H2O' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Jonsbo T8' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],

            // ---------- Fuentes de alimentación (PSU) ----------
            'Corsair CV550' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Thermaltake Smart 600W' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'DeepCool PQ650M' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'be quiet! System Power 10 650W' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Seasonic Focus GX-750' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'EVGA SuperNOVA 850 G6' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'MSI MAG A750GL PCIE5' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Corsair RM850x' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'be quiet! Pure Power 12 M 850W' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Fractal Design Ion+ 2 860W Platinum' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'XPG Core Reactor II 850W' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Gigabyte UD1000GM PG5' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Seasonic Prime TX-1000' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'ASUS ROG Thor 1000P2' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Corsair HX1000i' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Thermaltake Toughpower GF3 1200W' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Super Flower Leadex VII XG 1300W' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Seasonic Prime TX-1300' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Corsair AX1600i' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'be quiet! Dark Power Pro 13 1600W' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Corsair SF600 Platinum' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Seasonic Focus SGX-650' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Silverstone SX700-PT' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Cooler Master V750 SFX Gold' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
                'Neobyte'       => '',
            ],
            'Seasonic Focus SFX-L 650W' => [
                'PCComponentes' => '',
                'Coolmod'       => '',
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
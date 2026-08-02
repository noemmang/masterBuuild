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
 * cada uno con sus tiendas (Coolmod, Neobyte, etc.) y la url en
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
                'Coolmod' => 'https://www.coolmod.com/amd-ryzen-5-5600x-46ghz-socket-am4-boxed-procesador',
                'Neobyte' => 'https://www.neobyte.es/amd-ryzen-5-5600x-procesador-am4-7702.html',
            ],
            'AMD Ryzen 7 5800X' => [
                'Coolmod' => 'https://www.coolmod.com/amd-ryzen-7-5800x-47ghz-socket-am4-boxed-procesador',
                'Neobyte' => 'https://www.neobyte.es/amd-ryzen-7-5800x-procesador-am4-7699.html',
            ],
            'AMD Ryzen 9 5950X' => [
                'Coolmod' => 'https://www.coolmod.com/amd-ryzen-9-5950x-49ghz-socket-am4-boxed-procesador',
                'Neobyte' => 'https://www.neobyte.es/procesador-amd-ryzen-9-5950x-socket-am4-7700.html',
            ],
            'AMD Ryzen 5 5600G' => [
                'Coolmod' => 'https://www.coolmod.com/amd-ryzen-7-5600g-4-40ghz-socket-am4-boxed-6-core-sktchi',
                'Neobyte' => 'https://www.neobyte.es/procesador-amd-ryzen-5-5600g-socket-am4-9942.html',
            ],
            'AMD Ryzen 7 5800X3D' => [
                'Coolmod' => 'https://www.coolmod.com/amd-ryzen-7-5800x3d-4-5ghz-socket-am4-boxed-10th-aniversary',
                'Neobyte' => 'https://www.neobyte.es/amd-ryzen-7-5800x3d-edicion-10-aniversario-procesador-am4-38263.html',
            ],
            'AMD Ryzen 5 7600X' => [
                'Coolmod' => 'https://www.coolmod.com/amd-ryzen-5-7600x-5-3ghz-socket-am5-boxed',
                'Neobyte' => 'https://www.neobyte.es/amd-ryzen-5-7600x-procesador-am5-15297.html',
            ],
            'AMD Ryzen 5 7600' => [
                'Coolmod' => 'https://www.coolmod.com/amd-ryzen-5-7600-5-1ghz-socket-am5-boxed',
                'Neobyte' => 'https://www.neobyte.es/amd-ryzen-5-7600-procesador-am5-16669.html',
            ],
            'AMD Ryzen 7 7700X' => [
                'Coolmod' => 'https://www.coolmod.com/amd-ryzen-7-7700x-5-4ghz-socket-am5-boxed',
                'Neobyte' => 'https://www.neobyte.es/amd-ryzen-7-7700x-procesador-am5-15298.html',
            ],
            'AMD Ryzen 7 7700' => [
                'Coolmod' => 'https://www.coolmod.com/amd-ryzen-7-7700-5-3ghz-socket-am5-boxed',
                'Neobyte' => 'https://www.neobyte.es/amd-ryzen-7-7700-procesador-am5-16670.html',
            ],
            'AMD Ryzen 9 7900X' => [
                'Coolmod' => 'https://www.coolmod.com/amd-ryzen-9-7900x-5-6ghz-socket-am5-boxed',
                'Neobyte' => 'https://www.neobyte.es/amd-ryzen-9-7900x-procesador-am5-15299.html',
            ],
            'AMD Ryzen 7 7800X3D' => [
                'Coolmod' => 'https://www.coolmod.com/amd-ryzen-7-7800x-3d-5-0ghz-socket-am5-boxed',
                'Neobyte' => 'https://www.neobyte.es/amd-ryzen-7-7800x3d-procesador-am5-17301.html',
            ],
            'AMD Ryzen 5 8600G' => [
                'Coolmod' => 'https://www.coolmod.com/amd-ryzen-5-8600g-5-0ghz-socket-am5-boxed',
                'Neobyte' => 'https://www.neobyte.es/amd-ryzen-5-8600g-procesador-am5-20605.html',
            ],
            'AMD Ryzen 5 9600X' => [
                'Coolmod' => 'https://www.coolmod.com/amd-ryzen-5-9600x-5-4-ghz-socket-am5',
                'Neobyte' => 'https://www.neobyte.es/amd-ryzen-5-9600x-procesador-am5-22173.html',
            ],
            'AMD Ryzen 7 9700X' => [
                'Coolmod' => 'https://www.coolmod.com/amd-ryzen-7-9700x-5-5-ghz-socket-am5',
                'Neobyte' => 'https://www.neobyte.es/amd-ryzen-7-9700x-procesador-am5-22171.html',
            ],
            'AMD Ryzen 9 9900X' => [
                'Coolmod' => 'https://www.coolmod.com/amd-ryzen-9-9900x-5-6-ghz-socket-am5',
                'Neobyte' => 'https://www.neobyte.es/amd-ryzen-9-9900x-procesador-am5-22167.html',
            ],
            'AMD Ryzen 9 9950X' => [
                'Coolmod' => 'https://www.coolmod.com/amd-ryzen-9-9950x-5-7-ghz-socket-am5',
                'Neobyte' => 'https://www.neobyte.es/amd-ryzen-9-9950x-procesador-am5-22166.html',
            ],
            'AMD Ryzen 7 9800X3D' => [
                'Coolmod' => 'https://www.coolmod.com/amd-ryzen-7-9800x3d-5-2ghz-socket-am5-boxed',
                'Neobyte' => 'https://www.neobyte.es/amd-ryzen-7-9800x3d-procesador-am5-25474.html',
            ],
            'AMD Ryzen 9 9900X3D' => [
                'Coolmod' => 'https://www.coolmod.com/amd-ryzen-9-9900x3d-5-5ghz-socket-am5-boxed',
                'Neobyte' => 'https://www.neobyte.es/amd-ryzen-9-9900x3d-procesador-am5-27719.html',
            ],
            'AMD Ryzen 9 9950X3D' => [
                'Coolmod' => 'https://www.coolmod.com/amd-ryzen-9-9950x3d-5-7ghz-socket-am5-boxed',
                'Neobyte' => 'https://www.neobyte.es/amd-ryzen-9-9950x3d-procesador-am5-27720.html',
            ],
            'AMD Ryzen 5 5500' => [
                'Coolmod' => 'https://www.coolmod.com/amd-ryzen-5-5500-4-2ghz-socket-am4-boxed',
                'Neobyte' => 'https://www.neobyte.es/amd-ryzen-5-5500-procesador-am4-13306.html',
            ],

            // ---------- CPU Intel ----------
            'Intel Core i5-12600K' => [
                'Coolmod' => 'https://www.coolmod.com/intel-core-i5-12600k-4-90ghz-socket-1700-boxed-procesador',
                'Neobyte' => 'https://www.neobyte.es/intel-core-i5-12600k-procesador-1700-11137.html',
            ],
            'Intel Core i7-12700K' => [
                'Coolmod' => 'https://www.coolmod.com/intel-core-i7-12700k-5-00ghz-socket-1700-boxed-procesador',
                'Neobyte' => 'https://www.neobyte.es/intel-core-i7-12700k-procesador-1700-11135.html',
            ],
            'Intel Core i9-12900K' => [
                'Coolmod' => 'https://www.coolmod.com/intel-core-i9-12900k-5-10ghz-socket-1700-boxed-procesador',
                'Neobyte' => 'https://www.neobyte.es/intel-core-i9-12900k-procesador-1700-11132.html',
            ],
            'Intel Core i5-13400F' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/intel-core-i5-13400f-procesador-1700-16547.html',
            ],
            'Intel Core i9-13900KS' => [
                'Coolmod' => 'https://www.coolmod.com/intel-core-i9-13900ks-6-0ghz-socket-1700-boxed',
                'Neobyte' => 'https://www.neobyte.es/intel-core-i9-13900ks-procesador-1700-17488.html',
            ],
            'Intel Core i5-14600K' => [
                'Coolmod' => 'https://www.coolmod.com/intel-core-i5-14600k-5-3ghz-socket-1700-boxed',
                'Neobyte' => 'https://www.neobyte.es/intel-core-i5-14600k-procesador-1700-19505.html',
            ],
            'Intel Core i7-14700K' => [
                'Coolmod' => 'https://www.coolmod.com/intel-core-i7-14700k-5-6ghz-socket-1700-boxed/',
                'Neobyte' => 'https://www.neobyte.es/intel-core-i7-14700k-procesador-1700-19503.html',
            ],
            'Intel Core i9-14900K' => [
                'Coolmod' => 'https://www.coolmod.com/intel-core-i9-14900k-6-0ghz-socket-1700-boxed',
                'Neobyte' => 'https://www.neobyte.es/intel-core-i9-14900k-procesador-1700-19501.html',
            ],
            'Intel Core i5-14400F' => [
                'Coolmod' => 'https://www.coolmod.com/intel-core-i5-14400f-4-7ghz-socket-1700-boxed',
                'Neobyte' => 'https://www.neobyte.es/intel-core-i5-14400f-procesador-1700-20220.html',
            ],
            'Intel Core i9-14900KS' => [
                'Coolmod' => 'https://www.coolmod.com/intel-core-i9-14900ks-6-2ghz-socket-1700-boxed',
                'Neobyte' => '',
            ],
            'Intel Core Ultra 5 245K' => [
                'Coolmod' => 'https://www.coolmod.com/intel-core-ultra-5-245k-5-2ghz-socket-1851-boxed',
                'Neobyte' => 'https://www.neobyte.es/intel-core-ultra-5-245k-procesador-1851-24400.html',
            ],
            'Intel Core Ultra 7 265K' => [
                'Coolmod' => 'https://www.coolmod.com/intel-core-ultra-7-265k-5-5ghz-socket-1851-boxed',
                'Neobyte' => 'https://www.neobyte.es/intel-core-ultra-7-265k-procesador-1851-24398.html',
            ],
            'Intel Core Ultra 9 285K' => [
                'Coolmod' => 'https://www.coolmod.com/intel-core-ultra-9-285k-5-7ghz-socket-1851-boxed',
                'Neobyte' => 'https://www.neobyte.es/intel-core-ultra-9-285k-procesador-1851-24397.html',
            ],
            'Intel Core i5-12400F' => [
                'Coolmod' => 'https://www.coolmod.com/intel-core-i5-12400f-4-4ghz-socket-1700-boxed-procesador',
                'Neobyte' => 'https://www.neobyte.es/intel-core-i5-12400f-procesador-1700-11980.html',
            ],
            'Intel Core i7-12700' => [
                'Coolmod' => 'https://www.coolmod.com/intel-core-i7-12700-4-9ghz-socket-1700-boxed-procesador',
                'Neobyte' => 'https://www.neobyte.es/intel-core-i7-12700-procesador-1700-11892.html',
            ],
            'Intel Core i7-13700F' => [
                'Coolmod' => 'https://www.coolmod.com/intel-core-i7-13700f-5-2ghz-socket-1700-boxed',
                'Neobyte' => '',
            ],
            'Intel Core i5-14600KF' => [
                'Coolmod' => 'https://www.coolmod.com/intel-core-i5-14600kf-5-3ghz-socket-1700-boxed',
                'Neobyte' => 'https://www.neobyte.es/intel-core-i5-14600kf-procesador-1700-19506.html',
            ],
            'Intel Core i7-14700F' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/intel-core-i7-14700f-procesador-1700-20223.html',
            ],
            'Intel Core Ultra 5 245KF' => [
                'Coolmod' => 'https://www.coolmod.com/intel-core-ultra-5-245kf-5-2ghz-socket-1851-boxed',
                'Neobyte' => 'https://www.neobyte.es/intel-core-ultra-5-245kf-procesador-1851-24401.html',
            ],

            // ---------- Placas base AM4 ----------
            'ASUS ROG Strix B550-F Gaming' => [
                'Coolmod' => 'https://www.coolmod.com/asus-rog-strix-b550-f-gaming-socket-am4-placa-base',
                'Neobyte' => 'https://www.neobyte.es/placa-base-asus-rog-strix-b550-f-gaming-6609.html',
            ],
            'Gigabyte X570 Aorus Master' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/placa-base-gigabyte-x570-aorus-master-am4-4349.html',
            ],
            'MSI B550M Pro-VDH WiFi' => [
                'Coolmod' => 'https://www.coolmod.com/msi-b550m-pro-vdh-wifi-socket-am4-placa-base',
                'Neobyte' => 'https://www.neobyte.es/placa-base-msi-b550m-pro-vdh-wifi-7001.html',
            ],
            'MSI MPG B550I Gaming Edge WiFi' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/placa-base-msi-am4-b550i-gaming-edge-wifi-8444.html',
            ],
            'ASRock B550 Phantom Gaming 4' => [
                'Coolmod' => 'https://www.coolmod.com/asrock-b550-phantom-gaming-4-socket-am4-placa-base',
                'Neobyte' => 'https://www.neobyte.es/asrock-b550-phantom-gaming-4-placa-base-am4-atx-25939.html',
            ],
            'ASUS TUF Gaming B550M-Plus WiFi II' => [
                'Coolmod' => 'https://www.coolmod.com/asus-tuf-gaming-b550m-plus-wifi-ii-socket-am4',
                'Neobyte' => 'https://www.neobyte.es/asus-tuf-gaming-b550m-plus-wifi-ii-amd-placa-base-am4-micro-atx-11617.html',
            ],

            // ---------- Placas base AM5 ----------
            'ASUS TUF Gaming B650-Plus WiFi' => [
                'Coolmod' => 'https://www.coolmod.com/asus-tuf-gaming-b650-plus-wifi-socket-am5',
                'Neobyte' => '',
            ],
            'Gigabyte B650 Aorus Elite AX' => [
                'Coolmod' => 'https://www.coolmod.com/gigabyte-b650-aorus-elite-ax-ice-socket-am5',
                'Neobyte' => '',
            ],
            'ASUS ROG Crosshair X670E Hero' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/asus-rog-crosshair-x670e-hero-placa-base-amd-am5-15303.html',
            ],
            'MSI MEG X670E Ace' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/msi-meg-x670e-ace-placa-base-am5-e-atx-18693.html',
            ],
            'Gigabyte X670E Aorus Master' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/gigabyte-x670e-aorus-master-placa-base-am5-e-atx-15340.html',
            ],
            'MSI PRO B650M-A WiFi' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/msi-pro-b650m-a-wifi-placa-base-am5-micro-atx-15379.html',
            ],
            'Gigabyte B650M Aorus Elite AX' => [
                'Coolmod' => 'https://www.coolmod.com/gigabyte-b650m-aorus-elite-ax-ice-socket-am5',
                'Neobyte' => '',
            ],
            'ASUS ROG Strix B650E-I Gaming WiFi' => [
                'Coolmod' => 'https://www.coolmod.com/asus-rog-strix-b650e-i-gaming-wifi-socket-am5',
                'Neobyte' => 'https://www.neobyte.es/asus-rog-strix-b650e-i-gaming-wifi-placa-base-am5-mini-itx-15607.html',
            ],
            'ASUS ProArt X670E-Creator WiFi' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/asus-proart-x670e-creator-wi-fi-placa-base-amd-15313.html',
            ],
            'MSI MAG X870 Tomahawk WiFi' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/msi-mag-x870-tomahawk-wifi-placa-base-atx-am5-23975.html',
            ],
            'Gigabyte X870E Aorus Master' => [
                'Coolmod' => 'https://www.coolmod.com/gigabyte-x870e-aorus-master-x3d-ice-socket-am5',
                'Neobyte' => 'https://www.neobyte.es/gigabyte-x870e-aorus-elite-x3d-placa-base-am5-atx-32163.html',
            ],

            // ---------- Placas base LGA1700 ----------
            'ASUS ROG Maximus Z690 Hero' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/asus-rog-maximus-z690-hero-placa-base-1700-atx-11085.html',
            ],
            'Gigabyte Z690 Aorus Pro DDR4' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/gigabyte-z690-aorus-pro-placa-base-1700-atx-wifi-11105.html',
            ],
            'ASUS ROG Strix Z790-E Gaming WiFi II' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/asus-rog-strix-z790e-gaming-wifi-ii-placa-base-atx-19541.html',
            ],
            'MSI MAG Z790 Tomahawk WiFi DDR4' => [
                'Coolmod' => 'https://www.coolmod.com/msi-mag-z790-tomahawk-wifi-socket-1700-1667899500',
                'Neobyte' => 'https://www.neobyte.es/msi-mag-z790-tomahawk-wifi-placa-base-1700-atx-16654.html',
            ],
            'Gigabyte Z790 Aorus Elite AX' => [
                'Coolmod' => 'https://www.coolmod.com/gigabyte-z790-aorus-elite-ax-socket-1700',
                'Neobyte' => '',
            ],
            'ASRock B760M Pro RS' => [
                'Coolmod' => 'https://www.coolmod.com/asrock-b760m-pro-rs-socket-1700',
                'Neobyte' => 'https://www.neobyte.es/asrock-b760m-pro-rs-placa-base-1700-microatx-27209.html',
            ],
            'ASUS ROG Strix Z690-I Gaming WiFi' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/asus-rog-strix-z690-i-gaming-wifi-placa-base-1700-mini-itx-11140.html',
            ],
            'MSI MPG Z790I Edge WiFi' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/msi-z790i-edge-wifi-placa-base-1700-mini-itx-23628.html',
            ],
            'ASUS TUF Gaming Z790-Plus WiFi' => [
                'Coolmod' => 'https://www.coolmod.com/asus-tuf-gaming-z790-plus-wifi-socket-1700',
                'Neobyte' => '',
            ],

            // ---------- Placas base LGA1851 ----------
            'ASUS ROG Maximus Z890 Apex' => [
                'Coolmod' => 'https://www.coolmod.com/asus-rog-maximus-z890-apex-wifi-socket-1851',
                'Neobyte' => 'https://www.neobyte.es/asus-rog-maximus-z890-apex-placa-base-1851-atx-24342.html',
            ],
            'MSI MEG Z890 Ace' => [
                'Coolmod' => 'https://www.coolmod.com/msi-meg-z890-ace-wifi-socket-1851',
                'Neobyte' => '',
            ],
            'Gigabyte Z890 Aorus Master' => [
                'Coolmod' => 'https://www.coolmod.com/gigabyte-z890-aorus-master-wifi7-socket-1851',
                'Neobyte' => 'https://www.neobyte.es/gigabyte-z890-aorus-master-placa-base-1851-atx-24416.html',
            ],
            'ASUS TUF Gaming Z890-Plus WiFi' => [
                'Coolmod' => 'https://www.coolmod.com/asus-tuf-gaming-z890-plus-wifi-socket-1851',
                'Neobyte' => 'https://www.neobyte.es/asus-tuf-gaming-z890-plus-wifi-placa-base-1851-atx-24349.html',
            ],
            'MSI MAG Z890 Tomahawk WiFi' => [
                'Coolmod' => 'https://www.coolmod.com/msi-mag-z890-tomahawk-wifi-socket-1851',
                'Neobyte' => 'https://www.neobyte.es/msi-mag-z890-tomahawk-wifi-placa-base-1851-atx-24663.html',
            ],
            'ASRock Z890 Taichi' => [
                'Coolmod' => 'https://www.coolmod.com/asrock-z890-taichi-socket-1851',
                'Neobyte' => '',
            ],
            'MSI PRO B860M-A WiFi' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/msi-pro-b860m-a-wifi-placa-base-1851-micro-atx-26643.html',
            ],
            'Gigabyte B860M Aorus Elite WiFi' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/gigabyte-b860m-aorus-elite-wifi6e-placa-base-1851-micro-atx-26487.html',
            ],
            'ASRock B860M Pro RS WiFi' => [
                'Coolmod' => 'https://www.coolmod.com/asrock-b860m-pro-rs-wifi-socket-1851',
                'Neobyte' => '',
            ],
            'MSI MPG Z890I Edge WiFi' => [
                'Coolmod' => 'https://www.coolmod.com/msi-mpg-z890i-edge-ti-wifi-socket-1851',
                'Neobyte' => '',
            ],
            'Gigabyte Z890I Aorus Ultra WiFi7' => [
                'Coolmod' => 'https://www.coolmod.com/gigabyte-z890i-aorus-ultra-socket-1851',
                'Neobyte' => '',
            ],

            // ---------- RAM DDR4 ----------
            'Corsair Vengeance LPX 16GB DDR4-3200 CL16' => [
                'Coolmod' => 'https://www.coolmod.com/corsair-vengance-lpx-16gb-2x8gb-3200mhz-pc4-25600-cl16-memoria-ddr4',
                'Neobyte' => 'https://www.neobyte.es/memoria-corsair-16gb-ddr4-3200-vengeance-lpx-4647.html',
            ],
            'Corsair Vengeance LPX 128GB DDR4-3200 CL16' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/corsair-vengeance-lpx-128gb-4x32gb-ddr4-3200mhz-cl16-memoria-ram-28809.html',
            ],
            'G.Skill Trident Z RGB 16GB DDR4-3600 CL16' => [
                'Coolmod' => 'https://www.coolmod.com/gskill-trident-z-neo-16gb-2x8gb-3600mhz-pc4-28800-cl16-memoria-ddr4',
                'Neobyte' => '',
            ],
            'G.Skill Trident Z RGB 32GB DDR4-3600 CL16' => [
                'Coolmod' => 'https://www.coolmod.com/gskill-trident-z-rgb-32gb-2x16gb-3200-mhz-pc4-25600-cl16-led-rgb-memoria-ddr4',
                'Neobyte' => 'https://www.neobyte.es/gskill-trident-z-rgb-32gb-2x16gb-ddr4-3200mhz-cl16-memoria-ram-34939.html',
            ],
            'Kingston Fury Beast 16GB DDR4-3200 CL16' => [
                'Coolmod' => 'https://www.coolmod.com/kingston-fury-beast-rgb-16gb-2x8gb-3200mhz-cl16-xmp',
                'Neobyte' => 'https://www.neobyte.es/kingston-fury-beast-rgb-16gb-2x8gb-ddr4-3200mhz-cl16-memoria-ram-20403.html',
            ],
            'Kingston Fury Beast RGB 32GB DDR4-3600 CL18' => [
                'Coolmod' => 'https://www.coolmod.com/kingston-fury-beast-rgb-32gb-2x16gb-3600mhz-cl18-xmp',
                'Neobyte' => 'https://www.neobyte.es/kingston-fury-beast-rgb-32gb-2x16gb-ddr4-3600mhz-cl18-memoria-ram-20016.html',
            ],
            'TeamGroup T-Force Vulcan Z 16GB DDR4-3200 CL16' => [
                'Coolmod' => 'https://www.coolmod.com/team-group-vulcan-z-16gb-1x16gb-3200mhz-cl16-gris/',
                'Neobyte' => '',
            ],

            // ---------- RAM DDR5 ----------
            'G.Skill Trident Z5 RGB 32GB DDR5-6000 CL30' => [
                'Coolmod' => 'https://www.coolmod.com/g-skill-trident-z5-rgb-32gb-2x16gb-6000mhz-cl30',
                'Neobyte' => '',
            ],
            'G.Skill Trident Z5 RGB 64GB DDR5-6000 CL30' => [
                'Coolmod' => 'https://www.coolmod.com/g-skill-trident-z5-neo-rgb-64gb-2x32gb-6000mhz-cl30-expo',
                'Neobyte' => '',
            ],
            'Kingston Fury Beast DDR5 32GB 5200 CL40' => [
                'Coolmod' => 'https://www.coolmod.com/kingston-fury-2x16gb-5200-mhz-pc5-41600-cl40-memoria-ddr5',
                'Neobyte' => 'https://www.neobyte.es/kingston-fury-beast-32gb-2x16gb-ddr5-5200mhz-cl40-memoria-ram-11531.html',
            ],
            'Crucial Pro DDR5 64GB 5600 CL46' => [
                'Coolmod' => 'https://www.coolmod.com/crucial-pro-64gb-2x32gb-5600mhz-cl46-expo-xmp-negro',
                'Neobyte' => '',
            ],
            'TeamGroup T-Force Delta RGB DDR5 32GB 6000 CL30' => [
                'Coolmod' => 'https://www.coolmod.com/team-group-delta-rgb-32gb-2x16gb-6000mhz-cl30-xmp',
                'Neobyte' => 'https://www.neobyte.es/teamgroup-t-force-vulcan-32gb-2x16gb-ddr5-6000mhz-cl30-rgb-memoria-ram-39613.html',
            ],
            'XPG Lancer RGB DDR5 32GB 6000 CL30' => [
                'Coolmod' => 'https://www.coolmod.com/adata-xpg-lancer-blade-32gb-2x16gb-6000mhz-cl30-xmp-negro',
                'Neobyte' => 'https://www.neobyte.es/xpg-lancer-32gb-2x16gb-ddr5-6000mhz-cl30-memoria-ram-24759.html',
            ],
            'G.Skill Trident Z5 Neo RGB 32GB DDR5-6000 CL30' => [
                'Coolmod' => 'https://www.coolmod.com/g-skill-trident-z5-neo-rgb-1x32gb-6000mhz-cl30-expo',
                'Neobyte' => '',
            ],
            'Kingston Fury Beast DDR5 64GB 5200 CL40' => [
                'Coolmod' => 'https://www.coolmod.com/kingston-fury-beast-64gb-2x32gb-5200mhz-cl40-xmp-3-0',
                'Neobyte' => '',
            ],

            // ---------- GPU ----------
            'MSI GeForce RTX 3060 VENTUS 2X 12GB OC' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/msi-rtx-3060-ventus-oc-12gb-lhr-tarjeta-grafica-13252.html',
            ],
            'ASUS Dual GeForce RTX 3060 Ti OC 8GB' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/asus-dual-rtx-3060-ti-oc-edition-8gb-gddr6x-tarjeta-grafica-16514.html',
            ],
            'Gigabyte GeForce RTX 3070 EAGLE OC 8GB' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/gigabyte-rtx-3070-eagle-oc-8gb-tarjeta-grafica-10348.html',
            ],
            'Zotac Gaming GeForce RTX 3060 Twin Edge OC (SFF)' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/zotac-gaming-geforce-rtx-3060-twin-edge-12gb-gddr6-tarjeta-grafica-38389.html',
            ],
            'MSI GeForce RTX 4060 VENTUS 2X BLACK OC 8GB' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/msi-rtx-4060-ventus-2x-black-8gb-oc-dlss3-tarjeta-grafica-18601.html',
            ],
            'Zotac Gaming GeForce RTX 4060 Twin Edge OC 8GB (SFF)' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/zotac-gaming-rtx-4060-twin-edge-8gb-gddr6-dlss3-tarjeta-grafica-20635.html',
            ],
            'ASUS Dual GeForce RTX 4060 Ti OC 8GB' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/asus-dual-rtx-4060-ti-oc-16gb-gddr6-tarjeta-grafica-18732.html',
            ],
            'Gigabyte GeForce RTX 4060 Ti GAMING OC 16GB' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/gigabyte-rtx-4060-ti-gaming-oc-16gb-gddr6-dlss3-tarjeta-grafica-18741.html',
            ],
            'ASUS ROG Strix GeForce RTX 4070 Super OC 12GB' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/asus-rog-strix-rtx-4070-super-12gb-gddr6x-dlss3-tarjeta-grafica-20182.html',
            ],
            'Gigabyte GeForce RTX 4070 Ti Super AORUS MASTER 16GB' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/gigabyte-aorus-rtx-4070-ti-super-master-16gb-gddr6x-dlss3-tarjeta-grafica-20240.html',
            ],
            'ASUS Dual GeForce RTX 5060 Ti OC 16GB' => [
                'Coolmod' => 'https://www.coolmod.com/asus-dual-geforce-rtx-5060-ti-oc-16gb-gddr7-dlss4',
                'Neobyte' => 'https://www.neobyte.es/asus-dual-rtx-5060-ti-oc-8gb-gddr7-dlss4-tarjeta-grafica-28548.html',
            ],
            'MSI GeForce RTX 5070 GAMING TRIO OC 12GB' => [
                'Coolmod' => 'https://www.coolmod.com/msi-geforce-rtx-5070-gaming-trio-oc-12gb-gddr7-dlss4',
                'Neobyte' => 'https://www.neobyte.es/msi-geforce-rtx-5070-gaming-trio-oc-12gb-gddr7-dlss4-tarjeta-grafica-27798.html',
            ],
            'Gigabyte GeForce RTX 5070 Ti AORUS MASTER 16GB' => [
                'Coolmod' => 'https://www.coolmod.com/gigabyte-aorus-geforce-rtx-5070-ti-master-16gb-gddr7',
                'Neobyte' => 'https://www.neobyte.es/gigabyte-aorus-rtx-5070-ti-master-16gb-gddr7-dlss4-tarjeta-grafica-26521.html',
            ],
            'ASUS ROG Astral GeForce RTX 5080 OC 16GB' => [
                'Coolmod' => 'https://www.coolmod.com/asus-rog-astral-geforce-rtx-5080-oc-gaming-16gb-gddr7',
                'Neobyte' => '',
            ],
            'INNO3D GeForce RTX 5060 TWIN X2 OC 8GB (SFF)' => [
                'Coolmod' => 'https://www.coolmod.com/inno3d-geforce-rtx-5060-twin-x2-oc-v2-8gb-gddr7-dlss4',
                'Neobyte' => 'https://www.neobyte.es/inno3d-geforce-rtx-5060-twin-x2-oc-8gb-gddr7-dlss4-tarjeta-grafica-37501.html',
            ],
            'Sapphire Pulse Radeon RX 7600 8GB' => [
                'Coolmod' => 'https://www.coolmod.com/sapphire-pulse-radeon-rx-7600-gaming-oc-8gb-gddr6',
                'Neobyte' => '',
            ],
            'PowerColor Red Devil Radeon RX 7800 XT 16GB' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/powercolor-radeon-rx-7800xt-red-devil-16gb-gddr6-tarjeta-grafica-19029.html',
            ],
            'Sapphire Pulse Radeon RX 9070 16GB' => [
                'Coolmod' => 'https://www.coolmod.com/sapphire-pulse-amd-radeon-rx-9070-16gb-gddr6',
                'Neobyte' => '',
            ],
            'PowerColor Red Devil Radeon RX 9070 XT 16GB' => [
                'Coolmod' => 'https://www.coolmod.com/powercolor-red-devil-amd-radeon-rx-9070-xt-oc-16gb-gddr6',
                'Neobyte' => '',
            ],
            'Samsung 990 Pro 2TB NVMe PCIe 4.0' => [
                'Coolmod' => 'https://www.coolmod.com/samsung-990-pro-2tb-pcie-x4-nvme',
                'Neobyte' => 'https://www.neobyte.es/samsung-990-pro-2tb-ssd-m2-pci-express-40-16306.html',
            ],
            'Western Digital Black SN850X 1TB NVMe PCIe 4.0' => [
                'Coolmod' => 'https://www.coolmod.com/western-digital-black-sn850x-1tb-nvme-pcie-gen4',
                'Neobyte' => 'https://www.neobyte.es/wd-black-sn850x-1tb-unidad-ssd-m2-16138.html',
            ],
            'Crucial P5 Plus 2TB NVMe PCIe 4.0' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/crucial-p5-plus-2tb-disco-ssd-nvme-pcie-4-0-15246.html',
            ],
            'Lexar NM790 4TB NVMe PCIe 4.0' => [
                'Coolmod' => 'https://www.coolmod.com/lexar-nm790-4tb-pcie-gen4-x4-nvme-ssd',
                'Neobyte' => 'https://www.neobyte.es/lexar-nm790-4tb-unidad-ssd-m2-21879.html',
            ],
            'ADATA XPG Gammix S70 Blade 1TB NVMe PCIe 4.0' => [
                'Coolmod' => 'https://www.coolmod.com/adata-xpg-gammix-s70-blade-1tb-gen4-pcie-x4-nvme',
                'Neobyte' => 'https://www.neobyte.es/xpg-gammix-s70-blade-1tb-pcie-40-unidad-ssd-m2-24741.html',
            ],
            'Samsung 9100 Pro 2TB NVMe PCIe 5.0' => [
                'Coolmod' => 'https://www.coolmod.com/samsung-9100-pro-2tb-pcie-gen5-x4-nvme-2-0-ssd',
                'Neobyte' => 'https://www.neobyte.es/samsung-9100-pro-2tb-pcie-50-unidad-ssd-m2-27955.html',
            ],
            'Crucial T705 2TB NVMe PCIe 5.0' => [
                'Coolmod' => 'https://www.coolmod.com/crucial-t705-2tb-pcie-gen5-x4-nvme-ssd',
                'Neobyte' => '',
            ],
            'Western Digital Black SN850X 4TB NVMe PCIe 5.0' => [
                'Coolmod' => 'https://www.coolmod.com/western-digital-black-sn850x-4tb-nvme-pcie-gen4',
                'Neobyte' => 'https://www.neobyte.es/western-digital-black-sn850x-4tb-ssd-m2-nvme-16393.html',
            ],
            'Samsung 870 EVO 1TB SATA SSD' => [
                'Coolmod' => 'https://www.coolmod.com/samsung-870-evo-ssd-25-1tb-sata3-disco-duro-ssd',
                'Neobyte' => 'https://www.neobyte.es/disco-ssd-samsung-1tb-870-evo-sata3-mz-77e1t0beu-8241.html',
            ],
            'Kingston A400 480GB SATA SSD' => [
                'Coolmod' => 'https://www.coolmod.com/kingston-ssdnow-a400-480gb-25-sata3-disco-ssd',
                'Neobyte' => 'https://www.neobyte.es/disco-ssd-kingston-480gb-a400-sa400s37-480g-1709.html',
            ],
            'Seagate Barracuda 4TB HDD 3.5"' => [
                'Coolmod' => 'https://www.coolmod.com/seagate-barracuda-compute-4tb-35-disco-duro',
                'Neobyte' => 'https://www.neobyte.es/seagate-barracuda-4tb-disco-duro-35-sata-174.html',
            ],
            'Western Digital Red Plus 8TB HDD 3.5"' => [
                'Coolmod' => 'https://www.coolmod.com/western-digital-nas-plus-wd80efpx-8tb-3-5-sata3',
                'Neobyte' => 'https://www.neobyte.es/western-digital-red-pro-8tb-disco-duro-35-sata-22569.html',
            ],

            // ---------- Gabinetes ----------
            'Cooler Master NR200P' => [
                'Coolmod' => 'https://www.coolmod.com/cooler-master-nr200p-cristal-templado-negro-caja-torre',
                'Neobyte' => 'https://www.neobyte.es/cooler-master-masterbox-nr200p-negro-caja-mini-itx-8616.html',
            ],
            'Fractal Design North' => [
                'Coolmod' => 'https://www.coolmod.com/fractal-design-north-negro',
                'Neobyte' => 'https://www.neobyte.es/fractal-design-north-charcoal-black-caja-atx-18432.html',
            ],
            'Corsair 4000D' => [
                'Coolmod' => 'https://www.coolmod.com/corsair-frame-4000d-blanco',
                'Neobyte' => 'https://www.neobyte.es/corsair-frame-4000d-rs-blanca-caja-e-atx-29441.html',
            ],
            'Lian Li Lancool III' => [
                'Coolmod' => 'https://www.coolmod.com/lian-li-lancool-iii-argb-negro',
                'Neobyte' => 'https://www.neobyte.es/lian-li-lancool-iii-rgb-caja-eatx-17763.html',
            ],
            'DeepCool CH560 Digital' => [
                'Coolmod' => 'https://www.coolmod.com/deepcool-ch560-digital-mesh-blanco',
                'Neobyte' => 'https://www.neobyte.es/deepcool-ch560-digital-wh-blanca-caja-eatx-19491.html',
            ],
            'Lian Li PC-O11 Vision' => [
                'Coolmod' => 'https://www.coolmod.com/lian-li-011-vision-compacta-negro',
                'Neobyte' => 'https://www.neobyte.es/lian-li-o11-vision-compact-caja-eatx-25547.html',
            ],
            'Corsair 5000X RGB' => [
                'Coolmod' => 'https://www.coolmod.com/corsair-icue-5000x-rgb-white-cristal-templado-caja-torre',
                'Neobyte' => 'https://www.neobyte.es/corsair-icue-5000x-smart-blanca-caja-atx-rgb-7949.html',
            ],
            'Jonsbo D31 Mesh' => [
                'Coolmod' => 'https://www.coolmod.com/jonsbo-d31-mesh-negro',
                'Neobyte' => '',
            ],
            'ASUS Prime AP201' => [
                'Coolmod' => 'https://www.coolmod.com/asus-prime-ap201-cristal-templado-negro',
                'Neobyte' => 'https://www.neobyte.es/asus-prime-ap201-tempered-glass-caja-microatx-17385.html',
            ],
            'Antec Performance 1M' => [
                'Coolmod' => 'https://www.coolmod.com/antec-performance-1m-aventurine',
                'Neobyte' => 'https://www.neobyte.es/antec-performance-1-m-aventurine-caja-mini-itx-26830.html',
            ],
            'Jonsbo Z20' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/jonsbo-z20-blanca-caja-micro-atx-37363.html',
            ],
            'Fractal Design Pop Mini' => [
                'Coolmod' => 'https://www.coolmod.com/fractal-design-pop-mini-air-argb-blanco',
                'Neobyte' => 'https://www.neobyte.es/fractal-design-pop-mini-air-rgb-white-tg-clear-tint-caja-micro-atx-19023.html',
            ],
            'NZXT H5 Flow' => [
                'Coolmod' => 'https://www.coolmod.com/nzxt-h5-flow-2024-negro',
                'Neobyte' => 'https://www.neobyte.es/nzxt-h5-flow-2024-caja-e-atx-24719.html',
            ],
            'Lian Li PC-O11 Air Mini' => [
                'Coolmod' => 'https://www.coolmod.com/lian-li-o11-mini-air-negro',
                'Neobyte' => 'https://www.neobyte.es/lian-li-o11-air-mini-negra-caja-eatx-17143.html',
            ],
            'Cooler Master NR200P V2' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/cooler-master-masterbox-nr200p-v2-caja-mini-itx-26195.html',
            ],
            'Fractal Design Terra' => [
                'Coolmod' => 'https://www.coolmod.com/fractal-design-terra-negro',
                'Neobyte' => 'https://www.neobyte.es/fractal-design-terra-graphite-caja-mini-itx-21222.html',
            ],
            'Lian Li DAN A3-mATX' => [
                'Coolmod' => 'https://www.coolmod.com/lian-li-a3-matx-negro',
                'Neobyte' => 'https://www.neobyte.es/lian-li-a3-dan-caja-micro-atx-23836.html',
            ],
            'Jonsbo D31 STD' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/jonsbo-d31-std-caja-micro-atx-37218.html',
            ],
            'Lian Li A4-H2O' => [
                'Coolmod' => 'https://www.coolmod.com/lian-li-a4-h2o-pcie-5-0-negro',
                'Neobyte' => 'https://www.neobyte.es/lian-li-a4h2o-caja-mini-atx-17765.html',
            ],

            // ---------- Fuentes de alimentación (PSU) ----------
            'DeepCool PQ650M' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/fuente-alimentacion-deepcool-pq650m-650w-13365.html',
            ],
            'Seasonic Focus GX-750' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/seasonic-focus-gx-750-atx-3-2024-pcie-50-blanca-fuente-de-alimentacion-750w-24387.html',
            ],
            'EVGA SuperNOVA 850 G6' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/evga-supernova-850-g6-fuente-de-alimentacion-850w-80-gold-14492.html',
            ],
            'MSI MAG A750GL PCIE5' => [
                'Coolmod' => 'https://www.coolmod.com/msi-mag-a750gl-pcie5-ii-80-plus-gold-750w-atx-3-1-pcie-5-1-modular',
                'Neobyte' => 'https://www.neobyte.es/msi-mag-a750gl-ii-atx-30-pcie-51-fuente-de-alimentacion-750w-27007.html',
            ],
            'Corsair RM850x' => [
                'Coolmod' => 'https://www.coolmod.com/corsair-rm850x-shift-cybenetics-gold-850w-atx-3-1-pcie-5-1-modular',
                'Neobyte' => 'https://www.neobyte.es/corsair-rm850x-atx-31-pcie-51-fuente-de-alimentacion-850w-21818.html',
            ],
            'Fractal Design Ion+ 3 850W Platinum' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/fractal-design-ion-3-gold-850w-fuente-de-alimentacion-850w-32155.html',
            ],
            'Gigabyte UD1000GM PG5' => [
                'Coolmod' => 'https://www.coolmod.com/gigabyte-ud1000gm-pg5-80-plus-gold-1000w-atx-3-0-pcie-5-0-modular',
                'Neobyte' => 'https://www.neobyte.es/gigabyte-ud1000gm-pg5-atx-30-pcie-50-rev-2-fuente-de-alimentacion-1000w-16962.html',
            ],
            'Corsair HX1000i' => [
                'Coolmod' => 'https://www.coolmod.com/corsair-hx1000i-2023-80-plus-platinum-1000w-modular',
                'Neobyte' => 'https://www.neobyte.es/corsair-hx1000i-1000w-80-platinum-atx-3-0-pcie-5-0-fuente-de-alimentacion-17594.html',
            ],
            'Thermaltake Toughpower GF3 1200W' => [
                'Coolmod' => 'https://www.coolmod.com/thermaltake-toughpower-gf3-80-plus-gold-1200w-atx-3-0-pcie-5-0-modular',
                'Neobyte' => 'https://www.neobyte.es/thermaltake-toughpower-gf3-atx-30-pcie-50-fuente-de-alimentacion-1200w-20300.html',
            ],
            'Seasonic Prime TX-1300' => [
                'Coolmod' => 'https://www.coolmod.com/seasonic-prime-tx-80-plus-titanium-1300w-atx-3-0-pcie-5-0-modular',
                'Neobyte' => '',
            ],
            'be quiet! Dark Power Pro 13 1600W' => [
                'Coolmod' => 'https://www.coolmod.com/be-quiet-dark-power-pro-13-80-plus-titanium-1600w-atx-3-1-pcie-5-1-modular',
                'Neobyte' => 'https://www.neobyte.es/be-quiet-dark-power-pro-13-fuente-de-alimentacion-1600w-18477.html',
            ],
            'Seasonic Focus SFX-L 650W' => [
                'Coolmod' => 'https://www.coolmod.com/seasonic-focus-spx-80-plus-platinum-650w-modular',
                'Neobyte' => '',
            ],

            // ---------- Refrigeración por aire ----------
            'DeepCool Assassin IV' => [
                'Coolmod' => 'https://www.coolmod.com/deepcool-assassin-iv-negro',
                'Neobyte' => 'https://www.neobyte.es/deepcool-assassin-iv-disipador-cpu-20084.html',
            ],
            'DeepCool AK620 G2' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/deepcool-ak620-g2-refrigeracion-cpu-37700.html',
            ],
            'Thermalright Phantom Spirit 120 EVO' => [
                'Coolmod' => 'https://www.coolmod.com/thermalright-phantom-spirit-120-evo-argb-negro',
                'Neobyte' => 'https://www.neobyte.es/thermalright-phantom-spirit-120-evo-refrigeracion-cpu-36697.html',
            ],
            'Noctua NH-D15' => [
                'Coolmod' => 'https://www.coolmod.com/noctua-nh-d15-disipador-cpu',
                'Neobyte' => 'https://www.neobyte.es/noctua-nh-d15-refrigeracion-cpu-4748.html',
            ],
            'Noctua NH-U12S redux' => [
                'Coolmod' => 'https://www.coolmod.com/noctua-nh-u12s-redux',
                'Neobyte' => 'https://www.neobyte.es/noctua-nh-u12s-redux-refrigeracion-cpu-32271.html',
            ],
            'Noctua NH-D15S' => [
                'Coolmod' => 'https://www.coolmod.com/noctua-nh-d15s',
                'Neobyte' => 'https://www.neobyte.es/noctua-nh-d15s-refrigeracion-cpu-3786.html',
            ],
            'Noctua NH-L12S' => [
                'Coolmod' => 'https://www.coolmod.com/noctua-nh-l12s',
                'Neobyte' => 'https://www.neobyte.es/noctua-nhl12s-ventilador-cpu-multisocket-low-profile-12439.html',
            ],
            'Arctic Freezer 36' => [
                'Coolmod' => 'https://www.coolmod.com/arctic-freezer-36',
                'Neobyte' => 'https://www.neobyte.es/arctic-freezer-36-disipador-cpu-30923.html',
            ],
            'be quiet! Dark Rock Pro 5' => [
                'Coolmod' => 'https://www.coolmod.com/dark-rock-pro-5-negro',
                'Neobyte' => 'https://www.neobyte.es/be-quiet-dark-rock-pro-5-disipador-cpu-22362.html',
            ],
            'Thermalright Peerless Assassin 120 SE' => [
                'Coolmod' => 'https://www.coolmod.com/thermalright-peerless-assassin-120-se-argb-blanco',
                'Neobyte' => '',
            ],
            'Noctua NH-L9i-17xx' => [
                'Coolmod' => 'https://www.coolmod.com/noctua-nh-l9i-17xx',
                'Neobyte' => 'https://www.neobyte.es/noctua-nhl9i17xx-refrigeracion-cpu-12241.html',
            ],

            // ---------- Refrigeración líquida ----------
            'Arctic Liquid Freezer III Pro 240' => [
                'Coolmod' => 'https://www.coolmod.com/arctic-liquid-freezer-iii-pro-240-negro',
                'Neobyte' => 'https://www.neobyte.es/arctic-liquid-freezer-iii-pro-240-refrigeracion-liquida-240mm-33168.html',
            ],
            'NZXT Kraken 240' => [
                'Coolmod' => 'https://www.coolmod.com/nzxt-kraken-elite-240-lcd-display-negro',
                'Neobyte' => 'https://www.neobyte.es/nzxt-kraken-elite-240-refrigeracion-liquida-240mm-26535.html',
            ],
            'DeepCool LT240' => [
                'Coolmod' => 'https://www.coolmod.com/deepcool-lt240-argb-240mm-negro',
                'Neobyte' => 'https://www.neobyte.es/deepcool-lt240-argb-refrigeracion-liquida-240mm-25308.html',
            ],
            'Arctic Liquid Freezer III Pro 280' => [
                'Coolmod' => 'https://www.coolmod.com/arctic-liquid-freezer-iii-pro-280-negro',
                'Neobyte' => 'https://www.neobyte.es/arctic-liquid-freezer-iii-pro-280-black-refrigeracion-liquida-280mm-34754.html',
            ],
            'NZXT Kraken Elite 280' => [
                'Coolmod' => 'https://www.coolmod.com/nzxt-kraken-elite-280-rgb-2025-lcd-display-negro',
                'Neobyte' => 'https://www.neobyte.es/nzxt-kraken-elite-280-rgb-refrigeracion-liquida-280mm-26527.html',
            ],
            'Arctic Liquid Freezer III Pro 360' => [
                'Coolmod' => 'https://www.coolmod.com/arctic-liquid-freezer-iii-pro-360-negro',
                'Neobyte' => 'https://www.neobyte.es/arctic-liquid-freezer-iii-pro-360-black-refrigeracion-liquida-360mm-34757.html',
            ],
            'NZXT Kraken Elite 360' => [
                'Coolmod' => 'https://www.coolmod.com/nzxt-kraken-elite-360-2025-lcd-display-negro',
                'Neobyte' => 'https://www.neobyte.es/nzxt-kraken-elite-360-refrigeracion-liquida-360mm-26534.html',
            ],
            'Arctic Liquid Freezer III Pro 420' => [
                'Coolmod' => 'https://www.coolmod.com/arctic-liquid-freezer-iii-pro-420-negro',
                'Neobyte' => 'https://www.neobyte.es/arctic-liquid-freezer-iii-pro-420-black-refrigeracion-liquida-420mm-34761.html',
            ],
            'NZXT Kraken Elite 420' => [
                'Coolmod' => 'https://www.coolmod.com/nzxt-kraken-elite-420-rgb-lcd-display-negro',
                'Neobyte' => 'https://www.neobyte.es/nzxt-kraken-elite-420-rgb-refrigeracion-liquida-420mm-29130.html',
            ],

            // ---------- Ventiladores ----------
            'Noctua NF-F12 PWM' => [
                'Coolmod' => 'https://www.coolmod.com/noctua-nf-f12-1500-rpm-pwm-22dba-ventilador-12-cm',
                'Neobyte' => 'https://www.neobyte.es/ventilador-noctua-120x120-nf-f12-pwm-6973.html',
            ],
            'Noctua NF-A14 PWM' => [
                'Coolmod' => 'https://www.coolmod.com/noctua-nf-a14-pwm-ventilador-14-cm',
                'Neobyte' => 'https://www.neobyte.es/ventilador-noctua-caja-nf-a14-140mm-3785.html',
            ],
            'Arctic P12 PWM PST Value Pack (x5)' => [
                'Coolmod' => 'https://www.coolmod.com/arctic-p12-pro-pwm-pst-120mm-negro-pack-5',
                'Neobyte' => 'https://www.neobyte.es/arctic-p12-pro-pst-ln-pack-de-5-ventilador-120mm-37677.html',
            ],
            'Arctic P14 PWM PST Value Pack (x5)' => [
                'Coolmod' => 'https://www.coolmod.com/arctic-p14-pro-pst-140mm-negro-pack-5',
                'Neobyte' => 'https://www.neobyte.es/arctic-p14-pack-de-5-ventilador-140mm--35010.html',
            ],
            'Lian Li UNI FAN SL120 RGB (x3)' => [
                'Coolmod' => 'https://www.coolmod.com/lian-li-uni-fan-sl-inf-120-triple-pack-argb-12cm-blanco',
                'Neobyte' => '',
            ],
            'Lian Li UNI FAN SL140 RGB (x2)' => [
                'Coolmod' => 'https://www.coolmod.com/lian-li-uni-fan-sl140-rgb-pwm-dual-pack-blanco-ventilador-14-cm',
                'Neobyte' => '',
            ],
            'Noctua NF-A12x25 PWM' => [
                'Coolmod' => 'https://www.coolmod.com/noctua-nf-a12x25-pwm-ventilador-12-cm',
                'Neobyte' => 'https://www.neobyte.es/noctua-nf-a12x25-pwm-ventilador-caja-120-mm-3784.html',
            ],
            'be quiet! Silent Wings 4 140mm PWM' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/be-quiet-silent-wings-4-pwm-high-speed-ventilador-140mm-31204.html',
            ],
            'Noctua NF-A12x15 PWM' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/noctua-nf-a12x15-pwm-ventilador-120mm-12420.html',
            ],
            'Thermalright TL-C12 Pro ARGB (x3)' => [
                'Coolmod' => '',
                'Neobyte' => 'https://www.neobyte.es/thermalright-tlc12cs-argb-pack-de-3-ventilador-120mm-36731.html',
            ],
            'DeepCool FL12R ARGB (x3)' => [
                'Coolmod' => 'https://www.coolmod.com/deepcool-fl12r-argb-120mm-triple-pack-blanco',
                'Neobyte' => '',
            ],
            'Phanteks D30 140mm DRGB (x3)' => [
                'Coolmod' => 'https://www.coolmod.com/phanteks-d30-pack-3u-pwm-drgb-reverse-120mm-negro',
                'Neobyte' => '',
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
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Negocio\Regalo;
use App\Models\Negocio\Tienda;
use App\Models\Componentes\Componente;

class RegalosSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Definición de regalos ──────────────────────────────────────────────
        $regalosData = [
            // Videojuegos
            [
                'nombre'         => 'Cyberpunk 2077 (PC - GOG Key)',
                'tipo'           => 'videojuego',
                'imagen_url'     => 'https://cdn.akamai.steamstatic.com/steam/apps/1091500/header.jpg',
                'descripcion'    => 'Clave de activación de Cyberpunk 2077 para PC (plataforma GOG). Incluye el juego base.',
                'valor_estimado' => 29.99,
            ],
            [
                'nombre'         => 'Baldur\'s Gate 3 (PC - Steam Key)',
                'tipo'           => 'videojuego',
                'imagen_url'     => 'https://cdn.akamai.steamstatic.com/steam/apps/1086940/header.jpg',
                'descripcion'    => 'Clave de activación de Baldur\'s Gate 3 para PC via Steam.',
                'valor_estimado' => 59.99,
            ],
            [
                'nombre'         => 'Black Myth: Wukong (PC - Steam Key)',
                'tipo'           => 'videojuego',
                'imagen_url'     => 'https://cdn.akamai.steamstatic.com/steam/apps/2358720/header.jpg',
                'descripcion'    => 'Clave de activación de Black Myth: Wukong para PC via Steam. El juego acción-RPG más esperado de 2024.',
                'valor_estimado' => 49.99,
            ],
            [
                'nombre'         => 'Elden Ring (PC - Steam Key)',
                'tipo'           => 'videojuego',
                'imagen_url'     => 'https://cdn.akamai.steamstatic.com/steam/apps/1245620/header.jpg',
                'descripcion'    => 'Clave de activación de Elden Ring para PC via Steam.',
                'valor_estimado' => 39.99,
            ],
            [
                'nombre'         => 'Alan Wake 2 (PC - Epic Key)',
                'tipo'           => 'videojuego',
                'imagen_url'     => 'https://cdn1.epicgames.com/offer/c4763f236d08423eb47b4c3008779c84/EGS_AlanWake2_RemedyEntertainment_S1_2560x1440-ec44404c0b41bc457cb94cd72cf85872',
                'descripcion'    => 'Clave de activación de Alan Wake 2 para PC via Epic Games Store.',
                'valor_estimado' => 49.99,
            ],
            [
                'nombre'         => 'Starfield (PC - Steam Key)',
                'tipo'           => 'videojuego',
                'imagen_url'     => 'https://cdn.akamai.steamstatic.com/steam/apps/1716740/header.jpg',
                'descripcion'    => 'Clave de activación de Starfield para PC via Steam.',
                'valor_estimado' => 34.99,
            ],

            // Suscripciones
            [
                'nombre'         => 'Xbox Game Pass Ultimate — 3 meses',
                'tipo'           => 'suscripcion',
                'imagen_url'     => 'https://gaming-cdn.com/images/products/4994/orig/xbox-game-pass-ultimate-3-meses-xbox-one-xbox-series-x-s-pc-microsoft-store-cover.jpg?v=1776851245',
                'descripcion'    => 'Suscripción de 3 meses a Xbox Game Pass Ultimate. Acceso a más de 400 juegos en PC y consola, EA Play incluido.',
                'valor_estimado' => 44.99,
            ],
            [
                'nombre'         => 'Xbox Game Pass PC — 1 mes',
                'tipo'           => 'suscripcion',
                'imagen_url'     => 'https://gaming-cdn.com/images/products/4994/orig/xbox-game-pass-ultimate-3-meses-xbox-one-xbox-series-x-s-pc-microsoft-store-cover.jpg?v=1776851245',
                'descripcion'    => 'Suscripción de 1 mes a Xbox Game Pass para PC. Acceso a cientos de juegos en PC.',
                'valor_estimado' => 9.99,
            ],
            [
                'nombre'         => 'GeForce NOW Priority — 1 mes',
                'tipo'           => 'suscripcion',
                'imagen_url'     => 'https://acf.geeknetic.es/imgw/imagenes/auto/2023/10/16/jxz-image.png?f=webp',
                'descripcion'    => 'Suscripción de 1 mes a GeForce NOW Priority. Juega en la nube con servidores RTX sin colas de espera.',
                'valor_estimado' => 9.99,
            ],
            [
                'nombre'         => 'Adobe Creative Cloud — 1 mes',
                'tipo'           => 'suscripcion',
                'imagen_url'     => 'https://tecnopcchile.shop/wp-content/uploads/2025/10/adobecreativecloud2.jpg',
                'descripcion'    => 'Un mes de acceso a todas las aplicaciones de Adobe Creative Cloud: Photoshop, Premiere, After Effects y más.',
                'valor_estimado' => 54.99,
            ],

            // Accesorios / periféricos
            [
                'nombre'         => 'Cable de alimentación PCIe 5.0 (12VHPWR)',
                'tipo'           => 'accesorio',
                'imagen_url'     => 'https://cdn.videocardz.com/1/2022/08/atx30-20220809-2.jpg',
                'descripcion'    => 'Cable adaptador 12VHPWR de alta calidad para conectar GPUs de última generación que requieren el conector PCIe 5.0. Incluye clip de seguridad.',
                'valor_estimado' => 14.99,
            ],
            [
                'nombre'         => 'Pasta térmica Thermal Grizzly Kryonaut',
                'tipo'           => 'accesorio',
                'imagen_url'     => 'https://m.media-amazon.com/images/I/714NqcsBOlL.jpg_BO30,255,255,255_UF750,750_SR1910,1000,0,C_QL100_.jpg',
                'descripcion'    => 'Pasta térmica de alto rendimiento Thermal Grizzly Kryonaut (1 g). Referencia para overclockers y entusiastas del cooling.',
                'valor_estimado' => 9.99,
            ],
            [
                'nombre'         => 'SSD Thermal Pad Kit (M.2)',
                'tipo'           => 'accesorio',
                'imagen_url'     => 'https://akasa.co.uk/img/product/common/feature/00/AK-TT600-KT03_f0E.png',
                'descripcion'    => 'Kit de almohadillas térmicas para SSDs M.2. Reduce temperaturas hasta 10 °C en unidades NVMe de alta velocidad.',
                'valor_estimado' => 7.99,
            ],
            [
                'nombre'         => 'Organizador de cables modular Velcro (pack 20 uds.)',
                'tipo'           => 'accesorio',
                'imagen_url'     => 'https://m.media-amazon.com/images/I/81JBsaUNgDL._AC_UF894,1000_QL80_.jpg',
                'descripcion'    => 'Pack de 20 bridas de velcro reutilizables para organización de cables en torre. Compatibles con cualquier formato de gabinete.',
                'valor_estimado' => 5.99,
            ],

            // Otros
            [
                'nombre'         => 'Voucher 20€ PCComponentes',
                'tipo'           => 'otro',
                'imagen_url'     => 'https://assets.discoup.com/public/arjeta-regalo-GIJSgaCWaiQ.png?q=85&s=0a678d1d3ff677b38dc59e3d107f8d25',
                'descripcion'    => 'Vale de 20€ de descuento canjeable en cualquier compra en PCComponentes sin mínimo de gasto.',
                'valor_estimado' => 20.00,
            ],
            [
                'nombre'         => 'Voucher 15€ Amazon España',
                'tipo'           => 'otro',
                'imagen_url'     => 'https://i.blogs.es/2f8ca0/amazon-15-euros/840_560.jpg',
                'descripcion'    => 'Saldo de 15€ en tarjeta regalo Amazon España, aplicable a cualquier producto del catálogo.',
                'valor_estimado' => 15.00,
            ],
        ];

        // ── 2. Crear los regalos ──────────────────────────────────────────────────
        $regalos = [];
        foreach ($regalosData as $data) {
            $regalos[$data['nombre']] = Regalo::create($data);
        }

        // ── 3. Tiendas involucradas ───────────────────────────────────────────────
        $pccomponentes = Tienda::where('nombre', 'PCComponentes')->first();
        $amazon        = Tienda::where('nombre', 'Amazon España')->first();
        $coolmod       = Tienda::where('nombre', 'Coolmod')->first();
        $mediaMarkt    = Tienda::where('nombre', 'MediaMarkt')->first();
        $alternate     = Tienda::where('nombre', 'Alternate')->first();

        // ── 4. Seleccionar el 10 % de los componentes de forma determinista ───────
        //
        // Ordenamos por ID para garantizar reproducibilidad. Tomamos cada décimo
        // componente (posiciones 0, 9, 19 …) hasta cubrir el 10 % del catálogo.
        // De esta manera el resultado es idempotente aunque se añadan seeders
        // encima del mismo, siempre que los IDs no cambien.
        //
        // values() resetea las claves a índices ordinales (0, 1, 2…) antes del
        // filter; sin él la clave sería el ID del modelo (arbitrario) y % 10 no
        // garantizaría el 10 % esperado.
        $seleccionados = Componente::orderBy('id')->get()->values()
            ->filter(fn ($c, $index) => $index % 10 === 0);

        // ── 5. Asociar regalos a componentes ─────────────────────────────────────
        //
        // Asignamos el regalo según la categoría del componente para que sea
        // coherente (una GPU de alta gama recibe un juego exigente, una CPU
        // recibe Game Pass, un accesorio de refrigeración recibe pasta térmica…).
        // Si la categoría no encaja con ninguna regla, usamos un voucher genérico.
        //
        foreach ($seleccionados as $componente) {
            [$regalo, $tienda] = $this->elegirRegalo($componente, $regalos, [
                'pccomponentes' => $pccomponentes,
                'amazon'        => $amazon,
                'coolmod'       => $coolmod,
                'mediaMarkt'    => $mediaMarkt,
                'alternate'     => $alternate,
            ]);

            if (!$regalo || !$tienda) {
                continue;
            }

            $componente->regalos()->attach($regalo->id, [
                'tienda_id'        => $tienda->id,
                'fecha_inicio'     => now(),
                'fecha_expiracion' => now()->addMonths(2),
                'activo'           => true,
            ]);
        }
    }

    // ── Helper: elige regalo y tienda según la categoría del componente ───────────
    private function elegirRegalo(
        Componente $componente,
        array      $regalos,
        array      $tiendas,
    ): array {
        $categoria = $componente->categoria;

        return match (true) {
            // GPUs de gama alta → juego exigente en gráficos + PCComponentes
            $categoria === 'gpu' => [
                $regalos['Black Myth: Wukong (PC - Steam Key)'],
                $tiendas['pccomponentes'],
            ],

            // CPUs AM5 / Intel Arc recientes → Game Pass Ultimate 3 meses + Amazon
            $categoria === 'cpu' && str_contains($componente->nombre, '9') => [
                $regalos['Xbox Game Pass Ultimate — 3 meses'],
                $tiendas['amazon'],
            ],

            // CPUs AM4 / Intel de generación anterior → Game Pass PC 1 mes
            $categoria === 'cpu' => [
                $regalos['Xbox Game Pass PC — 1 mes'],
                $tiendas['pccomponentes'],
            ],

            // Refrigeraciones líquidas → pasta térmica Kryonaut
            $categoria === 'refrigeracion_liquida' => [
                $regalos['Pasta térmica Thermal Grizzly Kryonaut'],
                $tiendas['coolmod'],
            ],

            // Refrigeraciones de aire → thermal pad kit
            $categoria === 'refrigeracion_aire' => [
                $regalos['SSD Thermal Pad Kit (M.2)'],
                $tiendas['alternate'],
            ],

            // Almacenamiento NVMe → SSD thermal pad kit
            $categoria === 'almacenamiento' => [
                $regalos['SSD Thermal Pad Kit (M.2)'],
                $tiendas['amazon'],
            ],

            // Gabinetes → organizador de cables
            $categoria === 'gabinete' => [
                $regalos['Organizador de cables modular Velcro (pack 20 uds.)'],
                $tiendas['amazon'],
            ],

            // PSUs → cable PCIe 5.0
            $categoria === 'psu' => [
                $regalos['Cable de alimentación PCIe 5.0 (12VHPWR)'],
                $tiendas['pccomponentes'],
            ],

            // Placas base → Elden Ring (buen port PC, muy popular)
            $categoria === 'placa_base' => [
                $regalos['Elden Ring (PC - Steam Key)'],
                $tiendas['mediaMarkt'],
            ],

            // RAM → voucher Amazon
            $categoria === 'ram' => [
                $regalos['Voucher 15€ Amazon España'],
                $tiendas['amazon'],
            ],

            // Ventiladores y resto → voucher PCComponentes
            default => [
                $regalos['Voucher 20€ PCComponentes'],
                $tiendas['pccomponentes'],
            ],
        };
    }
}
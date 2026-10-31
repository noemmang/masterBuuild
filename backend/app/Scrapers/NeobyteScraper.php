private const CACHE_LANDING_TTL_HORAS = 24;
private const CACHE_LANDING_FALLO_MINUTOS = 30;
private const BANNER_ANCHO_MIN = 800;
private const BANNER_RATIO_PANORAMICO = 1.8;

private function imagenBannerDeLanding(string $urlPromo, string $slug): ?string
{
    // Solo landings de Neobyte
    $host = parse_url($urlPromo, PHP_URL_HOST);
    if (!is_string($host) || !str_ends_with(strtolower($host), 'neobyte.es')) {
        return null;
    }

    $clave = 'neobyte:landing-banner:'.sha1($urlPromo);

    try {
        $guardado = Cache::get($clave);

        if (is_array($guardado)) {
            return $guardado['imagen'];
        }

        try {
            $imagen = $this->elegirBannerDeLanding($this->descargar($urlPromo), $urlPromo, $slug);
        } catch (ScrapingException $e) {
            Log::warning("Neobyte: no se pudo abrir la landing {$urlPromo}: {$e->getMessage()}");
            Cache::put($clave, ['imagen' => null], now()->addMinutes(self::CACHE_LANDING_FALLO_MINUTOS));

            return null;
        }

        if ($imagen === null) {
            Log::warning("Neobyte: la landing {$urlPromo} no tiene un banner reconocible. ¿Formato nuevo?");
        }

        Cache::put(
            $clave,
            ['imagen' => $imagen],
            $imagen !== null
                ? now()->addHours(self::CACHE_LANDING_TTL_HORAS)
                : now()->addMinutes(self::CACHE_LANDING_FALLO_MINUTOS)
        );

        return $imagen;
    } catch (Throwable $e) {
        Log::warning("Neobyte: error leyendo el banner de {$urlPromo}: {$e->getMessage()}");

        return null;
    }
}

private function elegirBannerDeLanding(Crawler $crawler, string $urlLanding, string $slug): ?string
{
    $idCms = preg_match('/-(\d+)$/', $slug, $m) ? $m[1] : null;

    $candidatas = [];
    $orden = 0;

    foreach ($crawler->filter('#content img') as $img) {
        /** @var \DOMElement $img */
        $orden++;

        $src = '';
        foreach (['src', 'data-src', 'data-lazy-src'] as $atributo) {
            $valor = trim($img->getAttribute($atributo));
            if ($valor !== '' && !str_starts_with($valor, 'data:')) {
                $src = $valor;
                break;
            }
        }

        $url = $src !== '' ? $this->urlAbsoluta($src, $urlLanding) : null;
        if ($url === null || !preg_match('/\.(jpe?g|png|webp)(\?|$)/i', $url)) {
            continue;
        }

        [$ancho, $alto] = [(int) $img->getAttribute('width'), (int) $img->getAttribute('height')];
        if ($ancho < self::BANNER_ANCHO_MIN || $alto <= 0) {
            continue;
        }

        $propia = $idCms !== null && str_contains((string) parse_url($url, PHP_URL_PATH), "/{$idCms}/");

        $candidatas[] = [
            'url'        => $url,
            'propia'     => $propia,
            'panoramica' => ($ancho / $alto) >= self::BANNER_RATIO_PANORAMICO,
            'ancho'      => $ancho,
            'orden'      => $orden,
        ];
    }

    if ($candidatas !== []) {
        usort($candidatas, fn ($a, $b) => [$b['propia'], $b['panoramica'], $b['ancho'], $a['orden']]
            <=> [$a['propia'], $a['panoramica'], $a['ancho'], $b['orden']]);

        $elegida = $candidatas[0]['url'];

        return mb_strlen($elegida) <= 500 ? $elegida : null;
    }

    // Último recurso
    $og = $crawler->filter('meta[property="og:image"]');
    if ($og->count() > 0) {
        $url = $this->urlAbsoluta((string) $og->first()->attr('content'), $urlLanding);

        if ($url !== null && mb_strlen($url) <= 500) {
            return $url;
        }
    }

    return null;
}
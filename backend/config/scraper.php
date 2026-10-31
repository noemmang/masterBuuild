<?php

/*
|--------------------------------------------------------------------------
| Configuración del scraping
|--------------------------------------------------------------------------
|
| Todo se controla por variables de entorno, así que cambiar de estrategia
| (directo → proxy → API de scraping) en Azure NO requiere tocar código ni
| reconstruir la imagen: basta con actualizar las variables del Container
| App Job.
*/

return [

    /*
    | Cómo se hacen las peticiones a las tiendas:
    |
    |  direct → conexión directa desde la IP del contenedor (lo de siempre).
    |           En local funciona porque tu IP es residencial; desde Azure la
    |           IP es de datacenter y muchas tiendas (Cloudflare, etc.) la
    |           bloquean.
    |  proxy  → sale por SCRAPER_PROXY (proxy residencial / ISP).
    |  api    → delega la descarga en una API de scraping de pago
    |           (ScrapingBee, ZenRows, Scrape.do, ScraperAPI...) mediante
    |           SCRAPER_API_URL_TEMPLATE.
    */
    'modo' => env('SCRAPER_MODO', 'direct'),

    // http://usuario:clave@host:puerto  (solo modo "proxy")
    'proxy' => env('SCRAPER_PROXY'),

    /*
    | Plantilla de URL de la API de scraping (solo modo "api"). {url} se
    | sustituye por la URL del producto ya codificada. Ejemplo ScrapingBee:
    |   https://app.scrapingbee.com/api/v1/?api_key=TU_KEY&render_js=false&country_code=es&url={url}
    */
    'api_url_template' => env('SCRAPER_API_URL_TEMPLATE'),

    // Timeouts en segundos. Un connect_timeout corto evita quedarse
    // esperando 15 s por cada URL cuando la IP está "tragada" (blackhole).
    'timeout'         => (int) env('SCRAPER_TIMEOUT', 25),
    'connect_timeout' => (int) env('SCRAPER_CONNECT_TIMEOUT', 10),

    // Reintentos SOLO para errores de red y 5xx (nunca para 403/429: insistir
    // en un bloqueo solo empeora la reputación de la IP).
    'reintentos' => (int) env('SCRAPER_REINTENTOS', 1),

    /*
    | User-Agent. El anterior (Chrome 124, abril de 2024) tenía más de dos
    | años: los WAF puntúan como "bot" a los navegadores antiguos. Conviene
    | actualizarlo de vez en cuando (versión estable actual: Chrome 156).
    */
    'user_agent' => env(
        'SCRAPER_USER_AGENT',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'
    ),
    'chrome_major' => (int) env('SCRAPER_CHROME_MAJOR', 156),

    /*
    | Alertas. Todas opcionales; si no hay ninguna configurada el aviso
    | queda solo en el log (stderr → Log Analytics).
    |
    |  alert_webhook   → URL de un webhook de Discord o Slack.
    |  alert_email     → correo al que avisar (usa el MAIL_MAILER de la app).
    |  heartbeat_url   → URL de ping tipo healthchecks.io. Es la red de
    |                    seguridad más importante: avisa también cuando el
    |                    Job NO llega a ejecutarse, se cuelga o lo mata Azure
    |                    por timeout, casos en los que el propio código no
    |                    puede avisar de nada.
    */
    'alert_webhook' => env('SCRAPER_ALERT_WEBHOOK'),
    'alert_email'   => env('SCRAPER_ALERT_EMAIL'),
    'heartbeat_url' => env('SCRAPER_HEARTBEAT_URL'),

    // Horas sin datos frescos a partir de las cuales se considera caído.
    'horas_obsoleto' => (int) env('SCRAPER_HORAS_OBSOLETO', 30),
];
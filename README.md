# 🖥️ MasterBuild

> 🔎 Comparador de precios de componentes de PC en España.

**MasterBuild** es un comparador de precios de hardware, similar a Idealo pero centrado exclusivamente en componentes de PC. Permite buscar componentes, comparar sus precios entre tiendas y acceder directamente a ellas para comprar.

La aplicación **no vende productos**: actúa como intermediaria mostrando la mejor información disponible. Los precios no son ficticios, se obtienen a diario de las fichas reales de cada tienda. 💸

🌐 **Demo:** https://masterbuild.noemmang.com

---

## 🎯 Objetivo del proyecto

Este proyecto se desarrolló como **Trabajo de Fin de Grado (TFG)** del ciclo **Desarrollo de Aplicaciones Web (DAW)**. Es uno de mis primeros proyectos completos y el primero que despliego en la nube.

Lo que buscaba aprender:

* 🕷️ **Scraping** — obtener precios reales de las tiendas en lugar de usar datos de ejemplo.
* 🔀 **Dos lenguajes en un mismo proyecto** — PHP en el backend y TypeScript en el frontend, comunicados por una API REST.
* 🏗️ **MVC** — llevar la arquitectura de Laravel (modelos, controladores y servicios) a una aplicación completa.
* ☁️ **Azure** — publicar el frontend, el backend y las tareas programadas en la nube.
* 🚀 **Workflows con GitHub Actions** — automatizar la construcción y el despliegue en cada cambio.

El repositorio se comparte con fines **didácticos y educativos**. 🎓

---

## ✨ Funcionalidades principales

* 🔍 **Buscador** de componentes por tipo, con filtros y orden por relevancia.
* 💸 **Comparador de precios** entre tiendas, con enlace directo a cada una.
* 📈 **Historial de precios** con gráficas: media, rango mínimo-máximo y anotaciones del mínimo y máximo histórico.
* 🧠 **Configurador de PC** con validación de compatibilidad y dimensiones entre componentes.
* 🎯 **Recomendador** de configuraciones según el perfil de uso: gaming básico, gaming alto, edición de vídeo, renderizado u oficina.
* 📊 **Comparador de especificaciones** técnicas entre componentes.
* 🧊 **Visor 3D** de gabinetes para comparar sus dimensiones.
* 🎁 **Promociones y regalos** que las tiendas ofrecen con cada producto.
* ⭐ **Guardados** de componentes y de configuraciones completas.
* 🔔 **Alertas** de bajada de precio, con aviso por email y en la web.
* 📬 **Notificaciones** en la web con contador de no leídas, también cuando un guardado se agota o vuelve a estar disponible.
* 👤 **Cuenta de usuario**: registro, inicio de sesión, edición del perfil y eliminación de la cuenta.
* 🌗 **Modo claro y oscuro**.

---

## 🏗️ Arquitectura

MasterBuild separa el frontend y el backend: una **SPA en Angular** consume una **API REST en Laravel**. Un job programado en la nube ejecuta cada noche el scraping y las tareas que dependen de él.

```text
        🌐 Usuario
            │
            ▼
   🅰️ Frontend (Angular)
   Azure Static Web Apps
            │  HTTPS / JSON
            ▼
   🐘 Backend (Laravel API)          ⏱️ Job diario (cron 02:00)
   Azure Container Apps  ◄────────── scrape:diario
            │                                │
            ▼                                ▼
   🗄️ PostgreSQL (Neon)           🏬 Tiendas (fichas de producto)
```

### 🔄 Tarea diaria

El job `scrape:diario` es el único comando programado y encadena, en este orden:

1. 🕷️ `scrape:precios` — descarga precios, stock y promociones de las tiendas y actualiza el historial.
2. 🔔 `notificaciones:verificar` — compara el resultado con los guardados y las alertas de los usuarios y envía los avisos que correspondan.
3. 📊 `relevancia:recalcular` — recalcula la relevancia de cada componente y refresca la caché del home.
4. 👀 `scrape:vigilar` — avisa si alguna tienda lleva demasiado tiempo sin datos frescos.

El orden importa: la comprobación de alertas solo tiene sentido cuando los precios de esa noche ya están guardados.

---

## 🕷️ Scraping

Cada tienda tiene su propio scraper, que implementa una interfaz común (`ScraperTienda`) sobre una clase base compartida. Los scrapers leen los datos estructurados `JSON-LD` (`schema.org/Product`) de la ficha real del producto.

* 🏬 La base de datos registra 15 tiendas. Hoy hay scrapers implementados para **Coolmod** y **Neobyte**.
* ⏳ Pausa aleatoria entre peticiones para no saturar las tiendas.
* 🚦 Una URL que falla varias veces seguidas se marca como no disponible.
* 🩺 `scrape:diagnostico` prueba una ficha de cada tienda y explica por qué falla (IP bloqueada, desafío anti-bot, selectores…).
* 🗂️ El historial guarda cada tramo de precio por componente y tienda, no una fila por día, y permite consultar hasta 3 años.

---

## 🛠️ Stack tecnológico

### 🎨 Frontend

* 🅰️ **Angular 21** — componentes standalone y **Signals** para el estado reactivo
* 🟦 **TypeScript**
* 📈 **ApexCharts** — gráficas del historial de precios
* 🧊 **Three.js** — visor 3D de gabinetes
* 🧪 **Vitest** y **Playwright** — pruebas unitarias y end to end

### ⚙️ Backend

* 🐘 **Laravel 13** sobre **PHP 8.4**
* 🔐 **Laravel Sanctum** — autenticación con tokens
* 🧬 **Eloquent** — modelos, relaciones, migraciones y seeders
* 🚀 **FrankenPHP** (Caddy) — servidor de la aplicación, empaquetado en **Docker**

### 🗄️ Base de datos

* 🐘 **PostgreSQL** alojado en **Neon** (serverless)
* 🔎 Índice trigram (`pg_trgm`) para que la búsqueda por nombre no recorra toda la tabla

### ☁️ Infraestructura y DevOps

* 🪟 **Azure Static Web Apps** — frontend
* 📦 **Azure Container Apps** — backend, y **Container Apps Job** para el scraping diario
* 🗃️ **Azure Container Registry** — imágenes del backend
* 🚀 **GitHub Actions** — CI/CD
* 🌍 **Cloudflare** — dominio propio

---

## 📁 Estructura del proyecto

```text
masterBuild/
│
├── 🅰️ frontend/
│   ├── src/app/
│   │   ├── features/        # home, search, configurator, spec-compare,
│   │   │                    # case-viewer, saved, profile, auth
│   │   ├── shared/          # componentes reutilizables (header, tarjetas,
│   │   │                    # historial de precios, visor 3D, notificaciones)
│   │   └── core/            # servicios, interceptor y guard de autenticación
│   ├── e2e/                 # pruebas end to end (Playwright)
│   └── proxy.conf.json      # proxy de /api al backend en desarrollo
│
├── 🐘 backend/
│   ├── app/
│   │   ├── Http/Controllers/Api/   # Componentes, Configurador, Negocio, Auxiliares
│   │   ├── Models/                 # Componentes, Negocio, Auxiliares
│   │   ├── Services/               # compatibilidad, relevancia, bajadas, promociones
│   │   ├── Scrapers/               # interfaz común y un scraper por tienda
│   │   ├── Console/Commands/       # scrape:*, notificaciones:verificar, relevancia:recalcular
│   │   └── Notifications/          # emails de alertas y de cuenta
│   ├── database/                   # migraciones y seeders
│   ├── routes/api.php
│   ├── Dockerfile
│   └── Caddyfile
│
└── ⚙️ .github/workflows/
    ├── azure-static-web-apps-*.yml   # despliegue del frontend
    └── backend-deploy.yml            # imagen, backend y job
```

---

## 🧩 Modelo de datos

El esquema relacional separa el catálogo de componentes de la parte de negocio:

* 🧱 **Componentes** — tabla base `componentes` con una tabla por tipo: CPU, GPU, placa base, RAM, almacenamiento, fuente, gabinete, refrigeración por aire y líquida, y ventiladores.
* 📚 **Auxiliares** — marcas, sockets, chipsets, tipos de memoria, factores de forma, versiones de PCIe, certificaciones de fuentes, etc. Se usan para comprobar la compatibilidad.
* 🏬 **Tiendas y precios** — tiendas, URL de producto por tienda, precio actual e historial de precios.
* 👤 **Usuarios** — componentes guardados, configuraciones guardadas y alertas de precio.
* 📊 **Relevancia** — interacciones de los usuarios (búsquedas y selecciones) y métricas calculadas cada noche.

---

## 🔌 API

API REST versionada bajo `/api/v1`, con recursos públicos y recursos protegidos por Sanctum.

| Grupo | Ejemplos | Acceso |
|---|---|---|
| 🔐 Autenticación | `POST /auth/register`, `POST /auth/login`, `GET /auth/me` | Público / privado |
| 🧱 Componentes | `GET /componentes`, `GET /componentes/{uuid}`, `GET /componentes/destacados` | Público |
| 💸 Precios | `GET /componentes/{uuid}/precios`, `GET /componentes/{uuid}/precios/historial` | Público |
| 🧠 Configurador | `POST /configurador/validar`, `POST /configurador/recomendar` | Público |
| ⭐ Guardados y configuraciones | `GET/POST/PATCH/DELETE` sobre cada recurso | Privado |
| 🔔 Alertas y notificaciones | `GET/POST/PATCH/DELETE`, `GET /notificaciones/contador` | Privado |

---

## 💻 Ejecución en local

Requisitos: **PHP 8.4**, **Composer**, **Node.js** con npm y **PostgreSQL** (la búsqueda usa la extensión `pg_trgm`).

### 🐘 Backend

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
```

Configura en `.env` la conexión a PostgreSQL (`DB_CONNECTION=pgsql`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) y después:

```bash
php artisan migrate --seed
php artisan serve
```

La API queda en `http://127.0.0.1:8000`. En local los emails se escriben en el log (`MAIL_MAILER=log`).

Para probar el scraping manualmente:

```bash
php artisan scrape:precios Coolmod
php artisan scrape:diagnostico
```

### 🅰️ Frontend

```bash
cd frontend
npm install
npx ng serve --proxy-config proxy.conf.json
```

El proxy redirige `/api` al backend local. La aplicación queda en `http://localhost:4200`.

---

## 🔄 CI/CD

Hay dos workflows de GitHub Actions:

1. 🪟 **Frontend** — en cada push a `main` construye la SPA y la publica en **Azure Static Web Apps**. En las pull requests crea un entorno de previsualización.
2. 🐳 **Backend** — en cada push a `main` que toque `backend/`:
   * Construye la imagen Docker y la publica en **Azure Container Registry** con dos etiquetas: `latest` y el hash del commit.
   * Actualiza la **Container App** y el **Job** del scraping con la etiqueta del commit, no con `latest`, para que cada despliegue sea explícito y reproducible.

El `Dockerfile` aborta la construcción si la imagen base trae una versión de PHP sin parchear, para no desplegar nunca una imagen vulnerable.

---

## ☁️ Despliegue en Azure

* 🪟 **Frontend** en Azure Static Web Apps.
* 📦 **Backend** en Azure Container Apps. Al arrancar, el contenedor aplica las migraciones y levanta FrankenPHP.
* ⏱️ **Scraping** en un Container Apps Job programado con cron (`0 2 * * *`).
* 🗄️ **Base de datos** en Neon.
* 🌍 **Dominio propio** (`masterbuild.noemmang.com`) gestionado en Cloudflare.
* 📧 **Emails** de producción con Resend.

---

## 🚧 Estado del proyecto

🟡 **En desarrollo activo**

La aplicación está desplegada y funcional. Hay partes que siguen evolucionando, como el número de tiendas con scraper o la cobertura de pruebas.

### 🔮 Posibles mejoras

* 🏬 Scrapers para el resto de tiendas registradas.
* 🧪 Más pruebas automáticas del backend y del configurador de compatibilidad.
* 📄 Documentación de la API con OpenAPI.

---

## 👨‍💻 Autor

**Noe Mmang Obono**

Proyecto publicado con fines **didácticos y educativos**. 🎓📚

---

### ⭐ ¡Gracias por visitar el proyecto! 🖥️

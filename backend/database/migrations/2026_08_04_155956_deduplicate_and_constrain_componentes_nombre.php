<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ComponentesSeeder (antes de este fix) creaba cada componente con
 * Componente::create(...) sin comprobar si ya existía uno con el mismo
 * nombre. Si el seeder se ejecutaba más de una vez sin migrate:fresh, cada
 * pasada volvía a insertar los ~300 componentes como filas nuevas.
 *
 * Consecuencia visible: UrlProductoTiendaSeeder busca el componente con
 * Componente::where('nombre', ...)->first(), así que solo UNA de las filas
 * duplicadas terminaba con url_producto_tienda y, por tanto, con precio.
 * El resto de duplicados quedaban sin url → sin scrapear → sin precio.
 *
 * Esta migración:
 *   1. Detecta grupos de componentes duplicados (mismo nombre, no borrados).
 *   2. En cada grupo, se queda con el que ya tiene datos de precio/url
 *      (el "bueno"), o si ninguno los tiene, con el de id más bajo.
 *   3. Borra físicamente (forceDelete, no soft delete) el resto: al ser
 *      DELETE real, Postgres hace cascade automático sobre cpus, gpus,
 *      ram, urls_producto_tienda, entradas_precio, etc. (todas esas tablas
 *      tienen ->cascadeOnDelete() sobre componente_id).
 *   4. Añade un índice único parcial sobre nombre (solo filas no borradas)
 *      para que esto no pueda volver a pasar, ni siquiera si el seeder
 *      vuelve a tener un bug parecido en el futuro.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        // ── 1-3: deduplicar ─────────────────────────────────────────────
        DB::statement(<<<'SQL'
            WITH duplicados AS (
                SELECT
                    c.id,
                    c.nombre,
                    -- true si este componente tiene alguna url/tienda asociada
                    EXISTS (
                        SELECT 1 FROM urls_producto_tienda u
                        WHERE u.componente_id = c.id AND u.deleted_at IS NULL
                    ) AS tiene_url,
                    ROW_NUMBER() OVER (
                        PARTITION BY c.nombre
                        ORDER BY
                            EXISTS (
                                SELECT 1 FROM urls_producto_tienda u
                                WHERE u.componente_id = c.id AND u.deleted_at IS NULL
                            ) DESC,  -- el que tiene url va primero
                            c.id ASC                     -- empate: el más antiguo
                    ) AS orden
                FROM componentes c
                WHERE c.deleted_at IS NULL
            )
            DELETE FROM componentes
            WHERE id IN (SELECT id FROM duplicados WHERE orden > 1)
        SQL);

        // ── 4: constraint que evita que vuelva a pasar ──────────────────
        DB::statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS componentes_nombre_unique_idx ON componentes (nombre) WHERE deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS componentes_nombre_unique_idx');
        // La eliminación de duplicados no se puede deshacer (no sabemos
        // qué filas se borraron); si hace falta, restaurar desde backup.
    }
};
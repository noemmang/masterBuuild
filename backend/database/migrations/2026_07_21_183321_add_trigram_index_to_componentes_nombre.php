<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * El buscador (ComponenteController::index, filtro "buscar") hace
     * WHERE nombre ILIKE '%texto%'. Sin un índice trigram, Postgres no
     * puede usar B-tree para ese patrón y acaba haciendo Seq Scan sobre
     * toda la tabla componentes en cada búsqueda. pg_trgm + GIN permite
     * usar índice también con comodines al principio y al final.
     *
     * Solo se aplica en PostgreSQL: en local con sqlite (tests, sqlite en
     * memoria) esta extensión no existe y el ILIKE tampoco se usa igual.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX IF NOT EXISTS componentes_nombre_trgm_idx ON componentes USING GIN (nombre gin_trgm_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS componentes_nombre_trgm_idx');
    }
};
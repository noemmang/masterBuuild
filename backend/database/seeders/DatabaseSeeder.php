<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AuxiliaresSeeder::class,
            MarcasSeeder::class,
            TiendasSeeder::class,
            ComponentesSeeder::class,
            CuponesSeeder::class,
        ]);
    }
}
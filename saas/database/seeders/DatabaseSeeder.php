<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Tenant::updateOrCreate(
            ['nombre_comercial' => 'SALQUI S.R.L.'],
            ['estado' => 'activo']
        );

        Tenant::updateOrCreate(
            ['nombre_comercial' => 'Gran Palacio de la Industria'],
            ['estado' => 'activo']
        );
    }
}

<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenantSalqui = Tenant::updateOrCreate(
            ['nombre_comercial' => 'SALQUI S.R.L.'],
            ['estado' => 'activo']
        );

        $tenantGranPalacio = Tenant::updateOrCreate(
            ['nombre_comercial' => 'Gran Palacio de la Industria'],
            ['estado' => 'activo']
        );

        User::updateOrCreate(
            ['email' => 'admin@salqui.com'],
            [
                'tenant_id' => $tenantSalqui->id,
                'name' => 'Administrador SALQUI',
                'password' => Hash::make('Clave12345'),
                'rol' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@granpalacio.com'],
            [
                'tenant_id' => $tenantGranPalacio->id,
                'name' => 'Administrador Gran Palacio',
                'password' => Hash::make('Clave12345'),
                'rol' => 'admin',
            ]
        );

        $this->call(DatosDemostracionSeeder::class);
    }
}

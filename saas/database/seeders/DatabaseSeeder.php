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
            ['email' => 'admin@salqui.test'],
            [
                'tenant_id' => $tenantSalqui->id,
                'name' => 'Administrador SALQUI',
                'password' => Hash::make('ClaveSegura123!'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@granpalacio.test'],
            [
                'tenant_id' => $tenantGranPalacio->id,
                'name' => 'Administrador Gran Palacio',
                'password' => Hash::make('ClaveSegura123!'),
            ]
        );
    }
}

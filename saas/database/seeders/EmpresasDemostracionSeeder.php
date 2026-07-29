<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmpresasDemostracionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tenants')->updateOrInsert(
            ['nombre_comercial' => 'SALQUI S.R.L.'],
            [
                'estado' => 'activo',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $salquiId = DB::table('tenants')
            ->where('nombre_comercial', 'SALQUI S.R.L.')
            ->value('id');

        DB::table('users')->updateOrInsert(
            ['email' => 'admin@salqui.com'],
            [
                'tenant_id' => $salquiId,
                'name' => 'Administrador SALQUI',
                'password' => Hash::make('Clave12345'),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('tenants')->updateOrInsert(
            ['nombre_comercial' => 'Gran Palacio de la Industria'],
            [
                'estado' => 'activo',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $palacioId = DB::table('tenants')
            ->where('nombre_comercial', 'Gran Palacio de la Industria')
            ->value('id');

        DB::table('users')->updateOrInsert(
            ['email' => 'admin@palacio.com'],
            [
                'tenant_id' => $palacioId,
                'name' => 'Administrador Gran Palacio',
                'password' => Hash::make('Clave12345'),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
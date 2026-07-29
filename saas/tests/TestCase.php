<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        /*
         * Esta comprobación ocurre antes de iniciar Laravel.
         * Evita que RefreshDatabase trabaje sobre saas_db.
         */
        $this->verificarVariablesDePrueba();

        parent::setUp();

        /*
         * Segunda comprobación después de iniciar Laravel.
         * Confirma la conexión que realmente resolvió el framework.
         */
        $baseDeDatos = DB::connection()->getDatabaseName();

        if ($baseDeDatos !== 'saas_test') {
            throw new RuntimeException(
                "Pruebas detenidas: Laravel conectó con {$baseDeDatos}. " .
                'La única base permitida es saas_test.'
            );
        }
    }

    private function verificarVariablesDePrueba(): void
    {
        $valoresDetectados = [
            'getenv' => getenv('DB_DATABASE') ?: null,
            '_ENV' => $_ENV['DB_DATABASE'] ?? null,
            '_SERVER' => $_SERVER['DB_DATABASE'] ?? null,
        ];

        foreach ($valoresDetectados as $origen => $baseDeDatos) {
            if (
                $baseDeDatos !== null &&
                $baseDeDatos !== '' &&
                $baseDeDatos !== 'saas_test'
            ) {
                throw new RuntimeException(
                    "Pruebas detenidas antes de iniciar Laravel: " .
                    "{$origen} contiene DB_DATABASE={$baseDeDatos}. " .
                    'Debe utilizarse saas_test.'
                );
            }
        }
    }
}
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $baseDeDatos = DB::connection()->getDatabaseName();

        if ($baseDeDatos !== 'saas_test') {
            throw new RuntimeException(
                "Pruebas detenidas: PHPUnit intentó utilizar la base {$baseDeDatos}. " .
                'La base permitida es saas_test.'
            );
        }
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la columna usada para eliminación lógica.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    /**
     * Elimina la columna de eliminación lógica.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
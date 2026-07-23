<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('usuario_id');
            $table->decimal('total', 10, 2);
            $table->enum('estado', ['pendiente', 'pagada', 'anulada'])->default('pendiente');
            $table->timestamps();

            $table->index('tenant_id', 'idx_ventas_tenant');
            $table->index(['tenant_id', 'created_at'], 'idx_ventas_tenant_fecha');
            $table->foreign('tenant_id', 'fk_ventas_tenant')->references('id')->on('tenants');
            $table->foreign('cliente_id', 'fk_ventas_cliente')->references('id')->on('clientes');
            $table->foreign('usuario_id', 'fk_ventas_usuario')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};

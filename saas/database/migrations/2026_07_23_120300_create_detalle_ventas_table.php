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
        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('venta_id');
            $table->unsignedBigInteger('producto_id');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);

            $table->index('tenant_id', 'idx_detalle_tenant');
            $table->index('venta_id', 'idx_detalle_venta');
            $table->foreign('tenant_id', 'fk_detalle_tenant')->references('id')->on('tenants');
            $table->foreign('venta_id', 'fk_detalle_venta')->references('id')->on('ventas');
            $table->foreign('producto_id', 'fk_detalle_producto')->references('id')->on('productos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
    }
};

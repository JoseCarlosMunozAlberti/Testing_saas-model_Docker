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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('nombre', 150);
            $table->decimal('precio', 10, 2);
            $table->integer('stock')->default(0);
            $table->timestamps();

            $table->index('tenant_id', 'idx_productos_tenant');
            $table->foreign('tenant_id', 'fk_productos_tenant')->references('id')->on('tenants');
            $table->unique(['tenant_id', 'nombre'], 'uq_producto_por_tenant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};

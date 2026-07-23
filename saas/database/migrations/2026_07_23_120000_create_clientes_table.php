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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('nombre', 150);
            $table->string('ci', 30)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->timestamps();

            $table->index('tenant_id', 'idx_clientes_tenant');
            $table->foreign('tenant_id', 'fk_clientes_tenant')->references('id')->on('tenants');
            $table->unique(['tenant_id', 'ci'], 'uq_cliente_ci_tenant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};

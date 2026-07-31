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
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('referencia_pago', 64)->nullable()->unique()->after('estado');
            $table->timestamp('pagada_at')->nullable()->after('referencia_pago');
            $table->string('salesforce_id', 64)->nullable()->after('pagada_at');
            $table->string('salesforce_sync_status', 32)->nullable()->after('salesforce_id');
            $table->text('salesforce_sync_error')->nullable()->after('salesforce_sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn([
                'referencia_pago',
                'pagada_at',
                'salesforce_id',
                'salesforce_sync_status',
                'salesforce_sync_error',
            ]);
        });
    }
};

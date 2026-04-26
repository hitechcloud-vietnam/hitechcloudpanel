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
        Schema::table('metrics', function (Blueprint $table): void {
            $table->decimal('cpu_usage', 5, 2)->nullable()->after('load');
            $table->unsignedInteger('cpu_cores')->nullable()->after('cpu_usage');
            $table->decimal('network_upstream', 15, 2)->nullable()->after('disk_free');
            $table->decimal('network_downstream', 15, 2)->nullable()->after('network_upstream');
            $table->decimal('network_total_sent', 20, 2)->nullable()->after('network_downstream');
            $table->decimal('network_total_received', 20, 2)->nullable()->after('network_total_sent');
            $table->decimal('disk_read', 15, 2)->nullable()->after('network_total_received');
            $table->decimal('disk_write', 15, 2)->nullable()->after('disk_read');
            $table->decimal('disk_tps', 10, 2)->nullable()->after('disk_write');
            $table->decimal('io_wait', 5, 2)->nullable()->after('disk_tps');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('metrics', function (Blueprint $table): void {
            $table->dropColumn([
                'cpu_usage',
                'cpu_cores',
                'network_upstream',
                'network_downstream',
                'network_total_sent',
                'network_total_received',
                'disk_read',
                'disk_write',
                'disk_tps',
                'io_wait',
            ]);
        });
    }
};
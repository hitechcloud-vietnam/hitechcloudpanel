<?php

namespace Tests\Unit\Commands;

use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetMetricsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_metrics(): void
    {
        SSH::fake(<<<'EOF'
            load:1
            cpu_usage:10
            cpu_cores:4
            memory_total:1
            memory_used:1
            memory_free:1
            disk_total:1
            disk_used:1
            disk_free:1
            network_upstream:100
            network_downstream:200
            network_total_sent:300
            network_total_received:400
            disk_read:500
            disk_write:600
            disk_tps:700
            io_wait:5
        EOF);

        Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'remote-monitor',
            'type' => 'monitoring',
            'type_data' => [
                'data_retention' => 7,
            ],
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        $this->artisan('metrics:get')
            ->expectsOutput('Checked 1 metrics');

        $this->assertDatabaseHas('metrics', [
            'server_id' => $this->server->id,
            'load' => 1,
            'cpu_usage' => 10,
            'cpu_cores' => 4,
            'memory_total' => 1,
            'memory_used' => 1,
            'memory_free' => 1,
            'disk_total' => 1,
            'disk_used' => 1,
            'disk_free' => 1,
            'network_upstream' => 100,
            'network_downstream' => 200,
            'network_total_sent' => 300,
            'network_total_received' => 400,
            'disk_read' => 500,
            'disk_write' => 600,
            'disk_tps' => 700,
            'io_wait' => 5,
        ]);
    }
}

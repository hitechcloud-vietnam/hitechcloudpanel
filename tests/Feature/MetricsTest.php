<?php

namespace Tests\Feature;

use App\Enums\ServiceStatus;
use App\Models\Metric;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class MetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_visit_metrics(): void
    {
        $this->actingAs($this->user);

        Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'hitechcloudpanel-agent',
            'type' => 'monitoring',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        $this->get(route('monitoring', $this->server))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('monitoring/index'));
    }

    public function test_update_data_retention(): void
    {
        $this->actingAs($this->user);

        Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'hitechcloudpanel-agent',
            'type' => 'monitoring',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        $this->patch(route('monitoring.update', $this->server), [
            'data_retention' => 365,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'type' => 'monitoring',
            'type_data->data_retention' => 365,
        ]);
    }

    public function test_realtime_returns_latest_metric_for_agent_monitoring(): void
    {
        $this->actingAs($this->user);

        Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'hitechcloudpanel-agent',
            'type' => 'monitoring',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        Metric::factory()->create([
            'server_id' => $this->server->id,
            'load' => 1.23,
            'cpu_usage' => 45.67,
            'cpu_cores' => 8,
            'network_total_sent' => 123456,
            'network_total_received' => 654321,
            'disk_tps' => 12.5,
        ]);

        $this->get(route('monitoring.realtime', $this->server))
            ->assertSuccessful()
            ->assertJson([
                'load' => 1.23,
                'cpu_usage' => 45.67,
                'cpu_cores' => 8,
                'network_total_sent' => 123456,
                'network_total_received' => 654321,
                'disk_tps' => 12.5,
            ]);
    }

    public function test_stream_returns_sse_metric_payload_for_agent_monitoring(): void
    {
        $this->actingAs($this->user);

        Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'hitechcloudpanel-agent',
            'type' => 'monitoring',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        Metric::factory()->create([
            'server_id' => $this->server->id,
            'load' => 2.34,
            'cpu_usage' => 55.5,
            'cpu_cores' => 4,
            'network_total_sent' => 777,
            'network_total_received' => 999,
            'disk_tps' => 22.2,
        ]);

        $response = $this->get(route('monitoring.stream', $this->server));

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'text/event-stream; charset=UTF-8');
        $response->assertStreamedContent("retry: 3000\n\nevent: metric\ndata: {\"date\"");
        $response->assertSeeInOrder([
            'event: metric',
            '"cpu_usage":55.5',
            '"network_total_sent":777',
            '"network_total_received":999',
            '"disk_tps":22.2',
        ], false);
    }
}

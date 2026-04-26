<?php

namespace Tests\Feature\API;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_controller_stores_metrics_with_valid_secret(): void
    {
        $service = $this->createAgentService();
        $payload = $this->validPayload();

        $this->withHeader('secret', 'agent-secret')
            ->postJson(route('api.servers.agent', [$this->server, $service->id]), $payload)
            ->assertOk()
            ->assertExactJson([]);

        $this->assertDatabaseHas('metrics', [
            'server_id' => $this->server->id,
            'load' => 1.23,
            'cpu_usage' => 72.4,
            'cpu_cores' => 8,
            'network_total_sent' => 3333.3,
            'network_total_received' => 4444.4,
            'disk_tps' => 12.7,
            'io_wait' => 3.2,
        ]);
    }

    public function test_agent_controller_rejects_invalid_secret(): void
    {
        $service = $this->createAgentService();

        $response = $this->withHeader('secret', 'wrong-secret')
            ->postJson(route('api.servers.agent', [$this->server, $service->id]), $this->requiredPayload());

        $response->assertUnauthorized()
            ->assertJson([
                'error' => 'Unauthorized',
            ]);

        $this->assertDatabaseCount('metrics', 0);
    }

    public function test_agent_controller_validates_missing_required_fields(): void
    {
        $service = $this->createAgentService();

        $response = $this->withHeader('secret', 'agent-secret')
            ->postJson(route('api.servers.agent', [$this->server, $service->id]), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'load',
                'memory_total',
                'memory_used',
                'memory_free',
                'disk_total',
                'disk_used',
                'disk_free',
            ]);

        $this->assertDatabaseCount('metrics', 0);
    }

    private function createAgentService(): Service
    {
        return Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'hitechcloudpanel-agent',
            'type' => 'monitoring',
            'version' => 'latest',
            'type_data' => [
                'url' => '',
                'secret' => 'agent-secret',
                'data_retention' => 10,
            ],
        ]);
    }

    /**
     * @return array<string, int|float>
     */
    private function validPayload(): array
    {
        return [
            'load' => 1.23,
            'cpu_usage' => 72.4,
            'cpu_cores' => 8,
            'memory_total' => 16384,
            'memory_used' => 8192,
            'memory_free' => 8192,
            'disk_total' => 512000,
            'disk_used' => 256000,
            'disk_free' => 256000,
            'network_upstream' => 1000.5,
            'network_downstream' => 2000.5,
            'network_total_sent' => 3333.3,
            'network_total_received' => 4444.4,
            'disk_read' => 150.2,
            'disk_write' => 250.3,
            'disk_tps' => 12.7,
            'io_wait' => 3.2,
        ];
    }

    /**
     * @return array<string, int|float>
     */
    private function requiredPayload(): array
    {
        return [
            'load' => 1.23,
            'memory_total' => 16384,
            'memory_used' => 8192,
            'memory_free' => 8192,
            'disk_total' => 512000,
            'disk_used' => 256000,
            'disk_free' => 256000,
        ];
    }
}
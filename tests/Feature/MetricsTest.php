<?php

namespace Tests\Feature;

use App\Enums\ServiceStatus;
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
            'name' => 'vito-agent',
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
            'name' => 'vito-agent',
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
}

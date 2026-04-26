<?php

namespace Tests\Unit\Actions\Service;

use App\Actions\Service\Install;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InstallTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_hitechcloudpanel_agent(): void
    {
        $sshFake = SSH::fake('Active: active');
        Http::fake([
            'https://api.github.com/repos/hitechcloud-vietnam/agent/tags' => Http::response([['name' => '0.1.5']]),
        ]);

        $this->server->monitoring()?->delete();

        app(Install::class)->install($this->server, [
            'type' => 'monitoring',
            'name' => 'hitechcloudpanel-agent',
            'version' => 'latest',
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'name' => 'hitechcloudpanel-agent',
            'type' => 'monitoring',
            'version' => '0.1.5',
            'status' => ServiceStatus::READY,
        ]);

        $service = $this->server->fresh()->monitoring();

        $this->assertNotNull($service);
        $this->assertSame(route('api.servers.agent', [$this->server, $service->id]), $service->type_data['url']);
        $this->assertNotEmpty($service->type_data['secret']);

        $sshFake->assertExecutedContains('wget -O ./hitechcloudpanel-linux-');
        $sshFake->assertExecutedContains('/etc/systemd/system/hitechcloudpanel-agent.service');
        $sshFake->assertExecutedContains('/etc/hitechcloudpanel-agent/config.json');
        $sshFake->assertExecutedContains((string) $service->type_data['url']);
        $sshFake->assertExecutedContains((string) $service->type_data['secret']);
    }

    public function test_install_hitechcloudpanel_agent_stores_generated_endpoint_and_secret(): void
    {
        SSH::fake('Active: active');
        Http::fake([
            'https://api.github.com/repos/hitechcloud-vietnam/agent/tags' => Http::response([['name' => 'v0.1.5']]),
        ]);

        $this->server->monitoring()?->delete();

        $service = app(Install::class)->install($this->server, [
            'type' => 'monitoring',
            'name' => 'hitechcloudpanel-agent',
            'version' => 'latest',
        ]);

        $service->refresh();

        $this->assertSame('v0.1.5', $service->version);
        $this->assertSame(route('api.servers.agent', [$this->server, $service->id]), $service->type_data['url']);
        $this->assertIsString($service->type_data['secret']);
        $this->assertNotSame('', $service->type_data['secret']);
        $this->assertSame(10, $service->type_data['data_retention']);
    }

    public function test_install_hitechcloudpanel_agent_failed(): void
    {
        $this->server->monitoring()?->delete();
        SSH::fake('Active: inactive');
        Http::fake([
            'https://api.github.com/repos/hitechcloud-vietnam/agent/tags' => Http::response([]),
        ]);

        $service = app(Install::class)->install($this->server, [
            'type' => 'monitoring',
            'name' => 'hitechcloudpanel-agent',
            'version' => 'latest',
        ]);

        // Wait for the job to complete and check the service status
        $service->refresh();
        $this->assertEquals(ServiceStatus::INSTALLATION_FAILED, $service->status);
    }

    public function test_install_nginx(): void
    {
        $this->server->webserver()->delete();

        SSH::fake('Active: active');

        app(Install::class)->install($this->server, [
            'type' => 'webserver',
            'name' => 'nginx',
            'version' => 'latest',
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'name' => 'nginx',
            'type' => 'webserver',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);
    }

    public function test_install_caddy(): void
    {
        $this->server->webserver()->delete();

        SSH::fake('Active: active');

        app(Install::class)->install($this->server, [
            'type' => 'webserver',
            'name' => 'caddy',
            'version' => 'latest',
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'name' => 'caddy',
            'type' => 'webserver',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);
    }

    public function test_install_mysql(): void
    {
        $this->server->database()->delete();

        SSH::fake('Active: active');

        app(Install::class)->install($this->server, [
            'type' => 'database',
            'name' => 'mysql',
            'version' => '8.0',
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'name' => 'mysql',
            'type' => 'database',
            'version' => '8.0',
            'status' => ServiceStatus::READY,
        ]);
    }

    public function test_install_mysql_failed(): void
    {
        $this->expectException(ValidationException::class);
        app(Install::class)->install($this->server, [
            'type' => 'database',
            'name' => 'mysql',
            'version' => '8.0',
        ]);
    }

    public function test_install_supervisor(): void
    {
        $this->server->processManager()->delete();

        SSH::fake('Active: active');

        app(Install::class)->install($this->server, [
            'type' => 'process_manager',
            'name' => 'supervisor',
            'version' => 'latest',
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'name' => 'supervisor',
            'type' => 'process_manager',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);
    }

    public function test_install_redis(): void
    {
        $this->server->memoryDatabase()->delete();

        SSH::fake('Active: active');

        app(Install::class)->install($this->server, [
            'type' => 'memory_database',
            'name' => 'redis',
            'version' => 'latest',
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'name' => 'redis',
            'type' => 'memory_database',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);
    }
}

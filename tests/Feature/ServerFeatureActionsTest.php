<?php

namespace Tests\Feature;

use App\Facades\SSH;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerFeatureActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleanup_server_feature_runs_cleanup_command(): void
    {
        SSH::fake();

        $this->actingAs($this->user)
            ->post(route('server-features.action', [
                'server' => $this->server,
                'feature' => 'system-maintenance',
                'action' => 'cleanup',
            ]))
            ->assertRedirect();

        SSH::assertExecutedContains('apt-get autoremove -y');
    }

    public function test_fix_apt_lock_server_feature_runs_recovery_commands(): void
    {
        SSH::fake();

        $this->actingAs($this->user)
            ->post(route('server-features.action', [
                'server' => $this->server,
                'feature' => 'system-maintenance',
                'action' => 'fix-apt-lock',
            ]))
            ->assertRedirect();

        SSH::assertExecutedContains('dpkg --configure -a');
        SSH::assertExecutedContains('apt-get -f install -y');
    }

    public function test_update_hostname_server_feature_runs_hostnamectl_command(): void
    {
        SSH::fake();

        $this->actingAs($this->user)
            ->post(route('server-features.action', [
                'server' => $this->server,
                'feature' => 'hostname',
                'action' => 'update',
            ]), [
                'hostname' => 'production-app-01',
            ])
            ->assertRedirect();

        SSH::assertExecutedContains('hostnamectl set-hostname production-app-01');
        SSH::assertExecutedContains('127.0.1.1\tproduction-app-01');
    }
}

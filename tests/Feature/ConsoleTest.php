<?php

namespace Tests\Feature;

use App\Facades\SSH;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ConsoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_run(): void
    {
        SSH::fake('fake output');

        $this->actingAs($this->user);

        $this->post(route('console.run', $this->server), [
            'user' => 'hitechcloudpanel',
            'command' => 'ls -la',
        ])->assertStreamedContent('fake output');
    }

    public function test_run_validation_error(): void
    {
        $this->actingAs($this->user);

        $this->post(route('console.run', $this->server), [
            'user' => 'hitechcloudpanel',
        ])->assertSessionHasErrors('command');

        $this->post(route('console.run', $this->server), [
            'command' => 'ls -la',
        ])->assertSessionHasErrors('user');
    }

    public function test_working_dir_is_scoped_per_user(): void
    {
        Cache::put('console.'.$this->server->id.'.root.dir', '/root/projects');
        Cache::put('console.'.$this->server->id.'.'.$this->server->getSshUser().'.dir', home_path($this->server->getSshUser()).'/app');

        $this->actingAs($this->user);

        $this->get(route('console.working-dir', [
            'server' => $this->server,
            'user' => 'root',
        ]))->assertJsonPath('dir', '/root/projects');

        $this->get(route('console.working-dir', [
            'server' => $this->server,
            'user' => $this->server->getSshUser(),
        ]))->assertJsonPath('dir', home_path($this->server->getSshUser()).'/app');
    }
}

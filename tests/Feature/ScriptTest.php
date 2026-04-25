<?php

namespace Tests\Feature;

use App\Enums\ScriptExecutionStatus;
use App\Facades\SSH;
use App\Models\Script;
use App\Models\ScriptExecution;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ScriptTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_scripts(): void
    {
        $this->actingAs($this->user);

        Script::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->get(route('scripts'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('scripts/index'));
    }

    public function test_create_script(): void
    {
        $this->actingAs($this->user);

        $this->post(route('scripts.store'), [
            'name' => 'Test Script',
            'content' => 'echo "Hello, World!"',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('scripts', [
            'name' => 'Test Script',
            'content' => 'echo "Hello, World!"',
        ]);
    }

    public function test_edit_script(): void
    {
        $this->actingAs($this->user);

        $script = Script::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->put(route('scripts.update', $script), [
            'name' => 'New Name',
            'content' => 'echo "Hello, new World!"',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('scripts', [
            'id' => $script->id,
            'name' => 'New Name',
            'content' => 'echo "Hello, new World!"',
        ]);
    }

    public function test_delete_script(): void
    {
        $this->actingAs($this->user);

        $script = Script::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $scriptExecution = ScriptExecution::factory()->create([
            'script_id' => $script->id,
            'status' => ScriptExecutionStatus::EXECUTING,
        ]);

        $this->delete(route('scripts.destroy', $script->id));

        $this->assertDatabaseMissing('scripts', [
            'id' => $script->id,
        ]);

        $this->assertDatabaseMissing('script_executions', [
            'id' => $scriptExecution->id,
        ]);
    }

    public function test_execute_script_and_view_log(): void
    {
        SSH::fake('script output');

        $this->actingAs($this->user);

        $script = Script::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->post(route('scripts.execute', $script), [
            'server' => $this->server->id,
            'user' => 'root',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('script_executions', [
            'script_id' => $script->id,
            'status' => ScriptExecutionStatus::COMPLETED,
            'user' => 'root',
        ]);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
        ]);

        $this->get(route('scripts.show', $script))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('scripts/show'));
    }

    public function test_execute_script_as_isolated_user(): void
    {
        SSH::fake('script output');

        $this->actingAs($this->user);

        $script = Script::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'example',
        ]);

        $this->post(route('scripts.execute', $script), [
            'server' => $this->server->id,
            'user' => 'example',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('script_executions', [
            'script_id' => $script->id,
            'status' => ScriptExecutionStatus::COMPLETED,
            'user' => 'example',
        ]);
    }

    public function test_cannot_execute_script_as_non_existing_user(): void
    {
        $this->actingAs($this->user);

        $script = Script::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->post(route('scripts.execute', $script), [
            'server' => $this->server->id,
            'user' => 'example',
        ])
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('script_executions', [
            'script_id' => $script->id,
            'user' => 'example',
        ]);
    }

    public function test_cannot_execute_script_as_user_not_on_server(): void
    {
        $this->actingAs($this->user);

        $script = Script::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Site::factory()->create([
            'server_id' => Server::factory()->create(['user_id' => 1])->id,
            'user' => 'example',
        ]);

        $this->post(route('scripts.execute', $script), [
            'server' => $this->server->id,
            'user' => 'example',
        ])
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('script_executions', [
            'script_id' => $script->id,
            'user' => 'example',
        ]);
    }
}

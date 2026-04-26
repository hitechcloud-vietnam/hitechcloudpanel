<?php

namespace Tests\Feature;

use App\Facades\SSH;
use App\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class FileManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_file_manager_page(): void
    {
        $this->actingAs($this->user);

        SSH::fake(<<<'OUT'
total 8
drwxr-xr-x  2 deploy deploy 4096 Jan 01 10:00 .
drwxr-xr-x 10 deploy deploy 4096 Jan 01 10:00 ..
-rw-r--r--  1 deploy deploy  120 Jan 01 10:00 .env
-rw-r--r--  1 deploy deploy  512 Jan 01 10:00 index.php
drwxr-xr-x  3 deploy deploy 4096 Jan 01 10:00 storage
OUT);

        $this->get(route('server-files', ['server' => $this->server]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('files/index')
                ->where('serverUser', $this->server->getSshUser())
                ->where('currentPath', home_path($this->server->getSshUser()))
                ->has('files.data', 3)
            );
    }

    public function test_show_file_content(): void
    {
        $this->actingAs($this->user);

        SSH::fake('APP_NAME=HiTechCloudPanel');

        $this->get(route('server-files.content', [
            'server' => $this->server,
            'path' => '/home/'. $this->server->getSshUser() .'/.env',
        ]))
            ->assertSuccessful()
            ->assertJsonPath('content', 'APP_NAME=HiTechCloudPanel');
    }

    public function test_create_directory(): void
    {
        $this->actingAs($this->user);

        $sshFake = SSH::fake();

        $this->post(route('server-files.directories.store', ['server' => $this->server]), [
            'server_user' => $this->server->getSshUser(),
            'path' => '/home/'.$this->server->getSshUser(),
            'name' => 'releases',
        ])->assertSessionDoesntHaveErrors();

        $sshFake->assertExecutedContains('mkdir -p /home/'.$this->server->getSshUser().'/releases');
    }

    public function test_create_and_update_file(): void
    {
        $this->actingAs($this->user);

        $sshFake = SSH::fake();

        $this->post(route('server-files.store', ['server' => $this->server]), [
            'server_user' => $this->server->getSshUser(),
            'path' => '/home/'.$this->server->getSshUser(),
            'name' => 'notes.txt',
            'content' => 'initial content',
        ])->assertSessionDoesntHaveErrors();

        $sshFake->assertExecutedContains('/home/'.$this->server->getSshUser().'/notes.txt');

        $this->patch(route('server-files.update', ['server' => $this->server]), [
            'server_user' => $this->server->getSshUser(),
            'path' => '/home/'.$this->server->getSshUser().'/notes.txt',
            'content' => 'updated content',
        ])->assertSessionDoesntHaveErrors();

        $sshFake->assertExecutedContains('/home/'.$this->server->getSshUser().'/notes.txt');
    }

    public function test_delete_file(): void
    {
        $this->actingAs($this->user);

        $sshFake = SSH::fake();

        $file = File::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'server_user' => $this->server->getSshUser(),
            'path' => '/home/'.$this->server->getSshUser(),
            'type' => 'file',
            'name' => 'notes.txt',
        ]);

        $this->delete(route('server-files.destroy', [
            'server' => $this->server,
            'file' => $file,
        ]))->assertSessionDoesntHaveErrors();

        $sshFake->assertExecutedContains('rm -rf /home/'.$this->server->getSshUser().'/notes.txt');
        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }

    public function test_create_directory_validates_required_fields(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $this->post(route('server-files.directories.store', ['server' => $this->server]), [
            'server_user' => $this->server->getSshUser(),
            'path' => '/home/'.$this->server->getSshUser(),
            'name' => '',
        ])->assertSessionHasErrors(['name']);
    }

    public function test_create_directory_rejects_path_outside_selected_user_home(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $this->post(route('server-files.directories.store', ['server' => $this->server]), [
            'server_user' => $this->server->getSshUser(),
            'path' => '/etc',
            'name' => 'restricted',
        ])->assertSessionHasErrors(['path']);
    }
}

<?php

namespace Tests\Feature;

use App\Facades\SSH;
use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class FileManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_file_manager_page(): void
    {
        $this->actingAs($this->user);

        SSH::fake()->withDirectoryEntries([
            [
                'path' => '/home/'.$this->server->getSshUser(),
                'type' => 'file',
                'name' => '.env',
                'size' => 120,
                'links' => 1,
                'owner' => 'deploy',
                'group' => 'deploy',
                'date' => 'Jan 01 10:00',
                'permissions' => '-rw-r--r--',
            ],
            [
                'path' => '/home/'.$this->server->getSshUser(),
                'type' => 'file',
                'name' => 'index.php',
                'size' => 512,
                'links' => 1,
                'owner' => 'deploy',
                'group' => 'deploy',
                'date' => 'Jan 01 10:00',
                'permissions' => '-rw-r--r--',
            ],
            [
                'path' => '/home/'.$this->server->getSshUser(),
                'type' => 'directory',
                'name' => 'storage',
                'size' => 4096,
                'links' => 3,
                'owner' => 'deploy',
                'group' => 'deploy',
                'date' => 'Jan 01 10:00',
                'permissions' => 'drwxr-xr-x',
            ],
        ]);

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

        SSH::fake()->withRemoteFile('/home/'.$this->server->getSshUser().'/.env', 'APP_NAME=HiTechCloudPanel');

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

        $sshFake->assertExecutedContains('sftp:mkdir /home/'.$this->server->getSshUser().'/releases');
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

        $sshFake->assertExecutedContains('sftp:write /home/'.$this->server->getSshUser().'/notes.txt');

        $this->patch(route('server-files.update', ['server' => $this->server]), [
            'server_user' => $this->server->getSshUser(),
            'path' => '/home/'.$this->server->getSshUser().'/notes.txt',
            'content' => 'updated content',
        ])->assertSessionDoesntHaveErrors();

        $sshFake->assertExecutedContains('sftp:write /home/'.$this->server->getSshUser().'/notes.txt');
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

        $sshFake->assertExecutedContains('sftp:delete /home/'.$this->server->getSshUser().'/notes.txt');
        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }

    public function test_update_file_returns_json_for_editor_save(): void
    {
        $this->actingAs($this->user);

        $sshFake = SSH::fake();

        $this->patchJson(route('server-files.update', ['server' => $this->server]), [
            'server_user' => $this->server->getSshUser(),
            'path' => '/home/'.$this->server->getSshUser().'/notes.txt',
            'content' => 'updated content',
        ])
            ->assertSuccessful()
            ->assertJson([
                'status' => 'ok',
            ]);

        $sshFake->assertExecutedContains('sftp:write /home/'.$this->server->getSshUser().'/notes.txt');
    }

    public function test_rename_file(): void
    {
        $this->actingAs($this->user);

        $sshFake = SSH::fake();

        File::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'server_user' => $this->server->getSshUser(),
            'path' => '/home/'.$this->server->getSshUser(),
            'type' => 'file',
            'name' => 'notes.txt',
        ]);

        $this->patch(route('server-files.rename', ['server' => $this->server]), [
            'server_user' => $this->server->getSshUser(),
            'path' => '/home/'.$this->server->getSshUser().'/notes.txt',
            'name' => 'notes-renamed.txt',
        ])->assertSessionDoesntHaveErrors();

        $sshFake->assertExecutedContains(
            'sftp:rename /home/'.$this->server->getSshUser().'/notes.txt /home/'.$this->server->getSshUser().'/notes-renamed.txt'
        );

        $this->assertDatabaseHas('files', [
            'server_id' => $this->server->id,
            'server_user' => $this->server->getSshUser(),
            'path' => '/home/'.$this->server->getSshUser(),
            'name' => 'notes-renamed.txt',
        ]);
    }

    public function test_rename_file_returns_json_response(): void
    {
        $this->actingAs($this->user);

        $sshFake = SSH::fake();

        $this->patchJson(route('server-files.rename', ['server' => $this->server]), [
            'server_user' => $this->server->getSshUser(),
            'path' => '/home/'.$this->server->getSshUser().'/notes.txt',
            'name' => 'notes-renamed.txt',
        ])
            ->assertSuccessful()
            ->assertJson([
                'status' => 'ok',
                'path' => '/home/'.$this->server->getSshUser().'/notes-renamed.txt',
            ]);

        $sshFake->assertExecutedContains(
            'sftp:rename /home/'.$this->server->getSshUser().'/notes.txt /home/'.$this->server->getSshUser().'/notes-renamed.txt'
        );
    }

    public function test_upload_file(): void
    {
        $this->actingAs($this->user);

        $sshFake = SSH::fake();

        $this->post(route('server-files.upload', ['server' => $this->server]), [
            'server_user' => $this->server->getSshUser(),
            'path' => '/home/'.$this->server->getSshUser(),
            'file' => UploadedFile::fake()->createWithContent('deploy.log', 'deploy output'),
        ])->assertSessionDoesntHaveErrors();

        $sshFake->assertExecutedContains('sftp:upload /home/'.$this->server->getSshUser().'/deploy.log');
    }

    public function test_move_file(): void
    {
        $this->actingAs($this->user);

        $sshFake = SSH::fake();

        File::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'server_user' => $this->server->getSshUser(),
            'path' => '/home/'.$this->server->getSshUser(),
            'type' => 'file',
            'name' => 'notes.txt',
        ]);

        $this->patch(route('server-files.move', ['server' => $this->server]), [
            'server_user' => $this->server->getSshUser(),
            'path' => '/home/'.$this->server->getSshUser().'/notes.txt',
            'destination' => '/home/'.$this->server->getSshUser().'/releases',
        ])->assertSessionDoesntHaveErrors();

        $sshFake->assertExecutedContains(
            'sftp:rename /home/'.$this->server->getSshUser().'/notes.txt /home/'.$this->server->getSshUser().'/releases/notes.txt'
        );

        $this->assertDatabaseHas('files', [
            'server_id' => $this->server->id,
            'server_user' => $this->server->getSshUser(),
            'path' => '/home/'.$this->server->getSshUser().'/releases',
            'name' => 'notes.txt',
        ]);
    }

    public function test_preview_text_file(): void
    {
        $this->actingAs($this->user);

        SSH::fake()->withRemoteFile('/home/'.$this->server->getSshUser().'/notes.txt', 'hello world');

        $this->get(route('server-files.preview', [
            'server' => $this->server,
            'path' => '/home/'.$this->server->getSshUser().'/notes.txt',
        ]))
            ->assertSuccessful()
            ->assertHeader('content-type', 'text/plain; charset=UTF-8');
    }

    public function test_download_file(): void
    {
        $this->actingAs($this->user);

        SSH::fake()->withRemoteFile('/home/'.$this->server->getSshUser().'/notes.txt', 'download me');

        $this->get(route('server-files.download', [
            'server' => $this->server,
            'path' => '/home/'.$this->server->getSshUser().'/notes.txt',
        ]))
            ->assertSuccessful()
            ->assertHeader('content-disposition', 'attachment; filename="notes.txt"');
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

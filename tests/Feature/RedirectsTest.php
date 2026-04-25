<?php

namespace Tests\Feature;

use App\Enums\RedirectStatus;
use App\Facades\SSH;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RedirectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_redirects(): void
    {
        $this->actingAs($this->user);

        Redirect::factory()->create([
            'site_id' => $this->site->id,
        ]);

        $this->get(route('redirects', [
            'server' => $this->server,
            'site' => $this->site,
        ]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('redirects/index'));

    }

    public function test_delete_redirect(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $redirect = Redirect::factory()->create([
            'site_id' => $this->site->id,
        ]);

        $this->delete(route('redirects.destroy', [
            'server' => $this->server,
            'site' => $this->site,
            'redirect' => $redirect,
        ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('redirects', [
            'id' => $redirect->id,
        ]);
    }

    public function test_create_redirect(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('redirects.store', [
            'server' => $this->server,
            'site' => $this->site,
        ]), [
            'from' => 'some-path',
            'to' => 'https://example.com/redirect',
            'mode' => 301,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('redirects', [
            'from' => 'some-path',
            'to' => 'https://example.com/redirect',
            'mode' => 301,
            'status' => RedirectStatus::READY,
        ]);
    }
}

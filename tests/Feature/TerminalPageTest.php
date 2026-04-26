<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TerminalPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_terminal_page(): void
    {
        $this->actingAs($this->user);

        $this->get(route('server-terminal', ['server' => $this->server]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('terminal/index')
                ->where('defaultUser', 'root')
            );
    }
}
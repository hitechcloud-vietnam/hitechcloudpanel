<?php

namespace Tests\Feature;

use App\Facades\SSH;
use App\SiteTypes\Wordpress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WordpressFeatureActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_wordpress_auto_login_feature_creates_mu_plugin(): void
    {
        SSH::fake();

        $this->site->update([
            'type' => Wordpress::id(),
            'type_data' => [],
        ]);

        $this->actingAs($this->user)
            ->post(route('site-features.action', [
                'server' => $this->server,
                'site' => $this->site,
                'feature' => 'wordpress-management',
                'action' => 'install-auto-login',
            ]))
            ->assertRedirect();

        $this->site->refresh();

        $this->assertTrue($this->site->type_data['wordpress_auto_login_enabled']);
        SSH::assertExecutedContains('hitechcloudpanel-auto-login.php');
        SSH::assertExecutedContains('wp-content/mu-plugins');
    }

    public function test_uninstall_wordpress_auto_login_feature_removes_mu_plugin(): void
    {
        SSH::fake();

        $this->site->update([
            'type' => Wordpress::id(),
            'type_data' => [
                'wordpress_auto_login_enabled' => true,
            ],
        ]);

        $this->actingAs($this->user)
            ->post(route('site-features.action', [
                'server' => $this->server,
                'site' => $this->site,
                'feature' => 'wordpress-management',
                'action' => 'uninstall-auto-login',
            ]))
            ->assertRedirect();

        $this->site->refresh();

        $this->assertFalse($this->site->type_data['wordpress_auto_login_enabled']);
        SSH::assertExecutedContains('rm -f');
        SSH::assertExecutedContains('hitechcloudpanel-auto-login.php');
    }

    public function test_update_wordpress_timezone_feature_updates_wp_option(): void
    {
        SSH::fake();

        $this->site->update([
            'type' => Wordpress::id(),
            'type_data' => [],
        ]);

        $this->actingAs($this->user)
            ->post(route('site-features.action', [
                'server' => $this->server,
                'site' => $this->site,
                'feature' => 'wordpress-management',
                'action' => 'update-timezone',
            ]), [
                'timezone' => 'Asia/Ho_Chi_Minh',
            ])
            ->assertRedirect();

        $this->site->refresh();

        $this->assertSame('Asia/Ho_Chi_Minh', $this->site->type_data['wordpress_timezone']);
        SSH::assertExecutedContains("option update timezone_string 'Asia/Ho_Chi_Minh'");
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HitechcloudpanelSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_settings(): void
    {
        $this->actingAs($this->user);

        $this->get(route('hitechcloudpanel-settings.export'))
            ->assertDownload('hitechcloudpanel-backup-'.date('Y-m-d').'.zip');
    }
}

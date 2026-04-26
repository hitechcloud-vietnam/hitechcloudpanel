<?php

namespace Tests\Unit\Plugins;

use App\Plugins\RegisterServerFeature;
use App\Plugins\RegisterServerFeatureAction;
use App\Plugins\RegisterSiteFeature;
use App\Plugins\RegisterSiteFeatureAction;
use App\SiteTypes\Laravel;
use RuntimeException;
use Tests\TestCase;

class RegisterFeatureTest extends TestCase
{
    public function test_register_server_feature_is_idempotent_for_same_definition(): void
    {
        RegisterServerFeature::make('system-maintenance')
            ->label('System Maintenance')
            ->description('Run safe maintenance tasks for package management and temporary files')
            ->register();

        $this->assertSame('System Maintenance', config('server.features.system-maintenance.label'));
    }

    public function test_register_server_feature_still_throws_for_conflicting_definition(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Feature 'system-maintenance' already exists");

        RegisterServerFeature::make('system-maintenance')
            ->label('Different Label')
            ->description('Different description')
            ->register();
    }

    public function test_register_server_feature_action_is_idempotent_for_same_definition(): void
    {
        RegisterServerFeatureAction::make('system-maintenance', 'cleanup')
            ->label('Cleanup System')
            ->handler('App\\ServerFeatures\\System\\CleanupServer')
            ->register();

        $this->assertSame('Cleanup System', config('server.features.system-maintenance.actions.cleanup.label'));
    }

    public function test_register_server_feature_action_still_throws_for_conflicting_definition(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Action 'cleanup' already exists for feature 'system-maintenance'");

        RegisterServerFeatureAction::make('system-maintenance', 'cleanup')
            ->label('Cleanup')
            ->handler('Different\\Handler')
            ->register();
    }

    public function test_register_site_feature_is_idempotent_for_same_definition(): void
    {
        RegisterSiteFeature::make(Laravel::id(), 'modern-deployment')
            ->label('Modern Deployment (beta)')
            ->description('Enables zero downtime deployment and deployment rollbacks')
            ->register();

        $this->assertSame('Modern Deployment (beta)', config('site.types.laravel.features.modern-deployment.label'));
    }

    public function test_register_site_feature_action_is_idempotent_for_same_definition(): void
    {
        RegisterSiteFeatureAction::make(Laravel::id(), 'modern-deployment', 'enable')
            ->label('Enable')
            ->handler('App\\SiteFeatures\\ModernDeployment\\Enable')
            ->register();

        $this->assertSame('Enable', config('site.types.laravel.features.modern-deployment.actions.enable.label'));
    }
}
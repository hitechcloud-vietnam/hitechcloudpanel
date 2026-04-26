<?php

namespace App\Providers;

use App\Plugins\RegisterServerFeature;
use App\Plugins\RegisterServerFeatureAction;
use App\ServerFeatures\System\CleanupServer;
use App\ServerFeatures\System\FixAptLock;
use App\ServerFeatures\System\UpdateHostname;
use Illuminate\Support\ServiceProvider;

class ServerFeatureServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->systemMaintenance();
        $this->hostname();
    }

    private function systemMaintenance(): void
    {
        RegisterServerFeature::make('system-maintenance')
            ->label('System Maintenance')
            ->description('Run safe maintenance tasks for package management and temporary files')
            ->register();

        RegisterServerFeatureAction::make('system-maintenance', 'cleanup')
            ->label('Cleanup System')
            ->handler(CleanupServer::class)
            ->register();

        RegisterServerFeatureAction::make('system-maintenance', 'fix-apt-lock')
            ->label('Fix APT Lock')
            ->handler(FixAptLock::class)
            ->register();
    }

    private function hostname(): void
    {
        RegisterServerFeature::make('hostname')
            ->label('Hostname')
            ->description('Update the remote hostname for this server')
            ->register();

        RegisterServerFeatureAction::make('hostname', 'update')
            ->label('Update Hostname')
            ->handler(UpdateHostname::class)
            ->register();
    }
}

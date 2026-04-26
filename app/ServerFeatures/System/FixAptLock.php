<?php

namespace App\ServerFeatures\System;

use App\Exceptions\SSHError;
use App\ServerFeatures\Action;
use Illuminate\Http\Request;

class FixAptLock extends Action
{
    public function name(): string
    {
        return 'Fix APT Lock';
    }

    public function active(): bool
    {
        return true;
    }

    /**
     * @throws SSHError
     */
    public function handle(Request $request): void
    {
        $this->server->ssh()->exec(
            view('ssh.server-features.system.fix-apt-lock'),
            'fix-apt-lock'
        );

        $request->session()->flash('success', 'APT lock cleanup completed successfully.');
    }
}

<?php

namespace App\ServerFeatures\System;

use App\Exceptions\SSHError;
use App\ServerFeatures\Action;
use Illuminate\Http\Request;

class CleanupServer extends Action
{
    public function name(): string
    {
        return 'Cleanup System';
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
        $this->server->os()->cleanup();

        $request->session()->flash('success', 'System cleanup started successfully.');
    }
}

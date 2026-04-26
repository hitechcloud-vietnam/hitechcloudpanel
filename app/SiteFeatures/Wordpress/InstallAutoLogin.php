<?php

namespace App\SiteFeatures\Wordpress;

use App\Exceptions\SSHError;
use App\SiteFeatures\Action;
use Illuminate\Http\Request;

class InstallAutoLogin extends Action
{
    public function name(): string
    {
        return 'Install Auto Login';
    }

    public function active(): bool
    {
        return ! data_get($this->site->type_data, 'wordpress_auto_login_enabled', false);
    }

    /**
     * @throws SSHError
     */
    public function handle(Request $request): void
    {
        $this->site->server->ssh($this->site->user)->exec(
            view('ssh.site-features.wordpress.install-auto-login', [
                'path' => $this->site->path,
            ]),
            'wordpress-install-auto-login',
            $this->site->id
        );

        $typeData = $this->site->type_data ?? [];
        $typeData['wordpress_auto_login_enabled'] = true;
        $this->site->type_data = $typeData;
        $this->site->save();

        $request->session()->flash('success', 'WordPress auto login installed successfully.');
    }
}

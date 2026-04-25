<?php

namespace App\Http\Controllers;

use App\Actions\Server\EditServer;
use App\Models\Server;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('servers/{server}/settings')]
#[Middleware(['auth', 'has-project'])]
class ServerSettingController extends Controller
{
    #[Get('/', name: 'server-settings')]
    public function index(Server $server): Response
    {
        $this->authorize('view', $server);

        return Inertia::render('server-settings/index');
    }

    #[Patch('update', name: 'server-settings.update')]
    public function update(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('update', $server);

        app(EditServer::class)->edit($server, $request->input());

        return back()->with('success', 'Changes saved successfully.');
    }
}

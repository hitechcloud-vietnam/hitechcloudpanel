<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('servers/{server}/terminal')]
#[Middleware(['auth', 'has-project'])]
class TerminalController extends Controller
{
    #[Get('/', name: 'server-terminal')]
    public function __invoke(Server $server): Response
    {
        $this->authorize('view', $server);

        return Inertia::render('terminal/index', [
            'defaultUser' => 'root',
        ]);
    }
}
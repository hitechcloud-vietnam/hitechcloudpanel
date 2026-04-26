<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Prefix('servers/{server}/console')]
#[Middleware(['auth', 'has-project'])]
class ConsoleController extends Controller
{
    private function cacheKey(Server $server, string $user): string
    {
        return 'console.'.$server->id.'.'.$user.'.dir';
    }

    #[Post('/run', name: 'console.run')]
    public function run(Server $server, Request $request): StreamedResponse
    {
        $this->authorize('update', $server);

        $this->validate($request, [
            'user' => [
                'required',
                Rule::in($server->getSshUsers()),
            ],
            'command' => 'required|string',
        ]);

        $user = $request->string('user')->toString();
        $ssh = $server->ssh($user);
        $log = 'console-'.time();

        $currentDir = Cache::get($this->cacheKey($server, $user), home_path($user));

        return response()->stream(
            function () use ($server, $request, $ssh, $log, $currentDir, $user): void {
                $command = 'cd '.$currentDir.' && '.$request->command.' && echo -n "HITECHCLOUDPANEL_WORKING_DIR: " && pwd';
                $output = '';
                $ssh->exec(command: $command, log: $log, stream: true, streamCallback: function (string $out) use (&$output): void {
                    echo preg_replace('/^HITECHCLOUDPANEL_WORKING_DIR:.*(\r?\n)?/m', '', $out);
                    $output .= $out;
                    ob_flush();
                    flush();
                });
                // extract the working dir and put it in the session
                if (preg_match('/HITECHCLOUDPANEL_WORKING_DIR: (.*)/', $output, $matches)) {
                    Cache::put($this->cacheKey($server, $user), $matches[1]);
                }
            },
            200,
            [
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
                'Content-Type' => 'text/event-stream',
            ]
        );
    }

    #[Get('/working-dir', name: 'console.working-dir')]
    public function workingDir(Server $server, Request $request): JsonResponse
    {
        $this->authorize('view', $server);

        $validated = $request->validate([
            'user' => ['nullable', Rule::in($server->getSshUsers())],
        ]);

        $user = $validated['user'] ?? $server->getSshUser();

        return response()->json([
            'dir' => Cache::get($this->cacheKey($server, $user), home_path($user)),
        ]);
    }

    #[Get('/new-session', name: 'console.new-session')]
    public function newSession(Server $server, Request $request): JsonResponse
    {
        $this->authorize('update', $server);

        $validated = $request->validate([
            'user' => ['nullable', Rule::in($server->getSshUsers())],
            'dir' => ['nullable', 'string'],
        ]);

        $user = $validated['user'] ?? $server->getSshUser();
        $cacheKey = $this->cacheKey($server, $user);

        Cache::forget($cacheKey);

        if (isset($validated['dir']) && $validated['dir'] !== '') {
            Cache::put($cacheKey, $validated['dir']);
        }

        return response()->json(['status' => 'ok']);
    }
}

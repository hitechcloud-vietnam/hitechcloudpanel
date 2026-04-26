<?php

namespace App\Http\Controllers;

use App\Http\Resources\FileResource;
use App\Models\File;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('servers/{server}/files')]
#[Middleware(['auth', 'has-project'])]
class FileController extends Controller
{
    #[Get('/', name: 'server-files')]
    public function index(Request $request, Server $server): Response
    {
        $this->authorize('view', $server);

        $serverUser = $this->resolveServerUser($request, $server);
        $path = $this->resolvePath($request->string('path')->toString(), $serverUser);

        $this->syncFiles($request, $server, $serverUser, $path);

        $query = File::query()
            ->where('user_id', $request->user()->id)
            ->where('server_id', $server->id)
            ->where('server_user', $serverUser);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        $files = $query
            ->orderByRaw("CASE WHEN type = 'directory' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->simplePaginate(config('web.pagination_size'))
            ->withQueryString();

        return Inertia::render('files/index', [
            'currentPath' => $path,
            'serverUser' => $serverUser,
            'files' => FileResource::collection($files),
        ]);
    }

    #[Get('/content', name: 'server-files.content')]
    public function content(Request $request, Server $server): JsonResponse
    {
        $this->authorize('view', $server);

        $validated = Validator::make($request->all(), [
            'server_user' => ['nullable', 'string', Rule::in($server->getSshUsers())],
            'path' => ['required', 'string'],
        ])->validate();

        $serverUser = $validated['server_user'] ?? $server->getSshUser();
        $path = $this->normalizePath($validated['path'], $serverUser);

        return response()->json([
            'content' => $server->os()->readFile($path),
        ]);
    }

    #[Post('/directories', name: 'server-files.directories.store')]
    public function storeDirectory(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('update', $server);

        $validated = Validator::make($request->all(), [
            'server_user' => ['required', 'string', Rule::in($server->getSshUsers())],
            'path' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255', 'regex:/^[^\/]+$/', 'not_in:.,..'],
        ])->validate();

        $directoryPath = $this->normalizePath($validated['path'].'/'.$validated['name'], $validated['server_user']);
        $server->os()->mkdir($directoryPath, $validated['server_user']);

        return back()->with('success', 'Directory created successfully.');
    }

    #[Post('/files', name: 'server-files.store')]
    public function storeFile(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('update', $server);

        $validated = Validator::make($request->all(), [
            'server_user' => ['required', 'string', Rule::in($server->getSshUsers())],
            'path' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255', 'regex:/^[^\/]+$/', 'not_in:.,..'],
            'content' => ['nullable', 'string'],
        ])->validate();

        $filePath = $this->normalizePath($validated['path'].'/'.$validated['name'], $validated['server_user']);
        $server->os()->write($filePath, $validated['content'] ?? '', $validated['server_user']);

        return back()->with('success', 'File created successfully.');
    }

    #[Patch('/content', name: 'server-files.update')]
    public function update(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('update', $server);

        $validated = Validator::make($request->all(), [
            'server_user' => ['required', 'string', Rule::in($server->getSshUsers())],
            'path' => ['required', 'string'],
            'content' => ['required', 'string'],
        ])->validate();

        $server->os()->write($this->normalizePath($validated['path'], $validated['server_user']), $validated['content'], $validated['server_user']);

        return back()->with('success', 'File updated successfully.');
    }

    #[Delete('/{file}', name: 'server-files.destroy')]
    public function destroy(Request $request, Server $server, File $file): RedirectResponse
    {
        $this->authorize('update', $server);

        abort_unless(
            $file->server_id === $server->id && $file->user_id === $request->user()->id,
            404
        );

        $file->delete();

        return back()->with('success', 'File deleted successfully.');
    }

    private function syncFiles(Request $request, Server $server, string $serverUser, string $path): void
    {
        $listOutput = $server->os()->ls($path, $serverUser);
        File::parse($request->user(), $server, $path, $serverUser, $listOutput);
    }

    private function resolveServerUser(Request $request, Server $server): string
    {
        $requestedUser = $request->string('server_user')->toString();
        if ($requestedUser !== '' && in_array($requestedUser, $server->getSshUsers(), true)) {
            return $requestedUser;
        }

        return $server->getSshUser();
    }

    private function resolvePath(string $path, string $serverUser): string
    {
        if ($path === '') {
            return home_path($serverUser);
        }

        return $this->normalizePath($path, $serverUser);
    }

    /**
     * @throws ValidationException
     */
    private function normalizePath(string $path, string $serverUser): string
    {
        $normalized = absolute_path($path);
        $homePath = home_path($serverUser);

        if ($serverUser === 'root') {
            return $normalized;
        }

        if (! str_starts_with($normalized, '/')) {
            throw ValidationException::withMessages([
                'path' => 'The path must be absolute.',
            ]);
        }

        if ($normalized !== $homePath && ! str_starts_with($normalized, $homePath.'/')) {
            throw ValidationException::withMessages([
                'path' => 'The path must be inside the selected user home directory.',
            ]);
        }

        return $normalized;
    }
}

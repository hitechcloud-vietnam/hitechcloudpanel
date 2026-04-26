<?php

namespace App\Http\Controllers;

use App\Http\Resources\FileResource;
use App\Models\File;
use App\Models\Server;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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
            'content' => $server->ssh($serverUser)->readFileContents($path),
        ]);
    }

    #[Get('/preview', name: 'server-files.preview')]
    public function preview(Request $request, Server $server): HttpResponse
    {
        $this->authorize('view', $server);

        $validated = Validator::make($request->all(), [
            'server_user' => ['nullable', 'string', Rule::in($server->getSshUsers())],
            'path' => ['required', 'string'],
        ])->validate();

        $serverUser = $validated['server_user'] ?? $server->getSshUser();
        $path = $this->normalizePath($validated['path'], $serverUser);
        $content = $server->ssh($serverUser)->readFileContents($path);
        $mimeType = $this->detectMimeType($path, $content);

        abort_unless(
            Str::startsWith($mimeType, 'text/') || Str::startsWith($mimeType, 'image/'),
            422,
            'Preview is only available for text and image files.'
        );

        return response($content, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }

    #[Get('/download', name: 'server-files.download')]
    public function download(Request $request, Server $server): HttpResponse
    {
        $this->authorize('view', $server);

        $validated = Validator::make($request->all(), [
            'server_user' => ['nullable', 'string', Rule::in($server->getSshUsers())],
            'path' => ['required', 'string'],
        ])->validate();

        $serverUser = $validated['server_user'] ?? $server->getSshUser();
        $path = $this->normalizePath($validated['path'], $serverUser);
        $content = $server->ssh($serverUser)->readFileContents($path);
        $mimeType = $this->detectMimeType($path, $content);

        return response($content, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="'.basename($path).'"',
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
        $server->ssh($validated['server_user'])->createDirectory($directoryPath);

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
        $server->ssh($validated['server_user'])->writeFileContents($filePath, $validated['content'] ?? '');

        return back()->with('success', 'File created successfully.');
    }

    #[Post('/upload', name: 'server-files.upload')]
    public function upload(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('update', $server);

        $validated = Validator::make($request->all(), [
            'server_user' => ['required', 'string', Rule::in($server->getSshUsers())],
            'path' => ['required', 'string'],
            'file' => ['required', 'file', 'max:102400'],
        ])->validate();

        /** @var UploadedFile $file */
        $file = $validated['file'];
        $remotePath = $this->normalizePath($validated['path'].'/'.$file->getClientOriginalName(), $validated['server_user']);

        $server->ssh($validated['server_user'])->uploadLocalFile($file->getRealPath(), $remotePath);

        return back()->with('success', 'File uploaded successfully.');
    }

    #[Patch('/content', name: 'server-files.update')]
    public function update(Request $request, Server $server): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $server);

        $validated = Validator::make($request->all(), [
            'server_user' => ['required', 'string', Rule::in($server->getSshUsers())],
            'path' => ['required', 'string'],
            'content' => ['required', 'string'],
        ])->validate();

        $server->ssh($validated['server_user'])->writeFileContents(
            $this->normalizePath($validated['path'], $validated['server_user']),
            $validated['content']
        );

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'ok',
            ]);
        }

        return back()->with('success', 'File updated successfully.');
    }

    #[Patch('/rename', name: 'server-files.rename')]
    public function rename(Request $request, Server $server): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $server);

        $validated = Validator::make($request->all(), [
            'server_user' => ['required', 'string', Rule::in($server->getSshUsers())],
            'path' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255', 'regex:/^[^\/]+$/', 'not_in:.,..'],
        ])->validate();

        $currentPath = $this->normalizePath($validated['path'], $validated['server_user']);
        $targetPath = $this->normalizePath(dirname($currentPath).'/'.$validated['name'], $validated['server_user']);

        $server->ssh($validated['server_user'])->renamePath($currentPath, $targetPath);

        File::query()
            ->where('server_id', $server->id)
            ->where('user_id', $request->user()->id)
            ->where('server_user', $validated['server_user'])
            ->where('path', dirname($currentPath))
            ->where('name', basename($currentPath))
            ->update([
                'name' => $validated['name'],
            ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'ok',
                'path' => $targetPath,
            ]);
        }

        return back()->with('success', 'File renamed successfully.');
    }

    #[Patch('/move', name: 'server-files.move')]
    public function move(Request $request, Server $server): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $server);

        $validated = Validator::make($request->all(), [
            'server_user' => ['required', 'string', Rule::in($server->getSshUsers())],
            'path' => ['required', 'string'],
            'destination' => ['required', 'string'],
        ])->validate();

        $currentPath = $this->normalizePath($validated['path'], $validated['server_user']);
        $destinationDirectory = $this->normalizePath($validated['destination'], $validated['server_user']);
        $targetPath = $this->normalizePath($destinationDirectory.'/'.basename($currentPath), $validated['server_user']);

        $server->ssh($validated['server_user'])->renamePath($currentPath, $targetPath);

        File::query()
            ->where('server_id', $server->id)
            ->where('user_id', $request->user()->id)
            ->where('server_user', $validated['server_user'])
            ->where('path', dirname($currentPath))
            ->where('name', basename($currentPath))
            ->update([
                'path' => dirname($targetPath),
            ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'ok',
                'path' => $targetPath,
            ]);
        }

        return back()->with('success', 'File moved successfully.');
    }

    #[Delete('/{file}', name: 'server-files.destroy')]
    public function destroy(Request $request, Server $server, File $file): RedirectResponse
    {
        $this->authorize('update', $server);

        abort_unless(
            $file->server_id === $server->id && $file->user_id === $request->user()->id,
            404
        );

        $server->ssh($file->server_user)->deletePath($file->getFilePath());
        $file->deleteQuietly();

        return back()->with('success', 'File deleted successfully.');
    }

    private function syncFiles(Request $request, Server $server, string $serverUser, string $path): void
    {
        $entries = $server->ssh($serverUser)->listDirectory($path);
        File::parseEntries($request->user(), $server, $path, $serverUser, $entries);
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

    private function detectMimeType(string $path, string $content): string
    {
        $mimeType = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $content) ?: 'application/octet-stream';

        if ($mimeType !== 'application/octet-stream') {
            return $mimeType;
        }

        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'txt', 'log', 'md', 'json', 'yml', 'yaml', 'xml', 'ini', 'conf', 'env', 'js', 'ts', 'tsx', 'jsx', 'css', 'html', 'htm', 'php', 'go', 'sh' => 'text/plain',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };
    }
}

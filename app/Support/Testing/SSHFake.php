<?php

namespace App\Support\Testing;

use App\Exceptions\SSHConnectionError;
use App\Helpers\SSH;
use App\Models\Server;
use App\Models\ServerLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Traits\ReflectsClosures;
use PHPUnit\Framework\Assert;

class SSHFake extends SSH
{
    use ReflectsClosures;

    /** @var array<string> */
    protected array $commands = [];

    protected bool $connectionWillFail = false;

    protected string $uploadedLocalPath;

    protected string $uploadedRemotePath;

    protected string $uploadedContent;

    /** @var array<string, string> */
    protected array $remoteFiles = [];

    /** @var array<int, array<string, mixed>> */
    protected array $directoryEntries = [];

    public function __construct(protected ?string $output = null) {}

    public function init(Server $server, ?string $asUser = null): self
    {
        $this->connection = null;
        $this->log = null;
        $this->asUser = null;
        $this->server = $server->refresh();
        $this->user = $server->getSshUser();
        if ($asUser && $asUser !== $server->getSshUser()) {
            $this->asUser = $asUser;
        }

        return $this;
    }

    public function connectionWillFail(): void
    {
        $this->connectionWillFail = true;
    }

    public function connect(bool $sftp = false): void
    {
        if ($this->connectionWillFail) {
            throw new SSHConnectionError('Connection failed');
        }
    }

    public function exec(string|View $command, string $log = '', ?int $siteId = null, ?bool $stream = false, ?callable $streamCallback = null): string
    {
        if (! $this->log instanceof ServerLog && $log) {
            /** @var ServerLog $log */
            $log = $this->server->logs()->create([
                'site_id' => $siteId,
                'name' => $this->server->id.'-'.strtotime('now').'-'.$log.'.log',
                'type' => $log,
                'disk' => config('core.logs_disk'),
            ]);
            $this->log = $log;
        }

        $this->commands[] = $command;

        $output = $this->output ?? 'fake output';
        $this->log?->write($output);

        if ($stream === true) {
            echo $output;
            ob_flush();
            flush();

            return '';
        }

        return $output;
    }

    public function upload(string $local, string $remote, ?string $owner = null, ?string $log = null, ?int $siteId = null): void
    {
        $this->uploadedLocalPath = $local;
        $this->uploadedRemotePath = $remote;
        $this->uploadedContent = file_get_contents($local) ?: '';
    }

    public function download(string $local, string $remote, ?string $log = null, ?int $siteId = null): void {}

    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    public function withDirectoryEntries(array $entries): self
    {
        $this->directoryEntries = $entries;

        return $this;
    }

    public function withRemoteFile(string $path, string $content): self
    {
        $this->remoteFiles[$path] = $content;

        return $this;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDirectory(string $path): array
    {
        return $this->directoryEntries;
    }

    public function readFileContents(string $path): string
    {
        return $this->remoteFiles[$path] ?? ($this->output ?? '');
    }

    public function writeFileContents(string $path, string $content): void
    {
        $this->remoteFiles[$path] = $content;
        $this->commands[] = 'sftp:write '.$path;
    }

    public function uploadLocalFile(string $localPath, string $remotePath): void
    {
        $this->uploadedLocalPath = $localPath;
        $this->uploadedRemotePath = $remotePath;
        $this->uploadedContent = file_get_contents($localPath) ?: '';
        $this->remoteFiles[$remotePath] = $this->uploadedContent;
        $this->commands[] = 'sftp:upload '.$remotePath;
    }

    public function createDirectory(string $path): void
    {
        $this->commands[] = 'sftp:mkdir '.$path;
    }

    public function renamePath(string $from, string $to): void
    {
        if (isset($this->remoteFiles[$from])) {
            $this->remoteFiles[$to] = $this->remoteFiles[$from];
            unset($this->remoteFiles[$from]);
        }

        $this->commands[] = 'sftp:rename '.$from.' '.$to;
    }

    public function deletePath(string $path): void
    {
        unset($this->remoteFiles[$path]);
        $this->commands[] = 'sftp:delete '.$path;
    }

    /**
     * @param  array<string>|string  $commands
     */
    public function assertExecuted(array|string $commands): void
    {
        if ($this->commands === []) {
            Assert::fail('No commands are executed');
        }
        if (! is_array($commands)) {
            $commands = [$commands];
        }
        $allExecuted = true;
        foreach ($commands as $command) {
            if (! in_array($command, $commands)) {
                $allExecuted = false;
            }
        }
        if (! $allExecuted) {
            Assert::fail('The expected commands are not executed. executed commands: '.implode(', ', $this->commands));
        }
    }

    public function assertExecutedContains(string $command): void
    {
        if ($this->commands === []) {
            Assert::fail('No commands are executed');
        }
        $executed = false;
        foreach ($this->commands as $executedCommand) {
            if (str($executedCommand)->contains($command)) {
                return;
            }
        }

        Assert::fail(
            'The expected command is not executed in the executed commands: '.implode(', ', $this->commands)
        );
    }

    public function assertNotExecutedContains(string $command, string $message = ''): void
    {
        foreach ($this->commands as $executedCommand) {
            $commandStr = (string) $executedCommand;
            if (str($commandStr)->contains($command)) {
                Assert::fail(
                    $message ?: "The command '{$command}' should not be executed, but it was found in: {$commandStr}"
                );
            }
        }
    }

    public function assertFileUploaded(string $toPath, ?string $content = null): void
    {
        if ($this->uploadedLocalPath === '' || $this->uploadedLocalPath === '0' || ($this->uploadedRemotePath === '' || $this->uploadedRemotePath === '0')) {
            Assert::fail('File is not uploaded');
        }

        Assert::assertEquals($toPath, $this->uploadedRemotePath);

        if ($content !== null && $content !== '' && $content !== '0') {
            Assert::assertEquals($content, $this->uploadedContent);
        }
    }

    public function getUploadedLocalPath(): string
    {
        return $this->uploadedLocalPath;
    }
}

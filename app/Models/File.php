<?php

namespace App\Models;

use Database\Factories\FileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $user_id
 * @property int $server_id
 * @property string $path
 * @property string $type
 * @property string $server_user
 * @property string $name
 * @property int $size
 * @property int $links
 * @property string $owner
 * @property string $group
 * @property string $date
 * @property string $permissions
 * @property User $user
 * @property Server $server
 */
class File extends AbstractModel
{
    /** @use HasFactory<FileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'server_id',
        'server_user',
        'path',
        'type',
        'name',
        'size',
        'links',
        'owner',
        'group',
        'date',
        'permissions',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'server_id' => 'integer',
        'size' => 'integer',
        'links' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (File $file): bool {
            if ($file->name === '.' || $file->name === '..') {
                return false;
            }

            $file->server->ssh($file->server_user)->deletePath($file->getFilePath());

            return true;
        });
    }

    /**
     * @return BelongsTo<Server, covariant $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<User, covariant $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function path(User $user, Server $server, string $serverUser): string
    {
        $file = self::query()
            ->where('user_id', $user->id)
            ->where('server_id', $server->id)
            ->where('server_user', $serverUser)
            ->first();

        if ($file) {
            return $file->path;
        }

        return home_path($serverUser);
    }

    public static function parse(User $user, Server $server, string $path, string $serverUser, string $listOutput): void
    {
        self::query()
            ->where('user_id', $user->id)
            ->where('server_id', $server->id)
            ->where('server_user', $serverUser)
            ->delete();

        // Split output by line
        $lines = explode("\n", trim($listOutput));

        // Skip the first two lines (total count and . & .. directories)
        array_shift($lines);

        foreach ($lines as $line) {
            if (preg_match('/^([drwx\-]+)\s+(\d+)\s+([\w\-]+)\s+([\w\-]+)\s+(\d+)\s+([\w\s:\-]+)\s+(.+)$/', $line, $matches)) {
                $type = match ($matches[1][0]) {
                    '-' => 'file',
                    'd' => 'directory',
                    default => 'unknown',
                };
                if ($type === 'unknown') {
                    continue;
                }
                if ($matches[7] === '.') {
                    continue;
                }
                self::create([
                    'user_id' => $user->id,
                    'server_id' => $server->id,
                    'server_user' => $serverUser,
                    'path' => $path,
                    'type' => $type,
                    'name' => $matches[7],
                    'size' => (int) $matches[5],
                    'links' => (int) $matches[2],
                    'owner' => $matches[3],
                    'group' => $matches[4],
                    'date' => $matches[6],
                    'permissions' => $matches[1],
                ]);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    public static function parseEntries(User $user, Server $server, string $path, string $serverUser, array $entries): void
    {
        self::query()
            ->where('user_id', $user->id)
            ->where('server_id', $server->id)
            ->where('server_user', $serverUser)
            ->delete();

        foreach ($entries as $entry) {
            if (($entry['name'] ?? null) === '.' || ($entry['name'] ?? null) === '..') {
                continue;
            }

            self::create([
                'user_id' => $user->id,
                'server_id' => $server->id,
                'server_user' => $serverUser,
                'path' => $path,
                'type' => $entry['type'],
                'name' => $entry['name'],
                'size' => (int) ($entry['size'] ?? 0),
                'links' => (int) ($entry['links'] ?? 1),
                'owner' => (string) ($entry['owner'] ?? ''),
                'group' => (string) ($entry['group'] ?? ''),
                'date' => (string) ($entry['date'] ?? ''),
                'permissions' => (string) ($entry['permissions'] ?? ''),
            ]);
        }
    }

    public function getFilePath(): string
    {
        return absolute_path($this->path.'/'.$this->name);
    }

    public function isExtractable(): bool
    {
        $extension = pathinfo($this->name, PATHINFO_EXTENSION);

        return in_array($extension, ['zip', 'tar', 'tar.gz', 'bz2', 'tar.bz2']);
    }
}

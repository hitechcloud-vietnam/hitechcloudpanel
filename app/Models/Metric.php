<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\MetricFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $server_id
 * @property ?float $load
 * @property ?float $cpu_usage
 * @property ?int $cpu_cores
 * @property ?float $memory_total
 * @property ?float $memory_used
 * @property ?float $memory_free
 * @property ?float $disk_total
 * @property ?float $disk_used
 * @property ?float $disk_free
 * @property ?float $network_upstream
 * @property ?float $network_downstream
 * @property ?float $network_total_sent
 * @property ?float $network_total_received
 * @property ?float $disk_read
 * @property ?float $disk_write
 * @property ?float $disk_tps
 * @property ?float $io_wait
 * @property-read float|int $memory_total_in_bytes
 * @property-read float|int $memory_used_in_bytes
 * @property-read float|int $memory_free_in_bytes
 * @property-read float|int $disk_total_in_bytes
 * @property-read float|int $disk_used_in_bytes
 * @property-read float|int $disk_free_in_bytes
 * @property Server $server
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Metric extends Model
{
    /** @use HasFactory<MetricFactory> */
    use HasFactory;

    protected $fillable = [
        'server_id',
        'load',
        'cpu_usage',
        'cpu_cores',
        'memory_total',
        'memory_used',
        'memory_free',
        'disk_total',
        'disk_used',
        'disk_free',
        'network_upstream',
        'network_downstream',
        'network_total_sent',
        'network_total_received',
        'disk_read',
        'disk_write',
        'disk_tps',
        'io_wait',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'load' => 'float',
        'cpu_usage' => 'float',
        'cpu_cores' => 'integer',
        'memory_total' => 'float',
        'memory_used' => 'float',
        'memory_free' => 'float',
        'disk_total' => 'float',
        'disk_used' => 'float',
        'disk_free' => 'float',
        'network_upstream' => 'float',
        'network_downstream' => 'float',
        'network_total_sent' => 'float',
        'network_total_received' => 'float',
        'disk_read' => 'float',
        'disk_write' => 'float',
        'disk_tps' => 'float',
        'io_wait' => 'float',
    ];

    /**
     * @return BelongsTo<Server, covariant $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function getMemoryTotalInBytesAttribute(): float|int
    {
        return ($this->memory_total ?? 0) * 1024;
    }

    public function getMemoryUsedInBytesAttribute(): float|int
    {
        return ($this->memory_used ?? 0) * 1024;
    }

    public function getMemoryFreeInBytesAttribute(): float|int
    {
        return ($this->memory_free ?? 0) * 1024;
    }

    public function getDiskTotalInBytesAttribute(): float|int
    {
        return ($this->disk_total ?? 0) * (1024 * 1024);
    }

    public function getDiskUsedInBytesAttribute(): float|int
    {
        return ($this->disk_used ?? 0) * (1024 * 1024);
    }

    public function getDiskFreeInBytesAttribute(): float|int
    {
        return ($this->disk_free ?? 0) * (1024 * 1024);
    }
}

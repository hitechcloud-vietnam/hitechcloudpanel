<?php

namespace App\Http\Controllers;

use App\Actions\Monitoring\GetMetrics;
use App\Actions\Monitoring\UpdateMetricSettings;
use App\Enums\ServiceStatus;
use App\Models\Metric;
use App\Models\Server;
use App\Models\Service;
use App\Services\Monitoring\RemoteMonitor\RemoteMonitor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('servers/{server}/monitoring')]
#[Middleware(['auth', 'has-project'])]
class MonitoringController extends Controller
{
    #[Get('/', name: 'monitoring')]
    public function index(Server $server): Response
    {
        $this->authorize('viewAny', [Metric::class, $server]);

        return Inertia::render('monitoring/index', [
            'lastMetric' => $server->metrics()->latest()->first(),
            'dataRetention' => $server->monitoring()?->type_data['data_retention'] ?? 30,
            'hasMonitoringService' => $server->monitoring()?->status === ServiceStatus::READY,
        ]);
    }

    #[Get('/json', name: 'monitoring.json')]
    public function json(Request $request, Server $server): JsonResponse
    {
        $this->authorize('viewAny', [Metric::class, $server]);

        $metrics = app(GetMetrics::class)->filter($server, $request->input());

        return response()->json($metrics);
    }

    #[Get('/realtime', name: 'monitoring.realtime')]
    public function realtime(Server $server): JsonResponse
    {
        $this->authorize('viewAny', [Metric::class, $server]);

        try {
            $monitoring = $server->monitoring();

            if ($monitoring?->name === RemoteMonitor::id()) {
                return response()->json($this->normalizeRealtimeMetric($server->os()->resourceInfo()));
            }

            return response()->json(
                $this->normalizeRealtimeMetric($server->metrics()->latest()->first())
            );
        } catch (Throwable) {
            return response()->json(
                $this->normalizeRealtimeMetric($server->metrics()->latest()->first()),
            );
        }
    }

    #[Get('/stream', name: 'monitoring.stream')]
    public function stream(Server $server): StreamedResponse
    {
        $this->authorize('viewAny', [Metric::class, $server]);

        return response()->stream(function () use ($server): void {
            ignore_user_abort(true);
            @set_time_limit(0);

            echo "retry: 3000\n\n";

            $lastMetricId = null;

            for ($iteration = 0; $iteration < $this->streamMaxIterations(); $iteration++) {
                if (connection_aborted()) {
                    break;
                }

                try {
                    $monitoring = $server->monitoring();

                    if ($monitoring?->name === RemoteMonitor::id()) {
                        $this->sendStreamEvent('metric', $this->normalizeRealtimeMetric($server->os()->resourceInfo()));
                    } else {
                        $latestMetric = $server->metrics()->latest()->first();
                        $currentMetricId = $latestMetric?->id;

                        if ($currentMetricId !== $lastMetricId || $iteration === 0) {
                            $this->sendStreamEvent('metric', $this->normalizeRealtimeMetric($latestMetric));
                            $lastMetricId = $currentMetricId;
                        } else {
                            $this->sendStreamEvent('ping', [
                                'date' => now()->toIso8601String(),
                            ]);
                        }
                    }
                } catch (Throwable) {
                    $this->sendStreamEvent('metric', $this->normalizeRealtimeMetric($server->metrics()->latest()->first()));
                }

                usleep($this->streamSleepMicroseconds());
            }
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
            'Content-Type' => 'text/event-stream',
        ]);
    }

    #[Get('/{metric}', name: 'monitoring.show')]
    public function show(Server $server, string $metric): Response
    {
        if (! in_array($metric, ['cpu', 'load', 'memory', 'disk', 'traffic', 'disk-io'])) {
            abort(404);
        }

        $this->authorize('viewAny', [Metric::class, $server]);

        return Inertia::render('monitoring/show', [
            'metric' => $metric,
        ]);
    }

    #[Patch('/update', name: 'monitoring.update')]
    public function update(Request $request, Server $server): RedirectResponse
    {
        /** @var ?Service $monitoring */
        $monitoring = $server->monitoring();

        if (! $monitoring) {
            abort(404);
        }

        $this->authorize('update', $monitoring);

        app(UpdateMetricSettings::class)->update($server, $request->input());

        return back()->with('success', 'Settings updated!');
    }

    #[Delete('/reset', name: 'monitoring.destroy')]
    public function destroy(Server $server): RedirectResponse
    {
        /** @var ?Service $monitoring */
        $monitoring = $server->monitoring();

        if (! $monitoring) {
            abort(404);
        }

        $this->authorize('update', $monitoring);

        $server->metrics()->delete();

        return back()->with('success', 'All metrics deleted!');
    }

    /**
     * @param  array<string, mixed>|Metric|Model|null  $metric
     * @return array<string, int|float|string|null>
     */
    private function normalizeRealtimeMetric(array|Metric|Model|null $metric): array
    {
        $defaults = [
            'date' => now()->format('Y-m-d H:i'),
            'load' => null,
            'cpu_usage' => null,
            'cpu_cores' => null,
            'memory_total' => null,
            'memory_used' => null,
            'memory_free' => null,
            'disk_total' => null,
            'disk_used' => null,
            'disk_free' => null,
            'network_upstream' => null,
            'network_downstream' => null,
            'network_total_sent' => null,
            'network_total_received' => null,
            'disk_read' => null,
            'disk_write' => null,
            'disk_tps' => null,
            'io_wait' => null,
        ];

        if ($metric instanceof Model) {
            $metric = $metric->toArray();
        }

        if (! is_array($metric)) {
            return $defaults;
        }

        $normalized = array_merge($defaults, $metric);

        foreach ([
            'load',
            'cpu_usage',
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
        ] as $key) {
            $normalized[$key] = isset($normalized[$key]) && $normalized[$key] !== ''
                ? (float) $normalized[$key]
                : null;
        }

        $normalized['cpu_cores'] = isset($normalized['cpu_cores']) && $normalized['cpu_cores'] !== ''
            ? (int) $normalized['cpu_cores']
            : null;

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendStreamEvent(string $event, array $payload): void
    {
        echo "event: {$event}\n";
        echo 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";

        if (ob_get_level() > 0) {
            @ob_flush();
        }

        flush();
    }

    private function streamMaxIterations(): int
    {
        if (app()->runningUnitTests()) {
            return 1;
        }

        return 300;
    }

    private function streamSleepMicroseconds(): int
    {
        if (app()->runningUnitTests()) {
            return 0;
        }

        return 5000000;
    }
}

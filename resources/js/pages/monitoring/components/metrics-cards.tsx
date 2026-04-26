import { Server } from '@/types/server';
import { useQuery } from '@tanstack/react-query';
import { Metric, MetricsFilter } from '@/types/metric';
import { ResourceUsageChart } from '@/pages/monitoring/components/resource-usage-chart';
import { bytesToHuman, formatPercentage, kbToGb, mbToGb } from '@/lib/utils';
import { Skeleton } from '@/components/ui/skeleton';

export default function MetricsCards({ server, filter, metric }: { server: Server; filter?: MetricsFilter; metric?: string }) {
  if (!filter) {
    filter = {
      period: '10m',
    };
  }

  const query = useQuery<Metric[]>({
    queryKey: ['metrics', server.id, filter.period, filter.from, filter.to],
    queryFn: async () => {
      const response = await fetch(route('monitoring.json', { server: server.id, ...filter }));
      if (!response.ok) {
        throw new Error('Failed to fetch metrics');
      }
      return response.json();
    },
    refetchInterval: 60000,
    retry: false,
  });

  return (
    <div className={metric ? 'grid grid-cols-1 gap-4' : 'grid grid-cols-1 gap-6 lg:grid-cols-2 2xl:grid-cols-3'}>
      {query.isLoading && (
        <>
          {metric ? (
            <>
              <Skeleton className="h-[510px] w-full rounded-xl border shadow-xs" />
              {(metric === 'traffic' || metric === 'disk-io') && <Skeleton className="h-[510px] w-full rounded-xl border shadow-xs" />}
              {(metric === 'traffic' || metric === 'disk-io') && <Skeleton className="h-[510px] w-full rounded-xl border shadow-xs" />}
              {(metric === 'traffic' || metric === 'disk-io') && <Skeleton className="h-[510px] w-full rounded-xl border shadow-xs" />}
            </>
          ) : (
            <>
              <Skeleton className="h-[210px] w-full rounded-xl border shadow-xs" />
              <Skeleton className="h-[210px] w-full rounded-xl border shadow-xs" />
              <Skeleton className="h-[210px] w-full rounded-xl border shadow-xs" />
            </>
          )}
        </>
      )}
      {query.data && (
        <>
          {(!metric || metric === 'load') && (
            <ResourceUsageChart
              title="CPU Load"
              label="CPU load"
              dataKey="load"
              color="var(--color-chart-1)"
              chartData={query.data}
              link={route('monitoring.show', { server: server.id, metric: 'load' })}
              single={metric !== undefined}
            />
          )}
          {(!metric || metric === 'cpu') && (
            <ResourceUsageChart
              title="CPU Usage"
              label="CPU usage"
              dataKey="cpu_usage"
              color="var(--color-chart-4)"
              chartData={query.data}
              link={route('monitoring.show', { server: server.id, metric: 'cpu' })}
              formatter={(value) => formatPercentage(value as number)}
              single={metric !== undefined}
            />
          )}
          {(!metric || metric === 'memory') && (
            <ResourceUsageChart
              title="Memory Usage"
              label="Memory usage"
              dataKey="memory_used"
              color="var(--color-chart-2)"
              chartData={query.data}
              link={route('monitoring.show', { server: server.id, metric: 'memory' })}
              formatter={(value) => {
                return `${kbToGb(value as string)} GB`;
              }}
              single={metric !== undefined}
            />
          )}
          {(!metric || metric === 'disk') && (
            <ResourceUsageChart
              title="Disk Usage"
              label="Disk usage"
              dataKey="disk_used"
              color="var(--color-chart-3)"
              chartData={query.data}
              link={route('monitoring.show', { server: server.id, metric: 'disk' })}
              formatter={(value) => {
                return `${mbToGb(value as string)} GB`;
              }}
              single={metric !== undefined}
            />
          )}
          {(!metric || metric === 'traffic') && (
            <ResourceUsageChart
              title="Upstream Traffic"
              label="Upstream traffic"
              dataKey="network_upstream"
              color="var(--color-chart-5)"
              chartData={query.data}
              link={route('monitoring.show', { server: server.id, metric: 'traffic' })}
              formatter={(value) => `${bytesToHuman(value as number)}/s`}
              single={metric !== undefined}
            />
          )}
          {metric === 'traffic' && (
            <>
              <ResourceUsageChart
                title="Downstream Traffic"
                label="Downstream traffic"
                dataKey="network_downstream"
                color="var(--color-chart-2)"
                chartData={query.data}
                link={route('monitoring.show', { server: server.id, metric: 'traffic' })}
                formatter={(value) => `${bytesToHuman(value as number)}/s`}
                single
              />
              <ResourceUsageChart
                title="Total Sent"
                label="Total sent"
                dataKey="network_total_sent"
                color="var(--color-chart-3)"
                chartData={query.data}
                link={route('monitoring.show', { server: server.id, metric: 'traffic' })}
                formatter={(value) => bytesToHuman(value as number)}
                single
              />
              <ResourceUsageChart
                title="Total Received"
                label="Total received"
                dataKey="network_total_received"
                color="var(--color-chart-4)"
                chartData={query.data}
                link={route('monitoring.show', { server: server.id, metric: 'traffic' })}
                formatter={(value) => bytesToHuman(value as number)}
                single
              />
            </>
          )}
          {(!metric || metric === 'disk-io') && (
            <ResourceUsageChart
              title="Disk Read"
              label="Disk read"
              dataKey="disk_read"
              color="var(--color-chart-1)"
              chartData={query.data}
              link={route('monitoring.show', { server: server.id, metric: 'disk-io' })}
              formatter={(value) => `${bytesToHuman(value as number)}/s`}
              single={metric !== undefined}
            />
          )}
          {metric === 'disk-io' && (
            <>
              <ResourceUsageChart
                title="Disk Write"
                label="Disk write"
                dataKey="disk_write"
                color="var(--color-chart-2)"
                chartData={query.data}
                link={route('monitoring.show', { server: server.id, metric: 'disk-io' })}
                formatter={(value) => `${bytesToHuman(value as number)}/s`}
                single
              />
              <ResourceUsageChart
                title="IO Wait"
                label="IO wait"
                dataKey="io_wait"
                color="var(--color-chart-5)"
                chartData={query.data}
                link={route('monitoring.show', { server: server.id, metric: 'disk-io' })}
                formatter={(value) => formatPercentage(value as number)}
                single
              />
              <ResourceUsageChart
                title="Disk TPS"
                label="Disk tps"
                dataKey="disk_tps"
                color="var(--color-chart-3)"
                chartData={query.data}
                link={route('monitoring.show', { server: server.id, metric: 'disk-io' })}
                formatter={(value) => Number(value ?? 0).toLocaleString()}
                single
              />
            </>
          )}
        </>
      )}
    </div>
  );
}

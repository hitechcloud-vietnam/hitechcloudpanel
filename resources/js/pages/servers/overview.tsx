import type { Server } from '@/types/server';
import type { ServerLog } from '@/types/server-log';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/server-logs/components/columns';
import { usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { PaginatedData } from '@/types';
import MetricsCards from '@/pages/monitoring/components/metrics-cards';
import { useQuery } from '@tanstack/react-query';
import { Metric } from '@/types/metric';
import OverviewMetricCard from '@/pages/monitoring/components/overview-metric-card';
import { bytesToHuman, formatPercentage, kbToGb } from '@/lib/utils';

export default function ServerOverview() {
  const page = usePage<{
    server: Server;
    logs: PaginatedData<ServerLog>;
  }>();

  const realtime = useQuery<Metric>({
    queryKey: ['monitoring-realtime', page.props.server.id],
    queryFn: async () => {
      const response = await fetch(route('monitoring.realtime', { server: page.props.server.id }));
      if (!response.ok) {
        throw new Error('Failed to fetch realtime metrics');
      }

      return response.json();
    },
    refetchInterval: 15000,
    retry: false,
  });

  const metric = realtime.data;

  return (
    <Container className="max-w-5xl">
      <Heading title="Overview" description="Here you can see an overview of your server" />
      <div className="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-5">
        <OverviewMetricCard
          title="CPU"
          value={metric ? formatPercentage(metric.cpu_usage) : 'N/A'}
          description={metric ? `${metric.cpu_cores ?? 'N/A'} cores` : 'Realtime CPU'}
          accentClassName="bg-chart-4"
        />
        <OverviewMetricCard
          title="RAM"
          value={metric ? `${kbToGb(metric.memory_used)} GB` : 'N/A'}
          description={metric ? `${kbToGb(metric.memory_total)} GB total` : 'Realtime memory'}
          accentClassName="bg-chart-2"
        />
        <OverviewMetricCard
          title="Network"
          value={metric ? `${bytesToHuman(metric.network_downstream)}/s` : 'N/A'}
          description={metric ? `Up ${bytesToHuman(metric.network_upstream)}/s` : 'Realtime traffic'}
          accentClassName="bg-chart-5"
        />
        <OverviewMetricCard
          title="Load"
          value={metric ? String(metric.load ?? 'N/A') : 'N/A'}
          description="System load average"
          accentClassName="bg-chart-1"
        />
        <OverviewMetricCard
          title="Disk I/O"
          value={metric ? `${bytesToHuman(metric.disk_read)}/s` : 'N/A'}
          description={metric ? `Write ${bytesToHuman(metric.disk_write)}/s · TPS ${metric.disk_tps ?? 'N/A'}` : 'Realtime disk io'}
          accentClassName="bg-chart-3"
        />
      </div>
      <MetricsCards server={page.props.server} />
      <DataTable columns={columns} paginatedData={page.props.logs} />
    </Container>
  );
}

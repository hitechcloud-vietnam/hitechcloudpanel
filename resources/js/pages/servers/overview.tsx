import type { Server } from '@/types/server';
import type { ServerLog } from '@/types/server-log';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/server-logs/components/columns';
import { usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { PaginatedData } from '@/types';
import MetricsCards from '@/pages/monitoring/components/metrics-cards';
import { Metric } from '@/types/metric';
import OverviewMetricCard from '@/pages/monitoring/components/overview-metric-card';
import { bytesToHuman, formatPercentage, kbToGb } from '@/lib/utils';
import { useMonitoringStream } from '@/hooks/use-monitoring-stream';

export default function ServerOverview() {
  const page = usePage<{
    server: Server;
    logs: PaginatedData<ServerLog>;
  }>();

  const realtime = useMonitoringStream(route('monitoring.stream', { server: page.props.server.id }));
  const metric: Metric | null = realtime.metric;
  const memoryUsed = metric?.memory_used ?? 0;
  const memoryTotal = metric?.memory_total ?? 0;
  const networkDownstream = metric?.network_downstream ?? 0;
  const networkUpstream = metric?.network_upstream ?? 0;
  const diskRead = metric?.disk_read ?? 0;
  const diskWrite = metric?.disk_write ?? 0;

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
          value={metric ? `${kbToGb(memoryUsed)} GB` : 'N/A'}
          description={metric ? `${kbToGb(memoryTotal)} GB total` : 'Realtime memory'}
          accentClassName="bg-chart-2"
        />
        <OverviewMetricCard
          title="Network"
          value={metric ? `${bytesToHuman(networkDownstream)}/s` : 'N/A'}
          description={metric ? `Up ${bytesToHuman(networkUpstream)}/s` : 'Realtime traffic'}
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
          value={metric ? `${bytesToHuman(diskRead)}/s` : 'N/A'}
          description={metric ? `Write ${bytesToHuman(diskWrite)}/s · TPS ${metric.disk_tps ?? 'N/A'}` : 'Realtime disk io'}
          accentClassName="bg-chart-3"
        />
      </div>
      <MetricsCards server={page.props.server} />
      <DataTable columns={columns} paginatedData={page.props.logs} />
    </Container>
  );
}

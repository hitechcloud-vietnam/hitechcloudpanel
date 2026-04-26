import { Head, Link, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import ServerLayout from '@/layouts/server/layout';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, TriangleAlertIcon } from 'lucide-react';
import Container from '@/components/container';
import MetricsCards from '@/pages/monitoring/components/metrics-cards';
import Filter from '@/pages/monitoring/components/filter';
import { useState } from 'react';
import { Metric, MetricsFilter } from '@/types/metric';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { bytesToHuman, formatPercentage, kbToGb, mbToGb } from '@/lib/utils';
import Actions from '@/pages/monitoring/components/actions';
import { Alert, AlertDescription } from '@/components/ui/alert';
import OverviewMetricCard from '@/pages/monitoring/components/overview-metric-card';
import { useMonitoringStream } from '@/hooks/use-monitoring-stream';

export default function Monitoring() {
  const page = usePage<{
    server: Server;
    lastMetric?: Metric;
    hasMonitoringService: boolean;
  }>();

  const [filter, setFilter] = useState<MetricsFilter>();
  const stream = useMonitoringStream(route('monitoring.stream', { server: page.props.server.id }), page.props.hasMonitoringService);
  const liveMetric = stream.metric ?? page.props.lastMetric;
  const metricMemoryUsed = liveMetric?.memory_used ?? 0;
  const metricMemoryFree = liveMetric?.memory_free ?? 0;
  const metricMemoryTotal = liveMetric?.memory_total ?? 0;
  const metricDiskUsed = liveMetric?.disk_used ?? 0;
  const metricDiskFree = liveMetric?.disk_free ?? 0;
  const metricDiskTotal = liveMetric?.disk_total ?? 0;
  const metricUpstream = liveMetric?.network_upstream ?? 0;
  const metricDownstream = liveMetric?.network_downstream ?? 0;
  const metricTotalSent = liveMetric?.network_total_sent ?? 0;
  const metricTotalReceived = liveMetric?.network_total_received ?? 0;
  const metricDiskRead = liveMetric?.disk_read ?? 0;
  const metricDiskWrite = liveMetric?.disk_write ?? 0;

  return (
    <ServerLayout>
      <Head title={`Monitoring - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Monitoring" description="Here you can see your server's metrics" />
          <div className="flex items-center gap-2">
            <a href="https://docs.panel.hitechcloud.one/docs/servers/monitoring" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <Filter onValueChange={setFilter} />
            <Actions server={page.props.server} />
          </div>
        </HeaderContainer>

        {!page.props.hasMonitoringService && (
          <Alert variant="destructive">
            <TriangleAlertIcon />
            <AlertDescription>
              <p>
                To monitor your server, you need to first install a{' '}
                <Link href={route('services', { server: page.props.server })} className="font-bold underline">
                  monitoring service
                </Link>
                .
              </p>
            </AlertDescription>
          </Alert>
        )}

        <MetricsCards server={page.props.server} filter={filter} />

        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
          <OverviewMetricCard
            title="CPU"
            value={liveMetric ? formatPercentage(liveMetric.cpu_usage) : 'N/A'}
            description={liveMetric ? `${liveMetric.cpu_cores ?? 'N/A'} cores · load ${liveMetric.load}` : 'Realtime CPU'}
            accentClassName="bg-chart-4"
          />
          <OverviewMetricCard
            title="RAM"
            value={liveMetric ? `${kbToGb(metricMemoryUsed)} GB` : 'N/A'}
            description={liveMetric ? `${kbToGb(metricMemoryFree)} GB free / ${kbToGb(metricMemoryTotal)} GB total` : 'Realtime RAM'}
            accentClassName="bg-chart-2"
          />
          <OverviewMetricCard
            title="Network"
            value={liveMetric ? `${bytesToHuman(metricDownstream)}/s` : 'N/A'}
            description={liveMetric ? `Up ${bytesToHuman(metricUpstream)}/s · Down ${bytesToHuman(metricDownstream)}/s` : 'Realtime traffic'}
            accentClassName="bg-chart-5"
          />
          <OverviewMetricCard
            title="Load"
            value={liveMetric ? String(liveMetric.load ?? 'N/A') : 'N/A'}
            description={liveMetric ? `IO wait ${formatPercentage(liveMetric.io_wait)}` : 'Realtime load'}
            accentClassName="bg-chart-1"
          />
        </div>

        <div className="grid grid-cols-1 gap-6 xl:grid-cols-3">
          <Card>
            <CardHeader>
              <CardTitle>Memory details</CardTitle>
              <CardDescription className="sr-only">Memory details</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="flex items-center justify-between border-b p-4">
                <span>Used</span>
                <span>{liveMetric ? kbToGb(metricMemoryUsed) + ' GB' : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between border-b p-4">
                <span>Free</span>
                <span>{liveMetric ? kbToGb(metricMemoryFree) + ' GB' : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between p-4">
                <span>Total</span>
                <span>{liveMetric ? kbToGb(metricMemoryTotal) + ' GB' : 'N/A'}</span>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Disk details</CardTitle>
              <CardDescription className="sr-only">Disk details</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="flex items-center justify-between border-b p-4">
                <span>Used</span>
                <span>{liveMetric ? mbToGb(metricDiskUsed) + ' GB' : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between border-b p-4">
                <span>Free</span>
                <span>{liveMetric ? mbToGb(metricDiskFree) + ' GB' : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between p-4">
                <span>Total</span>
                <span>{liveMetric ? mbToGb(metricDiskTotal) + ' GB' : 'N/A'}</span>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Traffic & Disk I/O</CardTitle>
              <CardDescription className="sr-only">Traffic and disk io details</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="flex items-center justify-between border-b p-4">
                <span>Upstream</span>
                <span>{liveMetric ? `${bytesToHuman(metricUpstream)}/s` : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between border-b p-4">
                <span>Downstream</span>
                <span>{liveMetric ? `${bytesToHuman(metricDownstream)}/s` : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between border-b p-4">
                <span>Total sent</span>
                <span>{liveMetric ? bytesToHuman(metricTotalSent) : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between border-b p-4">
                <span>Total received</span>
                <span>{liveMetric ? bytesToHuman(metricTotalReceived) : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between border-b p-4">
                <span>Read</span>
                <span>{liveMetric ? `${bytesToHuman(metricDiskRead)}/s` : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between border-b p-4">
                <span>Write</span>
                <span>{liveMetric ? `${bytesToHuman(metricDiskWrite)}/s` : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between border-b p-4">
                <span>TPS</span>
                <span>{liveMetric ? liveMetric.disk_tps : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between p-4">
                <span>IO Wait</span>
                <span>{liveMetric ? formatPercentage(liveMetric.io_wait) : 'N/A'}</span>
              </div>
            </CardContent>
          </Card>
        </div>
      </Container>
    </ServerLayout>
  );
}

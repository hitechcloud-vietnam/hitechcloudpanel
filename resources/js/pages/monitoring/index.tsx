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

export default function Monitoring() {
  const page = usePage<{
    server: Server;
    lastMetric?: Metric;
    hasMonitoringService: boolean;
  }>();

  const [filter, setFilter] = useState<MetricsFilter>();

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
            value={page.props.lastMetric ? formatPercentage(page.props.lastMetric.cpu_usage) : 'N/A'}
            description={page.props.lastMetric ? `${page.props.lastMetric.cpu_cores ?? 'N/A'} cores · load ${page.props.lastMetric.load}` : 'Realtime CPU'}
            accentClassName="bg-chart-4"
          />
          <OverviewMetricCard
            title="RAM"
            value={page.props.lastMetric ? `${kbToGb(page.props.lastMetric.memory_used)} GB` : 'N/A'}
            description={page.props.lastMetric ? `${kbToGb(page.props.lastMetric.memory_free)} GB free / ${kbToGb(page.props.lastMetric.memory_total)} GB total` : 'Realtime RAM'}
            accentClassName="bg-chart-2"
          />
          <OverviewMetricCard
            title="Network"
            value={page.props.lastMetric ? `${bytesToHuman(page.props.lastMetric.network_downstream)}/s` : 'N/A'}
            description={page.props.lastMetric ? `Up ${bytesToHuman(page.props.lastMetric.network_upstream)}/s · Down ${bytesToHuman(page.props.lastMetric.network_downstream)}/s` : 'Realtime traffic'}
            accentClassName="bg-chart-5"
          />
          <OverviewMetricCard
            title="Load"
            value={page.props.lastMetric ? String(page.props.lastMetric.load ?? 'N/A') : 'N/A'}
            description={page.props.lastMetric ? `IO wait ${formatPercentage(page.props.lastMetric.io_wait)}` : 'Realtime load'}
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
                <span>{page.props.lastMetric ? kbToGb(page.props.lastMetric.memory_used) + ' GB' : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between border-b p-4">
                <span>Free</span>
                <span>{page.props.lastMetric ? kbToGb(page.props.lastMetric.memory_free) + ' GB' : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between p-4">
                <span>Total</span>
                <span>{page.props.lastMetric ? kbToGb(page.props.lastMetric.memory_total) + ' GB' : 'N/A'}</span>
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
                <span>{page.props.lastMetric ? mbToGb(page.props.lastMetric.disk_used) + ' GB' : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between border-b p-4">
                <span>Free</span>
                <span>{page.props.lastMetric ? mbToGb(page.props.lastMetric.disk_free) + ' GB' : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between p-4">
                <span>Total</span>
                <span>{page.props.lastMetric ? mbToGb(page.props.lastMetric.disk_total) + ' GB' : 'N/A'}</span>
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
                <span>{page.props.lastMetric ? `${bytesToHuman(page.props.lastMetric.network_upstream)}/s` : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between border-b p-4">
                <span>Downstream</span>
                <span>{page.props.lastMetric ? `${bytesToHuman(page.props.lastMetric.network_downstream)}/s` : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between border-b p-4">
                <span>Total sent</span>
                <span>{page.props.lastMetric ? bytesToHuman(page.props.lastMetric.network_total_sent) : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between border-b p-4">
                <span>Total received</span>
                <span>{page.props.lastMetric ? bytesToHuman(page.props.lastMetric.network_total_received) : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between border-b p-4">
                <span>Read</span>
                <span>{page.props.lastMetric ? `${bytesToHuman(page.props.lastMetric.disk_read)}/s` : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between border-b p-4">
                <span>Write</span>
                <span>{page.props.lastMetric ? `${bytesToHuman(page.props.lastMetric.disk_write)}/s` : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between border-b p-4">
                <span>TPS</span>
                <span>{page.props.lastMetric ? page.props.lastMetric.disk_tps : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between p-4">
                <span>IO Wait</span>
                <span>{page.props.lastMetric ? formatPercentage(page.props.lastMetric.io_wait) : 'N/A'}</span>
              </div>
            </CardContent>
          </Card>
        </div>
      </Container>
    </ServerLayout>
  );
}

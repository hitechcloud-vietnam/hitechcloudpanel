import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import ServerLayout from '@/layouts/server/layout';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon } from 'lucide-react';
import Container from '@/components/container';
import Filter from '@/pages/monitoring/components/filter';
import { useState } from 'react';
import { MetricsFilter } from '@/types/metric';
import MetricsCards from '@/pages/monitoring/components/metrics-cards';

export default function Show() {
  const page = usePage<{
    server: Server;
    metric: string;
  }>();

  const [filter, setFilter] = useState<MetricsFilter>();

  return (
    <ServerLayout>
      <Head title={`Monitoring - ${page.props.metric} - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading
            title={page.props.metric.charAt(0).toUpperCase() + page.props.metric.slice(1)}
            description={`You're viewing ${page.props.metric}'s metrics`}
          />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/monitoring" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <Filter onValueChange={setFilter} />
          </div>
        </HeaderContainer>

        <MetricsCards server={page.props.server} filter={filter} metric={page.props.metric} />
      </Container>
    </ServerLayout>
  );
}

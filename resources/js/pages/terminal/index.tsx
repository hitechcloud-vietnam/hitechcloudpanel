import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import WebTerminal from '@/components/web-terminal';
import ServerLayout from '@/layouts/server/layout';
import { Server } from '@/types/server';

type PageProps = {
  server: Server;
  defaultUser: string;
};

export default function TerminalPage() {
  const page = usePage<PageProps>();

  return (
    <ServerLayout>
      <Head title={`Terminal - ${page.props.server.name}`} />

      <Container>
        <HeaderContainer>
          <div className="space-y-1">
            <h2 className="text-xl font-semibold tracking-tight">Web Terminal</h2>
            <p className="text-muted-foreground text-sm">SSH shell riêng biệt cho server, mặc định vào root.</p>
          </div>
        </HeaderContainer>

        <WebTerminal server={page.props.server} defaultUser={page.props.defaultUser} />
      </Container>
    </ServerLayout>
  );
}
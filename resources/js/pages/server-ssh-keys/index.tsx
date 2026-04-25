import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/data-table';
import { SshKey } from '@/types/ssh-key';
import { columns } from '@/pages/server-ssh-keys/components/columns';
import { PaginatedData } from '@/types';
import DeployKey from '@/pages/server-ssh-keys/components/deploy-key';
import ServerLayout from '@/layouts/server/layout';
import HeaderContainer from '@/components/header-container';
import { BookOpenIcon, RocketIcon } from 'lucide-react';

type Page = {
  sshKeys: PaginatedData<SshKey>;
};

export default function SshKeys() {
  const page = usePage<Page>();

  return (
    <ServerLayout>
      <Head title="SSH Keys" />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="SSH Keys" description="Here you can manage the ssh keys deployed to the server" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/ssh-keys" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <DeployKey>
              <Button>
                <RocketIcon />
                Deploy key
              </Button>
            </DeployKey>
          </div>
        </HeaderContainer>

        <DataTable columns={columns} paginatedData={page.props.sshKeys} />
      </Container>
    </ServerLayout>
  );
}

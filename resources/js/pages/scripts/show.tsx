import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { DataTable } from '@/components/data-table';
import { PaginatedData } from '@/types';
import { columns } from '@/pages/scripts/components/execution-columns';
import { Site } from '@/types/site';
import { ScriptExecution } from '@/types/script-execution';
import Layout from '@/layouts/app/layout';

type Page = {
  server: Server;
  site: Site;
  executions: PaginatedData<ScriptExecution>;
};

export default function Show() {
  const page = usePage<Page>();

  return (
    <Layout>
      <Head title={`Executions`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title={`Script executions`} description="Here you can see the script executions" />
        </HeaderContainer>

        <DataTable columns={columns} paginatedData={page.props.executions} />
      </Container>
    </Layout>
  );
}

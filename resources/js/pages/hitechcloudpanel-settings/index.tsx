import AdminLayout from '@/layouts/admin/layout';
import { Head } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Card, CardContent, CardRow } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import ExportHiTechCloudPanel from './components/export';
import ImportHiTechCloudPanel from './components/import';

export default function HiTechCloudPanelSettings() {
  return (
    <AdminLayout>
      <Head title="HiTechCloudPanel Settings" />

      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="HiTechCloudPanel Settings" description="Here you can manage general HiTechCloudPanel settings" />
        </div>

        <Card>
          <CardContent>
            <CardRow>
              <span>Export all data</span>
              <ExportHiTechCloudPanel />
            </CardRow>
            <Separator />
            <CardRow>
              <span>Import</span>
              <ImportHiTechCloudPanel />
            </CardRow>
          </CardContent>
        </Card>
      </Container>
    </AdminLayout>
  );
}

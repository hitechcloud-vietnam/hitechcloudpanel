import AdminLayout from '@/layouts/admin/layout';
import { Head } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Card, CardContent, CardRow } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import ExportHiTechCloudPanel from '@/pages/hitechcloudpanel-settings/components/export';
import ImportHiTechCloudPanel from '@/pages/hitechcloudpanel-settings/components/import';
import ExportHitechCloudPanel from './components/export';
import ImportHitechCloudPanel from './components/import';

export default function Users() {
  return (
    <AdminLayout>
      <Head title="HitechCloudPanel Settings" />

      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="HitechCloudPanel Settings" description="Here you can manage general HitechCloudPanel settings" />
        </div>

        <Card>
          <CardContent>
            <CardRow>
              <span>Export all data</span>
              <ExportHitechCloudPanel />
            </CardRow>
            <Separator />
            <CardRow>
              <span>Import</span>
              <ImportHitechCloudPanel />
            </CardRow>
          </CardContent>
        </Card>
      </Container>
    </AdminLayout>
  );
}

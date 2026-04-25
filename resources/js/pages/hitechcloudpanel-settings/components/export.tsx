import { Button } from '@/components/ui/button';
import { DownloadIcon } from 'lucide-react';

export default function ExportHiTechCloudPanel() {
  const submit = () => {
    window.open(route('hitechcloudpanel-settings.export'), '_blank');
  };

  return (
    <Button onClick={submit}>
      <DownloadIcon />
      Export
    </Button>
  );
}

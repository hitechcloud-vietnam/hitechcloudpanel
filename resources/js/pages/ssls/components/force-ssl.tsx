import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';
import { Site } from '@/types/site';

export default function ToggleForceSSL({ site }: { site: Site }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    const url = site.force_ssl
      ? route('ssls.disable-force-ssl', { server: site.server_id, site: site.id })
      : route('ssls.enable-force-ssl', { server: site.server_id, site: site.id });
    form.post(url, {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>{site.force_ssl ? 'Disable' : 'Enable'} Force-SSL</DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{site.force_ssl ? 'Disable' : 'Enable'} Force-SSL</DialogTitle>
          <DialogDescription className="sr-only">{site.force_ssl ? 'Disable' : 'Enable'} Force-SSL</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>Are you sure you want to {site.force_ssl ? 'disable' : 'enable'} force-ssl?</p>
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant={site.force_ssl ? 'destructive' : 'default'} disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            {site.force_ssl ? 'Disable' : 'Enable'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

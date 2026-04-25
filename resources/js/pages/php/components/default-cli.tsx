import { Service } from '@/types/service';
import React, { FormEvent, useState } from 'react';
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
import InputError from '@/components/ui/input-error';

export default function DefaultCli({ service }: { service: Service }) {
  const [open, setOpen] = useState(false);
  const form = useForm<{
    version: string;
  }>({
    version: service.version,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('php.default-cli', { server: service.server_id, service: service.id }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem disabled={service.is_default} onSelect={(e) => e.preventDefault()}>
          Make default cli
        </DropdownMenuItem>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Make default cli</DialogTitle>
          <DialogDescription className="sr-only">Make default cli</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>Are you sure you want to make PHP {form.data.version} the default cli?</p>
          <InputError message={form.errors.version} />
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="install-extension-form" disabled={form.processing} onClick={submit} className="ml-2">
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

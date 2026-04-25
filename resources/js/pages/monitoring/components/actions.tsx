import { Server } from '@/types/server';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon, MoreHorizontalIcon } from 'lucide-react';
import React, { FormEvent, useState } from 'react';
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
import { Form, FormField, FormFields } from '@/components/ui/form';
import { useForm, usePage } from '@inertiajs/react';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import FormSuccessful from '@/components/form-successful';

function DataRetention() {
  const page = usePage<{
    server: Server;
    dataRetention: string;
  }>();
  const [open, setOpen] = useState(false);
  const form = useForm({
    data_retention: page.props.dataRetention || '30',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('monitoring.update', page.props.server.id), {
      onSuccess: () => {
        setOpen(false);
      },
      preserveScroll: true,
      preserveState: true,
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Data retention</DropdownMenuItem>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Data retention</DialogTitle>
          <DialogDescription className="sr-only">Data retention</DialogDescription>
        </DialogHeader>
        <Form id="data-retention-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="data_retention">Data retention (days)</Label>
              <Select value={form.data.data_retention} onValueChange={(value) => form.setData('data_retention', value)}>
                <SelectTrigger id="data_retention">
                  <SelectValue placeholder="Select a period" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="7">7 Days</SelectItem>
                    <SelectItem value="14">14 Days</SelectItem>
                    <SelectItem value="30">30 Days</SelectItem>
                    <SelectItem value="60">60 Days</SelectItem>
                    <SelectItem value="90">90 Days</SelectItem>
                    <SelectItem value="180">180 Days</SelectItem>
                    <SelectItem value="365">365 Days</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
          <Button form="data-retention-form" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function Reset({ server }: { server: Server }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('monitoring.destroy', { server: server.id }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem variant="destructive" onSelect={(e) => e.preventDefault()}>
          Reset
        </DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Reset metrics</DialogTitle>
          <DialogDescription className="sr-only">Reset and delete metrics</DialogDescription>
        </DialogHeader>
        <p className="p-4">
          Are you sure you want to reset metrics? This will delete all existing monitoring metrics data for server <strong>{server.name}</strong> and
          cannot be undone.
        </p>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant="destructive" disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Reset
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export default function Actions({ server }: { server: Server }) {
  return (
    <DropdownMenu modal={false}>
      <DropdownMenuTrigger asChild>
        <Button variant="outline" className="p-0">
          <span className="sr-only">Open menu</span>
          <MoreHorizontalIcon />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <DataRetention />
        <DropdownMenuSeparator />
        <Reset server={server} />
      </DropdownMenuContent>
    </DropdownMenu>
  );
}

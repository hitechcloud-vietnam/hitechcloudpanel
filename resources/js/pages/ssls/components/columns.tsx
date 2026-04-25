import { ColumnDef } from '@tanstack/react-table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon, LockIcon, LockOpenIcon, MoreVerticalIcon } from 'lucide-react';
import React, { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import DateTime from '@/components/date-time';
import { SSL } from '@/types/ssl';
import moment from 'moment';
import { View } from '@/pages/server-logs/components/columns';
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
import FormSuccessful from '@/components/form-successful';

function Delete({ ssl }: { ssl: SSL }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('ssls.destroy', { server: ssl.server_id, site: ssl.site_id, ssl: ssl.id }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem variant="destructive" onSelect={(e) => e.preventDefault()}>
          Delete
        </DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete SSL</DialogTitle>
          <DialogDescription className="sr-only">Delete SSL</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>Are you sure you want to delete this certificate?</p>
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant="destructive" disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Delete
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function ToggleActivate({ ssl }: { ssl: SSL }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    const url = ssl.is_active
      ? route('ssls.deactivate', { server: ssl.server_id, site: ssl.site_id, ssl: ssl.id })
      : route('ssls.activate', { server: ssl.server_id, site: ssl.site_id, ssl: ssl.id });
    form.post(url, {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>{ssl.is_active ? 'Deactivate' : 'Activate'}</DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{ssl.is_active ? 'Deactivate' : 'Activate'} SSL</DialogTitle>
          <DialogDescription className="sr-only">{ssl.is_active ? 'Deactivate' : 'Activate'} SSL</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>Are you sure you want to {ssl.is_active ? 'deactivate' : 'activate'} this certificate?</p>
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant={ssl.is_active ? 'destructive' : 'default'} disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            {ssl.is_active ? 'Deactivate' : 'Activate'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export const columns: ColumnDef<SSL>[] = [
  {
    accessorKey: 'id',
    header: 'Is active',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return row.original.is_active ? <LockIcon className="text-success" /> : <LockOpenIcon />;
    },
  },
  {
    accessorKey: 'type',
    header: 'Type',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'created_at',
    header: 'Created at',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <DateTime date={row.original.created_at} />;
    },
  },
  {
    accessorKey: 'expires_at',
    header: 'Expires at',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      const targetDate = moment(row.original.expires_at);
      const today = moment();
      const daysRemaining = targetDate.diff(today, 'days');

      return (
        <div className="flex items-center gap-2">
          <DateTime date={row.original.expires_at} /> ({daysRemaining} days remaining)
        </div>
      );
    },
  },
  {
    accessorKey: 'status',
    header: 'Status',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <Badge variant={row.original.status_color}>{row.original.status}</Badge>;
    },
  },
  {
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      return (
        <div className="flex items-center justify-end">
          <DropdownMenu modal={false}>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreVerticalIcon />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <ToggleActivate ssl={row.original} />
              {row.original.log && (
                <>
                  <View serverLog={row.original.log}>
                    <DropdownMenuItem onSelect={(e) => e.preventDefault()}>View Log</DropdownMenuItem>
                  </View>
                  <DropdownMenuSeparator />
                </>
              )}
              <Delete ssl={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];

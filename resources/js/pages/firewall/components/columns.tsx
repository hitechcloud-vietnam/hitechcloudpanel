import { ColumnDef } from '@tanstack/react-table';
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
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon, MoreVerticalIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';
import { useState } from 'react';
import { FirewallRule } from '@/types/firewall';
import { Badge } from '@/components/ui/badge';
import RuleForm from '@/pages/firewall/components/form';

function Delete({ firewallRule }: { firewallRule: FirewallRule }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('firewall.destroy', { server: firewallRule.server_id, firewallRule: firewallRule }), {
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
          <DialogTitle>Delete firewallRule [{firewallRule.name}]</DialogTitle>
          <DialogDescription className="sr-only">Delete firewallRule</DialogDescription>
        </DialogHeader>
        <p className="p-4">
          Are you sure you want to delete rule <strong>{firewallRule.name}</strong>? This action cannot be undone.
        </p>
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

export const columns: ColumnDef<FirewallRule>[] = [
  {
    accessorKey: 'name',
    header: 'Name',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'type',
    header: 'Type',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <span className="uppercase">{row.original.type}</span>;
    },
  },
  {
    accessorKey: 'source',
    header: 'Source',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <span>{row.original.source ?? 'any'}</span>;
    },
  },
  {
    accessorKey: 'protocol',
    header: 'Protocol',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <span className="uppercase">{row.original.protocol}</span>;
    },
  },
  {
    accessorKey: 'port',
    header: 'Port',
    enableColumnFilter: true,
    enableSorting: true,
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
              <RuleForm serverId={row.original.server_id} firewallRule={row.original}>
                <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
              </RuleForm>
              <DropdownMenuSeparator />
              <Delete firewallRule={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];

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
import { Link, useForm } from '@inertiajs/react';
import { LoaderCircleIcon, MoreVerticalIcon, PlayIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';
import { useState } from 'react';
import { Command } from '@/types/command';
import EditCommand from '@/pages/commands/components/edit-command';
import CopyableBadge from '@/components/copyable-badge';
import Execute from '@/pages/commands/components/execute';

function Delete({ command }: { command: Command }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('commands.destroy', { server: command.server_id, site: command.site_id, command: command.id }), {
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
          <DialogTitle>Delete command</DialogTitle>
          <DialogDescription className="sr-only">Delete command</DialogDescription>
        </DialogHeader>
        <p className="p-4">Are you sure you want to this command?</p>
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

export const columns: ColumnDef<Command>[] = [
  {
    accessorKey: 'name',
    header: 'Name',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'command',
    header: 'Command',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <CopyableBadge text={row.original.command} />;
    },
  },
  {
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      return (
        <div className="flex items-center justify-end gap-1">
          <Execute command={row.original}>
            <Button variant="outline" className="size-8">
              <PlayIcon className="size-3" />
            </Button>
          </Execute>
          <DropdownMenu modal={false}>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreVerticalIcon />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <EditCommand command={row.original}>
                <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
              </EditCommand>
              <Link
                href={route('commands.show', {
                  server: row.original.server_id,
                  site: row.original.site_id,
                  command: row.original.id,
                })}
              >
                <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Executions</DropdownMenuItem>
              </Link>
              <DropdownMenuSeparator />
              <Delete command={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];

import { Link, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { MoreVerticalIcon, FolderIcon, FileIcon, LoaderCircleIcon } from 'lucide-react';
import { ServerFile } from '@/types/file';
import { bytesToHuman } from '@/lib/utils';
import DateTime from '@/components/date-time';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
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
import { useState } from 'react';

type FileColumnsOptions = {
  onOpenEditor?: (file: ServerFile) => void;
};

function Delete({ file }: { file: ServerFile }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('server-files.destroy', { server: file.server_id, file: file.id }), {
      onSuccess: () => setOpen(false),
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
          <DialogTitle>Delete file</DialogTitle>
          <DialogDescription className="sr-only">Delete file</DialogDescription>
        </DialogHeader>
        <p className="p-4">Are you sure you want to delete <strong>{file.name}</strong>?</p>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant="destructive" disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Delete
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export function getColumns({ onOpenEditor }: FileColumnsOptions = {}): ColumnDef<ServerFile>[] {
  return [
    {
      accessorKey: 'name',
      header: 'Name',
      cell: ({ row }) => {
        const file = row.original;
        const isDirectory = file.type === 'directory';
        const href = isDirectory
          ? route('server-files', { server: file.server_id, path: file.file_path, server_user: file.server_user })
          : undefined;

        const content = (
          <div className="flex items-center gap-2">
            {isDirectory ? <FolderIcon className="h-4 w-4 text-blue-500" /> : <FileIcon className="h-4 w-4 text-muted-foreground" />}
            <span>{file.name}</span>
          </div>
        );

        return href ? (
          <Link href={href} className="hover:underline">
            {content}
          </Link>
        ) : (
          content
        );
      },
    },
    {
      accessorKey: 'permissions',
      header: 'Permissions',
    },
    {
      accessorKey: 'owner',
      header: 'Owner',
      cell: ({ row }) => `${row.original.owner}:${row.original.group}`,
    },
    {
      accessorKey: 'size',
      header: 'Size',
      cell: ({ row }) => (row.original.type === 'directory' ? '-' : bytesToHuman(row.original.size)),
    },
    {
      accessorKey: 'date',
      header: 'Modified',
      cell: ({ row }) => row.original.date,
    },
    {
      id: 'actions',
      cell: ({ row }) => {
        const file = row.original;

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
                {file.type === 'file' && (
                  <DropdownMenuItem
                    onSelect={(e) => {
                      e.preventDefault();
                      onOpenEditor?.(file);
                    }}
                  >
                    Edit
                  </DropdownMenuItem>
                )}
                <Delete file={file} />
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        );
      },
    },
  ];
}

import { ColumnDef } from '@tanstack/react-table';
import { Server } from '@/types/server';
import { Link } from '@inertiajs/react';
import DateTime from '@/components/date-time';
import { Site } from '@/types/site';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { EyeIcon } from 'lucide-react';

export default function getColumns(server?: Server): ColumnDef<Site>[] {
  let columns: ColumnDef<Site>[] = [
    {
      accessorKey: 'id',
      header: 'ID',
      enableColumnFilter: true,
      enableSorting: true,
      enableHiding: true,
    },
    {
      accessorKey: 'domain',
      header: 'Domain',
      enableColumnFilter: true,
      enableSorting: true,
    },
    {
      accessorKey: 'type',
      header: 'Type',
      enableColumnFilter: true,
      enableSorting: true,
      cell: ({ row }) => {
        return <Badge variant="outline">{row.original.type}</Badge>;
      },
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
            <Link href={route('application', { server: row.original.server_id, site: row.original.id })} prefetch>
              <Button variant="outline" size="sm">
                <EyeIcon />
              </Button>
            </Link>
          </div>
        );
      },
    },
  ];

  if (!server) {
    // add column to the first
    columns = [
      {
        id: 'server',
        header: 'Server',
        cell: ({ row }) => {
          return (
            <Link href={route('servers.show', { server: row.original.server_id })} prefetch>
              {row.original.server?.name}
            </Link>
          );
        },
      },
      ...columns,
    ];
  }

  return columns;
}

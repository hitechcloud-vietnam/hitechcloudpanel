import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { cn } from '@/lib/utils';

export function TableSkeleton({ cells, rows, modal }: { cells: number; rows: number; modal?: boolean }) {
  const extraClasses = modal && 'border-none shadow-none';

  return (
    <div className={cn('rounded-md border shadow-xs', extraClasses)}>
      <Table>
        <TableHeader>
          <TableRow>
            {[...Array(cells)].map((_, i) => (
              <TableHead key={i}>
                <Skeleton className="h-3" />
              </TableHead>
            ))}
          </TableRow>
        </TableHeader>
        <TableBody>
          {[...Array(rows)].map((_, i) => (
            <TableRow key={i} className="h-[60px]!">
              {[...Array(cells)].map((_, j) => (
                <TableCell key={j}>
                  <Skeleton className="h-3 w-full" />
                </TableCell>
              ))}
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}

import DateTime from '@/components/date-time';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { PaginatedData } from '@/types';
import { Server } from '@/types/server';
import { ServerLog } from '@/types/server-log';
import { useQuery } from '@tanstack/react-query';
import { ChevronRightIcon, LogsIcon, RefreshCwIcon, XIcon } from 'lucide-react';
import { ReactNode, useEffect, useState } from 'react';
import { toast } from 'sonner';
import { useLogStream } from '@/hooks/use-log-stream';
import LogOutput from '@/components/log-output';

interface LogEntry {
  id: number;
  content: string;
}

export function InstantLogs({ server, children }: { server: Server; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const [logEntry, setLogEntry] = useState<LogEntry | null>(null);
  const [page, setPage] = useState(1);
  const [logs, setLogs] = useState<ServerLog[]>([]);
  const [live, setLive] = useState(true);
  const activeLog = logs.find((log) => log.id === logEntry?.id) ?? null;
  const streamUrl = activeLog?.supports_streaming ? route('logs.stream', { server: server.id, log: activeLog.id }) : null;
  const stream = useLogStream(streamUrl, open && !!activeLog?.supports_streaming && live);

  const query = useQuery<PaginatedData<ServerLog>>({
    queryKey: ['instant-logs', server.id, page],
    queryFn: async () => {
      const response = await fetch(route('logs.json', { server: server.id, count: 15, page: page }));
      if (!response.ok) {
        toast.error('Failed to fetch logs');
        throw new Error('Network response was not ok');
      }
      const data = response.json();
      const logs = (await data).data;
      if (page === 1) {
        setLogs(logs);
      } else {
        setLogs((prev) => [...new Set([...prev, ...logs])]);
      }
      setPage((await data).meta.current_page);
      return data;
    },
    enabled: false,
  });

  useEffect(() => {
    if (open) {
      query.refetch();
    }
  }, [open]);

  useEffect(() => {
    query.refetch();
  }, [page]);

  const fetchLog = async (logId: number) => {
    if (logEntry?.id === logId) {
      setLogEntry(null);
      return;
    }

    const selectedLog = logs.find((log) => log.id === logId);

    if (selectedLog?.supports_streaming) {
      setLive(true);
      setLogEntry({ id: logId, content: '' });
      return;
    }

    setLogEntry({
      id: logId,
      content: 'Loading...',
    });
    const response = await fetch(route('logs.show', { server: server.id, log: logId }));
    if (!response.ok) {
      toast.error('Failed to fetch log');
      throw new Error('Network response was not ok');
    }
    const text = await response.text();
    setLogEntry({ id: logId, content: text });
  };

  const loadMore = () => {
    setPage((prev) => prev + 1);
  };

  const reset = () => {
    setLogs([]);
    setLogEntry(null);
    setPage(1);
  };

  useEffect(() => {
    const handleKeydown = (event: KeyboardEvent) => {
      if (event.ctrlKey && event.shiftKey && event.key === 'L') {
        event.preventDefault();
        setOpen(!open);
      }
    };

    document.addEventListener('keydown', handleKeydown);
    return () => document.removeEventListener('keydown', handleKeydown);
  }, [open, setOpen]);

  useEffect(() => {
    if (logEntry?.id && activeLog?.supports_streaming) {
      setLogEntry((previous) => {
        if (!previous || previous.id !== activeLog.id) {
          return previous;
        }

        return {
          ...previous,
          content: stream.error ? `Error: ${stream.error}` : stream.content || 'Connecting...',
        };
      });
    }
  }, [activeLog?.id, activeLog?.supports_streaming, logEntry?.id, stream.content, stream.error]);

  return (
    <Sheet open={open} onOpenChange={setOpen}>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent side="bottom" className="h-3/4" showClose={false}>
        <SheetHeader className="bg-muted/50 flex flex-row items-center justify-between border-b px-4 py-2">
          <div className="flex items-center gap-2">
            <LogsIcon className="h-4 w-4" />
            <SheetTitle className="text-sm font-medium">Logs - {server.name}</SheetTitle>
            <SheetDescription className="sr-only">Logs</SheetDescription>
          </div>
          <div className="flex items-center gap-2">
            <Tooltip delayDuration={0}>
              <TooltipTrigger asChild>
                <Button variant="ghost" size="icon" className="h-7 w-7" onClick={reset}>
                  {query.isFetching ? <RefreshCwIcon className="h-3 w-3 animate-spin" /> : <RefreshCwIcon className="h-3 w-3" />}
                </Button>
              </TooltipTrigger>
              <TooltipContent>Reload</TooltipContent>
            </Tooltip>
            <Tooltip delayDuration={0}>
              <TooltipTrigger asChild>
                <Button variant="ghost" size="icon" className="h-7 w-7" onClick={() => setOpen(false)}>
                  <XIcon className="h-3 w-3" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>Close</TooltipContent>
            </Tooltip>
          </div>
        </SheetHeader>
        <div className="flex h-full flex-col overflow-y-auto">
          {logs.map((log, index) => (
            <div key={`log-${log.id}`}>
              <Button
                variant="ghost"
                className="flex w-full items-center justify-between rounded-none border-b px-4 py-2 font-mono text-xs"
                onClick={() => fetchLog(log.id)}
                tabIndex={index + 1}
                autoFocus={index === 0}
              >
                <div className="flex items-center gap-2">
                  <ChevronRightIcon className="size-4" />
                  <div>{log.name}</div>
                </div>
                <div className="text-muted-foreground text-xs">
                  <DateTime date={log.created_at} />
                </div>
              </Button>
              {logEntry?.id === log.id && (
                <div className="border-b px-4 py-2">
                  <LogOutput className="h-64 max-h-64 text-xs" live={live} onLiveChange={setLive} showLiveToggle={!!activeLog?.supports_streaming}>
                    {logEntry.content}
                  </LogOutput>
                </div>
              )}
            </div>
          ))}
          <Button variant="ghost" onClick={loadMore} tabIndex={logs.length + 1}>
            {query.isFetching ? 'Loading...' : 'Load More'}
          </Button>
        </div>
      </SheetContent>
    </Sheet>
  );
}

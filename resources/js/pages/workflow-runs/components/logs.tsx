import LogOutput from '@/components/log-output';
import { useLogStream } from '@/hooks/use-log-stream';
import { WorkflowRun } from '@/types/workflow-run';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { useState } from 'react';

export default function Logs({ workflowRun }: { workflowRun: WorkflowRun }) {
  const [live, setLive] = useState(true);
  const streamUrl = workflowRun.supports_streaming ? route('workflow-runs.stream', { workflow: workflowRun.workflow_id, workflowRun: workflowRun.id }) : null;
  const stream = useLogStream(streamUrl, !!workflowRun.supports_streaming && live);

  const query = useQuery({
    queryKey: ['workflow-run-logs', workflowRun.id],
    queryFn: async () => {
      try {
        const response = await axios.get(route('workflow-runs.log', { workflow: workflowRun.workflow_id, workflowRun: workflowRun.id }));
        return response.data;
      } catch (error: unknown) {
        if (axios.isAxiosError(error)) {
          throw new Error(error.response?.data?.error || 'An error occurred while fetching the log');
        }
        throw new Error('Unknown error occurred');
      }
    },
    enabled: !workflowRun.supports_streaming,
    retry: false,
    refetchInterval: (query) => {
      if (query.state.status === 'error') return false;
      if (workflowRun.status !== 'running') return false;
      return 2500;
    },
  });

  return (
    <LogOutput className="rounded-lg border shadow" live={live} onLiveChange={setLive} showLiveToggle={!!workflowRun.supports_streaming}>
      <>
        {workflowRun.supports_streaming ? (
          <>
            {!stream.content && !stream.error && 'Connecting...'}
            {stream.error && <div className="text-red-500">Error: {stream.error}</div>}
            {stream.content}
          </>
        ) : (
          <>
            {query.isLoading && 'Loading...'}
            {query.isError && <div className="text-red-500">Error: {query.error.message}</div>}
            {query.data && !query.isError && query.data}
          </>
        )}
      </>
    </LogOutput>
  );
}

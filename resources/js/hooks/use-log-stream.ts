import { useEffect, useMemo, useState } from 'react';

interface LogStreamState {
  content: string;
  error: string | null;
  connected: boolean;
}

interface LogEventPayload {
  chunk?: string;
  reset?: boolean;
  completed?: boolean;
}

export function useLogStream(url: string | null, enabled = true): LogStreamState {
  const [content, setContent] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [connected, setConnected] = useState(false);

  useEffect(() => {
    if (!url || !enabled) {
      return;
    }

    setContent('');
    setError(null);
    setConnected(false);

    const eventSource = new EventSource(url, { withCredentials: true });

    const handleLogPayload = (payload: LogEventPayload) => {
      setConnected(true);
      setError(null);

      setContent((previous) => {
        const chunk = payload.chunk ?? '';
        if (payload.reset) {
          return chunk;
        }

        return `${previous}${chunk}`;
      });

      if (payload.completed) {
        eventSource.close();
        setConnected(false);
      }
    };

    eventSource.addEventListener('log', (event) => {
      handleLogPayload(JSON.parse((event as MessageEvent).data) as LogEventPayload);
    });

    eventSource.addEventListener('ping', (event) => {
      const payload = JSON.parse((event as MessageEvent).data) as LogEventPayload;
      setConnected(true);

      if (payload.completed) {
        eventSource.close();
        setConnected(false);
      }
    });

    eventSource.onerror = () => {
      setConnected(false);
      setError('Streaming connection interrupted');
      eventSource.close();
    };

    return () => {
      eventSource.close();
    };
  }, [url, enabled]);

  return useMemo(
    () => ({
      content,
      error,
      connected,
    }),
    [connected, content, error]
  );
}
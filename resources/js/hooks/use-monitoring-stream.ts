import { useEffect, useMemo, useState } from 'react';

import type { Metric } from '@/types/metric';

interface MonitoringStreamState {
  metric: Metric | null;
  error: string | null;
  connected: boolean;
}

export function useMonitoringStream(url: string | null, enabled = true): MonitoringStreamState {
  const [metric, setMetric] = useState<Metric | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [connected, setConnected] = useState(false);

  useEffect(() => {
    if (!url || !enabled) {
      return;
    }

    setError(null);
    setConnected(false);

    const eventSource = new EventSource(url, { withCredentials: true });

    eventSource.addEventListener('metric', (event) => {
      setMetric(JSON.parse((event as MessageEvent).data) as Metric);
      setConnected(true);
      setError(null);
    });

    eventSource.addEventListener('ping', () => {
      setConnected(true);
    });

    eventSource.onerror = () => {
      setConnected(false);
      setError('Streaming connection interrupted');
    };

    return () => {
      eventSource.close();
    };
  }, [enabled, url]);

  return useMemo(
    () => ({
      metric,
      error,
      connected,
    }),
    [connected, error, metric]
  );
}
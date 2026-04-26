export interface Metric {
  date: string;
  load: number | null;
  cpu_usage: number | null;
  cpu_cores: number | null;
  memory_total: number | null;
  memory_used: number | null;
  memory_free: number | null;
  disk_total: number | null;
  disk_used: number | null;
  disk_free: number | null;
  network_upstream: number | null;
  network_downstream: number | null;
  network_total_sent: number | null;
  network_total_received: number | null;
  disk_read: number | null;
  disk_write: number | null;
  disk_tps: number | null;
  io_wait: number | null;
  date_interval: string;

  [key: string]: number | string | null;
}

export interface MetricsFilter {
  period: string;
  from?: string;
  to?: string;
  [key: string]: number | string;
}

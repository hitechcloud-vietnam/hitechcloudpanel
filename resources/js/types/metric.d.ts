export interface Metric {
  date: string;
  load: number;
  cpu_usage: number;
  cpu_cores: number;
  memory_total: number;
  memory_used: number;
  memory_free: number;
  disk_total: number;
  disk_used: number;
  disk_free: number;
  network_upstream: number;
  network_downstream: number;
  network_total_sent: number;
  network_total_received: number;
  disk_read: number;
  disk_write: number;
  disk_tps: number;
  io_wait: number;
  date_interval: string;

  [key: string]: number | string;
}

export interface MetricsFilter {
  period: string;
  from?: string;
  to?: string;
  [key: string]: number | string;
}

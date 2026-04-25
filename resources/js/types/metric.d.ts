export interface Metric {
  date: string;
  load: number;
  memory_total: number;
  memory_used: number;
  memory_free: number;
  disk_total: number;
  disk_used: number;
  disk_free: number;
  date_interval: string;

  [key: string]: number | string;
}

export interface MetricsFilter {
  period: string;
  from?: string;
  to?: string;
  [key: string]: number | string;
}

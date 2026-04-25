import { ServerLog } from '@/types/server-log';

export interface CommandExecution {
  id: number;
  command_id: number;
  server_id: number;
  user_id: number;
  server_log_id: number;
  log: ServerLog;
  status: string;
  status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}

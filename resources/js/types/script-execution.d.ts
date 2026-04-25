import { ServerLog } from '@/types/server-log';
import { Server } from './server';

export interface ScriptExecution {
  id: number;
  script_id: number;
  server_id: number;
  server?: Server;
  user_id: number;
  server_log_id: number;
  log: ServerLog;
  user: string;
  variables: string[];
  status: string;
  status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}

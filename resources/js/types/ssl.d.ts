import { ServerLog } from '@/types/server-log';

export interface SSL {
  id: number;
  server_id: number;
  site_id: number;
  is_active: boolean;
  type: string;
  log?: ServerLog;
  status: string;
  status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  expires_at: string;
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}

export interface WorkflowRun {
  id: number;
  workflow_id: number;
  status: string;
  status_color: 'success' | 'danger' | 'warning' | 'info';
  current_node_label: string | null;
  current_node_id: string | null;
  supports_streaming?: boolean;
  created_at: string;
  updated_at: string;
  [key: string]: unknown;
}

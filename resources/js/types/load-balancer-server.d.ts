export interface LoadBalancerServer {
  load_balancer_id: number;
  ip: string;
  port: number;
  weight: string;
  backup: boolean;
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}

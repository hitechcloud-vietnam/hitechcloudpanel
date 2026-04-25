import { ScriptExecution } from './script-execution';
import { User } from './user';

export interface Script {
  id: number;
  user_id: number;
  user?: User;
  name: string;
  content: string;
  variables: string[];
  last_execution?: ScriptExecution;
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}

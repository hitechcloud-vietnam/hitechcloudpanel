export interface ServerFile {
  id: number;
  server_id: number;
  user_id: number;
  server_user: string;
  path: string;
  type: 'file' | 'directory';
  name: string;
  size: number;
  links: number;
  owner: string;
  group: string;
  date: string;
  permissions: string;
  file_path: string;
  is_extractable: boolean;
  created_at: string;
  updated_at: string;
  [key: string]: unknown;
}

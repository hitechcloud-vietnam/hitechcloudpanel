declare module 'ziggy-js' {
  export type RouteName = string;

  export interface Config {
    url?: string;
    port?: number | null;
    defaults?: Record<string, unknown>;
    routes?: Record<string, unknown>;
    location?: string | URL;
    [key: string]: unknown;
  }

  export function route<T extends RouteName = RouteName>(
    name?: T,
    params?: unknown,
    absolute?: boolean,
    config?: Config,
  ): string;
}
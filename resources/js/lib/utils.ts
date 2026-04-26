import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

// convert kb to gb
export function kbToGb(kb: number | string): number {
  if (typeof kb === 'string') {
    kb = parseFloat(kb);
  }
  return Math.round((kb / 1024 / 1024) * 100) / 100;
}

// convert mb to gb
export function mbToGb(mb: number | string): number {
  if (typeof mb === 'string') {
    mb = parseFloat(mb);
  }
  return Math.round((mb / 1024) * 100) / 100;
}

export function bytesToHuman(bytes: number | string, decimals = 2): string {
  if (typeof bytes === 'string') {
    bytes = parseFloat(bytes);
  }

  if (!Number.isFinite(bytes) || bytes <= 0) {
    return '0 B';
  }

  const units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
  const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
  const value = bytes / 1024 ** index;

  return `${parseFloat(value.toFixed(decimals))} ${units[index]}`;
}

export function formatPercentage(value: number | string | null | undefined): string {
  if (value === null || value === undefined || value === '') {
    return 'N/A';
  }

  const numeric = typeof value === 'string' ? parseFloat(value) : value;

  if (!Number.isFinite(numeric)) {
    return 'N/A';
  }

  return `${numeric.toFixed(2)}%`;
}

export function formatDateString(dateString: string | Date): string {
  const date = new Date(dateString);

  const year = date.toLocaleString('default', { year: 'numeric' });
  const month = date.toLocaleString('default', { month: '2-digit' });
  const day = date.toLocaleString('default', { day: '2-digit' });

  // Generate yyyy-mm-dd date string
  return year + '-' + month + '-' + day;
}

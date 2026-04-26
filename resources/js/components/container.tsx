import { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export default function Container({ className, children }: { className?: string; children?: ReactNode }) {
  return <div className={cn('mx-auto w-full !max-w-none space-y-5 px-4 py-5', className)}>{children}</div>;
}

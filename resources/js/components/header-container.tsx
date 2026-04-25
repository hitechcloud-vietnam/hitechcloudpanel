import { ReactNode } from 'react';

export default function HeaderContainer({ children }: { children: ReactNode }) {
  return <div className="flex items-start justify-between gap-2">{children}</div>;
}

import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

interface Props {
  title: string;
  value: string;
  description?: string;
  accentClassName?: string;
}

export default function OverviewMetricCard({ title, value, description, accentClassName }: Props) {
  return (
    <Card className="overflow-hidden">
      <CardContent className="p-0">
        <div className={cn('h-1 w-full bg-primary/70', accentClassName)} />
        <div className="space-y-3 p-4">
          <Badge variant="outline">{title}</Badge>
          <div className="space-y-1">
            <div className="text-2xl font-bold">{value}</div>
            {description ? <p className="text-muted-foreground text-sm">{description}</p> : null}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
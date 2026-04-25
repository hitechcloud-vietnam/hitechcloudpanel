import * as React from 'react';
import { Area, AreaChart, XAxis, YAxis } from 'recharts';

import { Card, CardContent } from '@/components/ui/card';
import { ChartConfig, ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import { Metric } from '@/types/metric';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { cn } from '@/lib/utils';

interface Props {
  title: string;
  color: string;
  dataKey: 'load' | 'memory_used' | 'disk_used';
  label: string;
  chartData: Metric[];
  link: string;
  formatter?: (value: unknown, name: unknown) => string | number;
  single?: boolean;
}

export function ResourceUsageChart({ title, color, dataKey, label, chartData, link, formatter, single }: Props) {
  const chartConfig = {
    [dataKey]: {
      label: label,
      color: color,
    },
  } satisfies ChartConfig;

  return (
    <Card>
      <CardContent className="overflow-hidden p-0">
        <div className="flex items-start justify-between p-4">
          <div className="space-y-2 py-[7px]">
            <h2 className="text-muted-foreground text-sm">{title}</h2>
            <span className="text-3xl font-bold">
              {chartData.length > 0
                ? formatter
                  ? formatter(chartData[chartData.length - 1][dataKey], dataKey)
                  : chartData[chartData.length - 1][dataKey].toLocaleString()
                : 'N/A'}
            </span>
          </div>
          {!single && (
            <Button variant="ghost" onClick={() => router.visit(link)}>
              View
            </Button>
          )}
        </div>
        <ChartContainer config={chartConfig} className={cn('aspect-auto w-full overflow-hidden rounded-b-xl', single ? 'h-[400px]' : 'h-[100px]')}>
          <AreaChart accessibilityLayer data={chartData} margin={{ left: 0, right: 0, top: 0, bottom: 0 }}>
            <defs>
              <linearGradient id={`fill-${dataKey}`} x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor={color} stopOpacity={0.8} />
                <stop offset="95%" stopColor={color} stopOpacity={0.1} />
              </linearGradient>
            </defs>
            <YAxis dataKey={dataKey} hide />
            <XAxis
              hide={!single}
              dataKey="date"
              tickLine={false}
              axisLine={false}
              tickMargin={8}
              minTickGap={32}
              tickFormatter={(value) => {
                const date = new Date(value);
                return date.toLocaleDateString('en-US', {
                  hour: '2-digit',
                  minute: '2-digit',
                  month: 'short',
                  day: 'numeric',
                });
              }}
            />
            <ChartTooltip
              cursor={true}
              content={
                <ChartTooltipContent
                  labelFormatter={(value) => {
                    return new Date(value).toLocaleDateString('en-US', {
                      hour: '2-digit',
                      minute: '2-digit',
                      month: 'short',
                      day: 'numeric',
                    });
                  }}
                  formatter={formatter}
                  indicator="dot"
                />
              }
            />
            <Area dataKey={dataKey} type="monotone" fill={`url(#fill-${dataKey})`} stroke={color} />
          </AreaChart>
        </ChartContainer>
      </CardContent>
    </Card>
  );
}

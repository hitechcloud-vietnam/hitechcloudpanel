import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import React from 'react';
import { SelectTriggerProps } from '@radix-ui/react-select';

export default function ServiceVersionSelect({
  serverId,
  service,
  value,
  onValueChange,
  ...props
}: {
  serverId: number;
  service: string;
  value: string;
  onValueChange: (value: string) => void;
} & SelectTriggerProps) {
  const query = useQuery<string[]>({
    queryKey: ['service'],
    queryFn: async () => {
      return (await axios.get(route('services.versions', { server: serverId, service: service }))).data;
    },
  });

  return (
    <Select value={value} onValueChange={onValueChange} disabled={query.isFetching}>
      <SelectTrigger {...props}>
        <SelectValue placeholder={query.isFetching ? 'Loading...' : 'Select a version'} />
      </SelectTrigger>
      <SelectContent>
        <SelectGroup>
          {query.isSuccess &&
            query.data.map((version: string) => (
              <SelectItem key={`service-v-${version}`} value={version}>
                {version}
              </SelectItem>
            ))}
        </SelectGroup>
      </SelectContent>
    </Select>
  );
}

import React from 'react';
import { Card } from '@/components/ui/Card';
import { Skeleton } from '@/components/ui/skeleton';

interface ChartCardProps {
  title: string;
  children: React.ReactNode;
  isLoading?: boolean;
  description?: string;
}

export function ChartCard({ title, children, isLoading = false, description }: ChartCardProps) {
  return (
    <Card className="p-6">
      <div className="mb-4">
        <h3 className="text-lg font-semibold text-gray-900">{title}</h3>
        {description && (
          <p className="text-sm text-gray-600 mt-1">{description}</p>
        )}
      </div>
      
      {isLoading ? (
        <div className="space-y-3">
          <Skeleton className="h-64 w-full" />
        </div>
      ) : (
        <div className="w-full">
          {children}
        </div>
      )}
    </Card>
  );
}

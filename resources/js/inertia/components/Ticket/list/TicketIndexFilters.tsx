import { Search } from 'lucide-react';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import type { TicketPriority, TicketStatus } from '@/types';

const statusTabs = [
    { id: '', label: 'All' },
    { id: 'waiting', label: 'Menunggu' },
    { id: 'in_progress', label: 'Dalam Proses' },
    { id: 'done', label: 'Selesai' },
    { id: 'cancelled', label: 'Dibatalkan' },
] as const;

const priorityOptions: { value: TicketPriority | ''; label: string }[] = [
    { value: '', label: 'All Priority' },
    { value: 'low', label: 'Rendah' },
    { value: 'medium', label: 'Sedang' },
    { value: 'high', label: 'Tinggi' },
    { value: 'critical', label: 'Kritis' },
];

interface TicketIndexFiltersProps {
    search: string;
    status: TicketStatus | '';
    priority: TicketPriority | '';
    categoryId: string;
    assignedUserId: string;
    categoryOptions: { value: string; label: string }[];
    staffOptions: { value: string; label: string }[];
    onSearchChange: (value: string) => void;
    onSearchSubmit: (event: React.FormEvent) => void;
    onStatusChange: (status: TicketStatus | '') => void;
    onPriorityChange: (value: string) => void;
    onCategoryChange: (value: string) => void;
    onAssignedChange: (value: string) => void;
}

export function TicketIndexFilters({
    search,
    status,
    priority,
    categoryId,
    assignedUserId,
    categoryOptions,
    staffOptions,
    onSearchChange,
    onSearchSubmit,
    onStatusChange,
    onPriorityChange,
    onCategoryChange,
    onAssignedChange,
}: TicketIndexFiltersProps) {
    return (
        <Card className="p-4 shadow-sm border-gray-200/80">
            <div className="flex flex-col lg:flex-row gap-4">
                <form
                    onSubmit={onSearchSubmit}
                    className="flex-1 flex gap-2"
                >
                    <div className="relative flex-1">
                        <Search className="absolute top-1/2 left-3 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <Input
                            placeholder="Search tickets..."
                            value={search}
                            onChange={(event) => onSearchChange(event.target.value)}
                            className="pl-10"
                        />
                    </div>
                    <Button
                        type="submit"
                        size="sm"
                    >
                        Search
                    </Button>
                </form>

                <div className="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                    {statusTabs.map((tab) => (
                        <button
                            key={tab.id}
                            onClick={() => onStatusChange(tab.id as TicketStatus | '')}
                            className={cn(
                                'px-3 py-1.5 text-sm font-medium rounded-md transition-all',
                                status === tab.id
                                    ? 'bg-white text-primary shadow-sm'
                                    : 'text-gray-600 hover:text-gray-900',
                            )}
                        >
                            {tab.label}
                        </button>
                    ))}
                </div>

                <div className="flex items-center gap-2 flex-wrap">
                    <Select
                        value={priority}
                        onChange={(value: string | number) => onPriorityChange(String(value))}
                        options={priorityOptions}
                        placeholder="Priority"
                        className="w-32"
                    />
                    <Select
                        value={categoryId}
                        onChange={(value: string | number) => onCategoryChange(String(value))}
                        options={categoryOptions}
                        placeholder="Category"
                        className="w-40"
                    />
                    <Select
                        value={assignedUserId}
                        onChange={(value: string | number) => onAssignedChange(String(value))}
                        options={staffOptions}
                        placeholder="Assigned"
                        className="w-40"
                    />
                </div>
            </div>
        </Card>
    );
}

import { Calendar } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/Card';
import { cn } from '@/lib/utils';

interface PeriodPreset {
    label: string;
    getRange: () => { from: string; to: string };
}

interface ReportingFiltersProps {
    dateFrom: string;
    dateTo: string;
    periodPresets: PeriodPreset[];
    onDateFromChange: (value: string) => void;
    onDateToChange: (value: string) => void;
    onPreset: (preset: PeriodPreset) => void;
    onApply: () => void;
}

export default function ReportingFilters({
    dateFrom,
    dateTo,
    periodPresets,
    onDateFromChange,
    onDateToChange,
    onPreset,
    onApply,
}: ReportingFiltersProps) {
    return (
        <Card className="border-gray-200 bg-white p-2 shadow-none">
            <div className="flex flex-col gap-2 lg:flex-row lg:items-center">
                <div className="flex items-center gap-1.5 flex-wrap">
                    {periodPresets.map((preset) => (
                        <button
                            key={preset.label}
                            onClick={() => onPreset(preset)}
                            className={cn(
                                'h-8 rounded-md border px-3 text-xs font-medium transition-colors',
                                dateFrom === preset.getRange().from && dateTo === preset.getRange().to
                                    ? 'border-primary/20 bg-primary/10 text-primary'
                                    : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                            )}
                        >
                            {preset.label}
                        </button>
                    ))}
                </div>

                <div className="hidden h-7 w-px bg-gray-200 lg:block" />

                <div className="flex items-center gap-2 border-t border-gray-100 pt-2 lg:border-t-0 lg:pt-0">
                    <Calendar
                        className="h-4 w-4 flex-shrink-0 text-gray-400"
                        strokeWidth={1.5}
                    />
                    <input
                        type="date"
                        value={dateFrom}
                        onChange={(event) => onDateFromChange(event.target.value)}
                        className="h-8 w-32 rounded-md border border-gray-200 bg-white px-2 text-xs focus:border-gray-400 focus:outline-none focus:ring-0"
                    />
                    <span className="text-xs text-gray-300 font-medium">—</span>
                    <input
                        type="date"
                        value={dateTo}
                        onChange={(event) => onDateToChange(event.target.value)}
                        className="h-8 w-32 rounded-md border border-gray-200 bg-white px-2 text-xs focus:border-gray-400 focus:outline-none focus:ring-0"
                    />
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={onApply}
                        className="h-8 text-xs"
                    >
                        Apply
                    </Button>
                </div>
            </div>
        </Card>
    );
}

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
        <Card className="p-3 shadow-sm border-gray-200/80">
            <div className="flex flex-col lg:flex-row lg:items-center gap-3">
                <div className="flex items-center gap-1.5 flex-wrap">
                    {periodPresets.map((preset) => (
                        <button
                            key={preset.label}
                            onClick={() => onPreset(preset)}
                            className={cn(
                                'h-7 px-3 text-xs font-medium rounded-md border transition-all cursor-pointer',
                                dateFrom === preset.getRange().from && dateTo === preset.getRange().to
                                    ? 'bg-primary text-white border-primary shadow-sm'
                                    : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                            )}
                        >
                            {preset.label}
                        </button>
                    ))}
                </div>

                <div className="w-px h-7 bg-gray-200 hidden lg:block" />

                <div className="flex items-center gap-2">
                    <Calendar
                        className="w-4 h-4 text-gray-400 flex-shrink-0"
                        strokeWidth={1.5}
                    />
                    <input
                        type="date"
                        value={dateFrom}
                        onChange={(event) => onDateFromChange(event.target.value)}
                        className="w-[130px] text-xs h-8 rounded-md border border-gray-200 bg-white px-2 focus:border-primary focus:ring-1 focus:ring-primary/20 focus:outline-none"
                    />
                    <span className="text-xs text-gray-300 font-medium">—</span>
                    <input
                        type="date"
                        value={dateTo}
                        onChange={(event) => onDateToChange(event.target.value)}
                        className="w-[130px] text-xs h-8 rounded-md border border-gray-200 bg-white px-2 focus:border-primary focus:ring-1 focus:ring-primary/20 focus:outline-none"
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

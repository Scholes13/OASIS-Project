import { format } from 'date-fns';
import { id as idLocale } from 'date-fns/locale';
import { ChevronLeft, ChevronRight, Plus } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';

type CalendarView = 'dayGridMonth' | 'timeGridWeek' | 'timeGridDay';

interface CalendarHeaderProps {
    currentDate: Date;
    currentView: CalendarView;
    onPrev: () => void;
    onNext: () => void;
    onToday: () => void;
    onViewChange: (view: CalendarView) => void;
    onCreateTask: () => void;
}

const viewOptions: Array<{ label: string; value: CalendarView; border?: string }> = [
    { label: 'Month', value: 'dayGridMonth' },
    { label: 'Week', value: 'timeGridWeek', border: 'border-x border-gray-200' },
    { label: 'Day', value: 'timeGridDay' },
];

export default function CalendarHeader({
    currentDate,
    currentView,
    onPrev,
    onNext,
    onToday,
    onViewChange,
    onCreateTask,
}: CalendarHeaderProps) {
    return (
        <div className="px-5 py-4 border-b border-gray-100">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div className="flex items-center gap-4">
                    <div className="flex items-center gap-1">
                        <button
                            onClick={onPrev}
                            className="p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                        >
                            <ChevronLeft className="h-5 w-5" />
                        </button>
                        <button
                            onClick={onToday}
                            className="px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
                        >
                            Today
                        </button>
                        <button
                            onClick={onNext}
                            className="p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                        >
                            <ChevronRight className="h-5 w-5" />
                        </button>
                    </div>
                    <h2 className="text-lg font-semibold text-gray-900">
                        {format(
                            currentDate,
                            currentView === 'dayGridMonth' ? 'MMMM yyyy' : 'd MMMM yyyy',
                            { locale: idLocale }
                        )}
                    </h2>
                </div>

                <div className="flex items-center gap-3">
                    <div className="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                        {viewOptions.map((option) => (
                            <button
                                key={option.value}
                                onClick={() => onViewChange(option.value)}
                                className={cn(
                                    'px-3 py-1.5 text-sm font-medium transition-colors',
                                    option.border,
                                    currentView === option.value
                                        ? 'bg-primary text-white'
                                        : 'bg-white text-gray-600 hover:bg-gray-50'
                                )}
                            >
                                {option.label}
                            </button>
                        ))}
                    </div>

                    <Button
                        variant="primary"
                        size="sm"
                        onClick={onCreateTask}
                        className="bg-primary hover:bg-blue-600"
                    >
                        <Plus className="h-4 w-4 mr-1" />
                        Add
                    </Button>
                </div>
            </div>
        </div>
    );
}

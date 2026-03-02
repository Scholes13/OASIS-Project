import * as React from 'react';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import { router } from '@inertiajs/react';
import { format } from 'date-fns';
import { id as idLocale } from 'date-fns/locale';
import {
    ChevronLeft,
    ChevronRight,
    Info,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import type { AdminTask } from './types';

interface PurchasingTaskCalendarProps {
    tasks: AdminTask[];
    onTaskClick?: (task: AdminTask) => void;
}

type CalendarView = 'dayGridMonth' | 'timeGridWeek' | 'timeGridDay';

// Status styling
const statusStyles: Record<string, { bg: string; text: string; border: string; label: string }> = {
    pending_followup: {
        bg: '#f1f5f9',
        text: '#334155',
        border: '#94a3b8',
        label: 'Pending'
    },
    in_progress: {
        bg: '#fef3c7',
        text: '#92400e',
        border: '#f59e0b',
        label: 'In Progress'
    },
    done: {
        bg: '#d1fae5',
        text: '#065f46',
        border: '#10b981',
        label: 'Completed'
    },
};

// Format currency
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

// Custom Event Content
function EventContent({ event, view }: { event: any; view: string }) {
    const task = event.extendedProps.task as AdminTask;
    const status = statusStyles[task.status] || statusStyles.pending_followup;
    const isMonthView = view === 'dayGridMonth';
    const isPR = task.taskable_type?.includes('PurchaseRequest');
    const number = isPR ? task.taskable?.pr_number : task.taskable?.st_number;

    if (isMonthView) {
        return (
            <div
                className={cn(
                    'flex items-center gap-1 px-1.5 py-0.5 text-[11px] rounded cursor-pointer transition-all hover:shadow-sm',
                    'border-l-[3px]'
                )}
                style={{
                    backgroundColor: status.bg,
                    borderLeftColor: status.border,
                }}
            >
                <span
                    className={cn(
                        'w-1.5 h-1.5 rounded-full flex-shrink-0',
                        task.status === 'done' ? 'bg-emerald-500' :
                            task.status === 'in_progress' ? 'bg-amber-500' : 'bg-slate-400'
                    )}
                />
                <span
                    className="font-semibold truncate"
                    style={{ color: status.text }}
                >
                    {number || 'Task'}
                </span>
            </div>
        );
    }

    // Week/Day view
    return (
        <div
            className="h-full p-2 text-xs overflow-hidden rounded cursor-pointer border-l-[3px]"
            style={{
                backgroundColor: status.bg,
                borderLeftColor: status.border,
            }}
        >
            <p className="font-semibold truncate" style={{ color: status.text }}>
                {number || 'Task'}
            </p>
            <p className="text-gray-600 truncate text-[10px]">
                {task.department?.name}
            </p>
            <p className="text-[10px] text-gray-500 mt-1">
                {formatCurrency(task.estimated_total_price || 0)}
            </p>
        </div>
    );
}

export function PurchasingTaskCalendar({ tasks, onTaskClick }: PurchasingTaskCalendarProps) {
    const calendarRef = React.useRef<FullCalendar>(null);
    const [currentView, setCurrentView] = React.useState<CalendarView>('dayGridMonth');
    const [currentDate, setCurrentDate] = React.useState(new Date());

    // Convert tasks to calendar events
    const events = React.useMemo(() => {
        return tasks.map((task) => ({
            id: String(task.id),
            title: task.taskable?.pr_number || task.taskable?.st_number || 'Task',
            start: task.entered_at,
            end: task.entered_at,
            allDay: true,
            extendedProps: {
                task,
                status: task.status,
            },
        }));
    }, [tasks]);

    // Navigation handlers
    const handlePrev = () => {
        const api = calendarRef.current?.getApi();
        api?.prev();
        setCurrentDate(api?.getDate() || new Date());
    };

    const handleNext = () => {
        const api = calendarRef.current?.getApi();
        api?.next();
        setCurrentDate(api?.getDate() || new Date());
    };

    const handleToday = () => {
        const api = calendarRef.current?.getApi();
        api?.today();
        setCurrentDate(api?.getDate() || new Date());
    };

    const handleViewChange = (view: CalendarView) => {
        const api = calendarRef.current?.getApi();
        api?.changeView(view);
        setCurrentView(view);
    };

    const handleEventClick = (info: any) => {
        const task = info.event.extendedProps.task as AdminTask;
        if (onTaskClick) {
            onTaskClick(task);
        } else {
            router.visit(route('purchasing.admin.tasks.show', { taskId: task.id }));
        }
    };

    // Count tasks by status
    const statusCounts = React.useMemo(() => {
        const counts: Record<string, number> = {
            pending_followup: 0,
            in_progress: 0,
            done: 0,
        };
        tasks.forEach((task) => {
            if (counts[task.status] !== undefined) {
                counts[task.status]++;
            }
        });
        return counts;
    }, [tasks]);

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            {/* Header */}
            <div className="px-5 py-4 border-b border-gray-100">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    {/* Left: Navigation & Title */}
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-1">
                            <button
                                onClick={handlePrev}
                                className="p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                            >
                                <ChevronLeft className="h-5 w-5" />
                            </button>
                            <button
                                onClick={handleToday}
                                className="px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
                            >
                                Today
                            </button>
                            <button
                                onClick={handleNext}
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

                    {/* Right: Calendar View Switcher */}
                    <div className="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                        <button
                            onClick={() => handleViewChange('dayGridMonth')}
                            className={cn(
                                'px-3 py-1.5 text-sm font-medium transition-colors',
                                currentView === 'dayGridMonth'
                                    ? 'bg-primary text-white'
                                    : 'bg-white text-gray-600 hover:bg-gray-50'
                            )}
                        >
                            Month
                        </button>
                        <button
                            onClick={() => handleViewChange('timeGridWeek')}
                            className={cn(
                                'px-3 py-1.5 text-sm font-medium transition-colors border-x border-gray-200',
                                currentView === 'timeGridWeek'
                                    ? 'bg-primary text-white'
                                    : 'bg-white text-gray-600 hover:bg-gray-50'
                            )}
                        >
                            Week
                        </button>
                        <button
                            onClick={() => handleViewChange('timeGridDay')}
                            className={cn(
                                'px-3 py-1.5 text-sm font-medium transition-colors',
                                currentView === 'timeGridDay'
                                    ? 'bg-primary text-white'
                                    : 'bg-white text-gray-600 hover:bg-gray-50'
                            )}
                        >
                            Day
                        </button>
                    </div>
                </div>
            </div>

            {/* Status Legend */}
            <div className="px-5 py-2.5 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                <div className="flex items-center gap-4 text-xs">
                    {Object.entries(statusCounts).map(([status, count]) => (
                        <div key={status} className="flex items-center gap-1.5">
                            <span
                                className={cn(
                                    'w-2.5 h-2.5 rounded-full',
                                    status === 'done' ? 'bg-emerald-500' :
                                        status === 'in_progress' ? 'bg-amber-500' : 'bg-slate-400'
                                )}
                            />
                            <span className="text-gray-700 font-medium">
                                {statusStyles[status]?.label || status}
                            </span>
                            <span className="text-gray-500">({count})</span>
                        </div>
                    ))}
                </div>
                <div className="text-xs text-gray-600 font-medium">
                    {tasks.length} task{tasks.length !== 1 ? 's' : ''} total
                </div>
            </div>

            {/* Calendar */}
            <div className="p-4 calendar-container">
                <FullCalendar
                    ref={calendarRef}
                    plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin, listPlugin]}
                    initialView={currentView}
                    headerToolbar={false}
                    events={events}
                    selectable={false}
                    dayMaxEvents={4}
                    dayMaxEventRows={4}
                    moreLinkClick="popover"
                    weekends={true}
                    eventClick={handleEventClick}
                    eventContent={(arg) => (
                        <EventContent event={arg.event} view={currentView} />
                    )}
                    height="auto"
                    aspectRatio={1.5}
                    eventDisplay="block"
                    moreLinkContent={(args) => (
                        <div className="text-xs font-semibold text-primary hover:text-primary px-1.5 py-0.5 bg-primary rounded cursor-pointer">
                            +{args.num} more
                        </div>
                    )}
                    dayCellClassNames={(arg) =>
                        cn(
                            'transition-colors',
                            arg.isToday && '!bg-blue-100',
                            arg.isPast && !arg.isToday && 'bg-gray-50/30'
                        )
                    }
                    slotMinTime="06:00:00"
                    slotMaxTime="22:00:00"
                    allDaySlot={true}
                    nowIndicator={true}
                    stickyHeaderDates={true}
                    locale="id"
                    firstDay={0}
                />
            </div>

            {/* Footer Help */}
            <div className="px-5 py-3 border-t border-gray-100 bg-gray-50/50">
                <div className="flex items-center gap-6 text-xs text-gray-600">
                    <span className="flex items-center gap-1.5">
                        <Info className="h-3.5 w-3.5 text-gray-400" />
                        Click task to view details
                    </span>
                </div>
            </div>

            {/* Custom styles for FullCalendar */}
            <style>{`
                .calendar-container .fc {
                    font-family: inherit;
                }
                .calendar-container .fc-theme-standard td,
                .calendar-container .fc-theme-standard th {
                    border-color: #e5e7eb;
                }
                .calendar-container .fc-col-header-cell {
                    padding: 12px 0;
                    background: #f9fafb;
                }
                .calendar-container .fc-col-header-cell-cushion {
                    font-weight: 600;
                    color: #374151;
                    text-transform: uppercase;
                    font-size: 11px;
                    letter-spacing: 0.05em;
                }
                .calendar-container .fc-daygrid-day-number {
                    font-weight: 500;
                    color: #6b7280;
                    padding: 8px;
                }
                .calendar-container .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
                    background: #2563eb;
                    color: white;
                    border-radius: 9999px;
                    width: 28px;
                    height: 28px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .calendar-container .fc-daygrid-day-events {
                    padding: 2px 4px;
                }
                .calendar-container .fc-event {
                    border: none !important;
                    background: transparent !important;
                    margin-bottom: 2px;
                }
                .calendar-container .fc-event-main {
                    padding: 0;
                }
                .calendar-container .fc-daygrid-event-harness {
                    margin-top: 1px;
                }
                .calendar-container .fc-popover {
                    border-radius: 12px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
                    border: 1px solid #e5e7eb;
                    overflow: hidden;
                }
                .calendar-container .fc-popover-header {
                    background: #f9fafb;
                    padding: 10px 12px;
                    font-weight: 600;
                    color: #374151;
                }
                .calendar-container .fc-popover-body {
                    padding: 8px;
                    max-height: 300px;
                    overflow-y: auto;
                }
                .calendar-container .fc-highlight {
                    background: #dbeafe !important;
                }
            `}</style>
        </div>
    );
}

export default PurchasingTaskCalendar;

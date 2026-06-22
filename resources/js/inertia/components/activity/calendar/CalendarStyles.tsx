export default function CalendarStyles() {
    return (
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
            .calendar-container .fc-more-link {
                margin-top: 2px;
            }
            .calendar-container .fc-daygrid-more-link {
                background: transparent !important;
            }
            .calendar-container .fc-highlight {
                background: #dbeafe !important;
            }
        `}</style>
    );
}

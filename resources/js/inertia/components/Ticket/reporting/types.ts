export interface SlaPriorityData {
    priority: string;
    label: string;
    total: number;
    within_sla: number;
    breached: number;
    sla_hours: number;
    avg_hours: number;
    compliance_rate: number;
}

export interface SlaCompliance {
    rate: number;
    total_resolved: number;
    within_sla: number;
    breached: number;
    by_priority: SlaPriorityData[];
}

export interface ReportData {
    total_tickets: number;
    resolved_tickets: number;
    avg_resolution_hours: number;
    by_status: { name: string; value: number; color: string }[];
    by_priority: { name: string; count: number; color: string }[];
    by_category: { name: string; count: number; color: string }[];
    by_staff: { name: string; count: number; color: string }[];
    daily_trend: { date: string; total: number; resolved: number }[];
    sla_compliance?: SlaCompliance;
}

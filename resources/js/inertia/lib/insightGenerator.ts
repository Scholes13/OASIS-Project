type ViewMode = 'personal' | 'department' | 'executive';

interface FocusSummary {
    name: string;
    percentage_of_report: number;
}

export function generateInsight(focus: FocusSummary | undefined, currentViewMode: ViewMode) {
    if (!focus || Number(focus.percentage_of_report || 0) === 0) {
        return 'No insight available yet. Complete more tasks to build recommendations.';
    }

    const name = focus.name;
    const p = Number(focus.percentage_of_report || 0);

    if (currentViewMode === 'department') {
        if (p > 60) return `Warning: Department workload is heavily concentrated on ${name} (${p}%). High risk of bottleneck, consider reassigning tasks to balance capacity.`;
        if (p > 40) return `${name} is currently dominating the department's focus (${p}%). Ensure other strategic priorities are not being neglected.`;
        if (p > 25) return `Department focus is relatively balanced, with ${name} leading slightly at ${p}%. This indicates healthy task distribution.`;
        return `Department workload is highly diversified. ${name} leads with only ${p}%, indicating a wide spread of active projects.`;
    }

    if (p > 60) return `Warning: Your personal workload is heavily focused on ${name} (${p}%). Be careful of burnout in this area and consider delegating if possible.`;
    if (p > 40) return `You are currently dedicating ${p}% of your effort to ${name}. Ensure this aligns with your primary objectives for this period.`;
    if (p > 25) return `Your time is fairly balanced, with ${name} taking up ${p}%. This is a good mix of responsibilities.`;
    return `You are juggling multiple priorities. ${name} is your top focus but only takes ${p}%, indicating highly fragmented attention.`;
}

export default generateInsight;

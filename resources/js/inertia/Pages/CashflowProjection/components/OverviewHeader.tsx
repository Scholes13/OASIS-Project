import { Bell, Calendar } from 'lucide-react';

interface OverviewHeaderProps {
    year: number;
    selectedMonth: number;
    monthOptions: ReadonlyArray<{ value: number; label: string }>;
    onMonthChange: (month: number) => void;
    userName?: string | null;
    userAvatarUrl?: string | null;
}

export default function OverviewHeader({
    year,
    selectedMonth,
    monthOptions,
    onMonthChange,
    userName,
    userAvatarUrl,
}: OverviewHeaderProps) {
    const displayName = userName?.trim() || 'User';
    const initials = displayName
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');

    return (
        <header className="cfp-top-bar">
            <div className="cfp-page-header">
                <h1>Dashboard Overview</h1>
            </div>

            <div className="cfp-actions-group">
                <div className="cfp-date-picker">
                    <Calendar className="h-4 w-4" />
                    <select value={selectedMonth} onChange={(event) => onMonthChange(Number(event.target.value))}>
                        {monthOptions.map((month) => (
                            <option key={month.value} value={month.value}>
                                {month.label} {year}
                            </option>
                        ))}
                    </select>
                </div>

                <button type="button" className="cfp-icon-btn">
                    <Bell className="h-5 w-5" />
                </button>

                <div className="cfp-user-pill">
                    {userAvatarUrl ? (
                        <img src={userAvatarUrl} alt={displayName} className="cfp-avatar" />
                    ) : (
                        <div className="cfp-avatar-fallback">{initials || 'U'}</div>
                    )}
                    <span className="cfp-user-name">{displayName}</span>
                </div>
            </div>
        </header>
    );
}

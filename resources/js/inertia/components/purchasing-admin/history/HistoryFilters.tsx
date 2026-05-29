import type { AdminOption, HistoryFiltersState } from './historyUtils';

interface HistoryFiltersProps {
    filters: HistoryFiltersState;
    admins?: AdminOption[];
    onChange: (key: keyof HistoryFiltersState, value: string) => void;
}

export function HistoryFilters({ filters, admins = [], onChange }: HistoryFiltersProps) {
    const columnClass = admins.length > 0 ? 'lg:grid-cols-5' : 'lg:grid-cols-4';

    return (
        <div className="px-5 py-4 bg-gray-50 border-b border-gray-100">
            <div className={`grid grid-cols-1 sm:grid-cols-2 ${columnClass} gap-4`}>
                <div>
                    <label className="block text-xs font-medium text-gray-700 mb-1">From Date</label>
                    <input
                        type="date"
                        value={filters.date_from}
                        onChange={(e) => onChange('date_from', e.target.value)}
                        className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                    />
                </div>

                <div>
                    <label className="block text-xs font-medium text-gray-700 mb-1">To Date</label>
                    <input
                        type="date"
                        value={filters.date_to}
                        onChange={(e) => onChange('date_to', e.target.value)}
                        className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                    />
                </div>

                <div>
                    <label className="block text-xs font-medium text-gray-700 mb-1">Status</label>
                    <select
                        value={filters.status}
                        onChange={(e) => onChange('status', e.target.value)}
                        className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                    >
                        <option value="all">All Statuses</option>
                        <option value="pending_followup">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="done">Completed</option>
                    </select>
                </div>

                <div>
                    <label className="block text-xs font-medium text-gray-700 mb-1">Type</label>
                    <select
                        value={filters.type}
                        onChange={(e) => onChange('type', e.target.value)}
                        className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                    >
                        <option value="all">All Types</option>
                        <option value="purchase_request">Purchase Request</option>
                        <option value="stock_request">Stock Request</option>
                    </select>
                </div>

                {admins.length > 0 && (
                    <div>
                        <label className="block text-xs font-medium text-gray-700 mb-1">Admin</label>
                        <select
                            value={filters.admin || 'all'}
                            onChange={(e) => onChange('admin', e.target.value)}
                            className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                        >
                            <option value="all">All Admins</option>
                            {admins.map((admin) => (
                                <option key={admin.id} value={admin.id.toString()}>
                                    {admin.name}
                                </option>
                            ))}
                        </select>
                    </div>
                )}
            </div>
        </div>
    );
}

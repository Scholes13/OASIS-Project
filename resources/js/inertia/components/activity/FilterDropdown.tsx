import { Fragment, useState } from 'react';
import { Popover, Transition } from '@headlessui/react';
import { SlidersHorizontal, X } from 'lucide-react';
import type { TaskFilters, ActivityType } from '@/types';

interface FilterDropdownProps {
    filters: TaskFilters;
    onChange: (filters: TaskFilters) => void;
    activityTypes: ActivityType[];
    isFiltering?: boolean;
}

export default function FilterDropdown({ filters, onChange, activityTypes, isFiltering }: FilterDropdownProps) {
    const [localFilters, setLocalFilters] = useState(filters);

    const handleChange = (key: keyof TaskFilters, value: string) => {
        const newFilters = { ...localFilters, [key]: value };
        setLocalFilters(newFilters);
        onChange(newFilters);
    };

    const handleReset = () => {
        const threeMonthsAgo = new Date();
        threeMonthsAgo.setMonth(threeMonthsAgo.getMonth() - 3);
        
        const resetFilters: TaskFilters = {
            search: '',
            activity_type_id: '',
            status: '',
            date_from: threeMonthsAgo.toISOString().split('T')[0],
            date_to: new Date().toISOString().split('T')[0],
        };
        setLocalFilters(resetFilters);
        onChange(resetFilters);
    };

    const hasActiveFilters = filters.activity_type_id || filters.status || filters.search;

    return (
        <div className="flex items-center gap-2">
            {/* Filter Button */}
            <Popover className="relative">
                {({ open }) => (
                    <>
                        <Popover.Button className="inline-flex items-center px-3 py-2 text-sm text-gray-600 hover:text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary shadow-sm">
                            <SlidersHorizontal className="w-4 h-4 mr-1.5" strokeWidth={1.5} />
                            Filter
                            {hasActiveFilters && (
                                <span className="ml-1.5 w-1.5 h-1.5 bg-blue-500 rounded-full" />
                            )}
                        </Popover.Button>

                        <Transition
                            as={Fragment}
                            enter="transition ease-out duration-200"
                            enterFrom="opacity-0 translate-y-1"
                            enterTo="opacity-100 translate-y-0"
                            leave="transition ease-in duration-150"
                            leaveFrom="opacity-100 translate-y-0"
                            leaveTo="opacity-0 translate-y-1"
                        >
                            <Popover.Panel className="absolute right-0 z-10 mt-2 w-72 bg-white rounded-xl shadow-lg border border-gray-100 p-4">
                                <div className="space-y-3">
                                    {/* Activity Type */}
                                    <div>
                                        <label className="block text-xs font-medium text-gray-500 mb-1.5">
                                            Activity Type
                                        </label>
                                        <select
                                            value={localFilters.activity_type_id}
                                            onChange={(e) => handleChange('activity_type_id', e.target.value)}
                                            className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white"
                                        >
                                            <option value="">All Types</option>
                                            {(activityTypes ?? []).map((type) => (
                                                <option key={type.id} value={type.id}>
                                                    {type.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    {/* Status */}
                                    <div>
                                        <label className="block text-xs font-medium text-gray-500 mb-1.5">
                                            Status
                                        </label>
                                        <select
                                            value={localFilters.status}
                                            onChange={(e) => handleChange('status', e.target.value)}
                                            className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white"
                                        >
                                            <option value="">All Status</option>
                                            <option value="planned">Planned</option>
                                            <option value="in_progress">In Progress</option>
                                            <option value="completed">Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>

                                    {/* Date Range */}
                                    <div className="grid grid-cols-2 gap-2">
                                        <div>
                                            <label className="block text-xs font-medium text-gray-500 mb-1.5">
                                                From
                                            </label>
                                            <input
                                                type="date"
                                                value={localFilters.date_from}
                                                onChange={(e) => handleChange('date_from', e.target.value)}
                                                className="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-xs font-medium text-gray-500 mb-1.5">
                                                To
                                            </label>
                                            <input
                                                type="date"
                                                value={localFilters.date_to}
                                                onChange={(e) => handleChange('date_to', e.target.value)}
                                                className="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white"
                                            />
                                        </div>
                                    </div>

                                    {/* Clear Button */}
                                    <button
                                        type="button"
                                        onClick={handleReset}
                                        className="w-full px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors border border-gray-200"
                                    >
                                        Clear Filters
                                    </button>
                                </div>
                            </Popover.Panel>
                        </Transition>
                    </>
                )}
            </Popover>
        </div>
    );
}

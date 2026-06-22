import { Building2, Search } from 'lucide-react';

import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';

interface BusinessUnit {
    id: number;
    name: string;
    code: string;
}

interface ActivityConfigurationFiltersProps {
    search: string;
    businessUnitId: string;
    businessUnits: BusinessUnit[];
    isSuperAdmin: boolean;
    onSearchChange: (value: string) => void;
    onBusinessUnitChange: (value: string | number) => void;
}

export function ActivityConfigurationFilters({
    search,
    businessUnitId,
    businessUnits,
    isSuperAdmin,
    onSearchChange,
    onBusinessUnitChange,
}: ActivityConfigurationFiltersProps) {
    return (
        <div className="bg-white rounded-xl border border-gray-200 p-4">
            <div
                className={`grid grid-cols-1 gap-4 ${
                    isSuperAdmin ? 'md:grid-cols-2' : 'md:grid-cols-1'
                }`}
            >
                <div className="relative">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <Input
                        type="text"
                        placeholder="Search activity types or sub-activities..."
                        value={search}
                        onChange={(event) => onSearchChange(event.target.value)}
                        className="pl-10"
                    />
                </div>
                {isSuperAdmin && businessUnits.length > 0 && (
                    <div className="relative">
                        <Building2 className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none z-10" />
                        <Select
                            value={businessUnitId}
                            onChange={onBusinessUnitChange}
                            options={[
                                { value: '', label: 'All Business Units' },
                                ...businessUnits.map((businessUnit) => ({
                                    value: businessUnit.id.toString(),
                                    label: `${businessUnit.code} - ${businessUnit.name}`,
                                })),
                            ]}
                            placeholder="All Business Units"
                            className="pl-10"
                        />
                    </div>
                )}
            </div>
        </div>
    );
}

export default ActivityConfigurationFilters;

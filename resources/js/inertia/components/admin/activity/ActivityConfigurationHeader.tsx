import { Plus } from 'lucide-react';

import { Button } from '@/components/ui/button';

interface ActivityConfigurationHeaderProps {
    isSuperAdmin: boolean;
    onCreateActivityType: () => void;
}

export function ActivityConfigurationHeader({
    isSuperAdmin,
    onCreateActivityType,
}: ActivityConfigurationHeaderProps) {
    return (
        <div className="flex items-center justify-between">
            <div>
                <h1 className="text-2xl font-bold text-gray-900">
                    Activity Configuration
                </h1>
                <p className="text-sm text-gray-600 mt-1">
                    {isSuperAdmin
                        ? 'Manage activity types, sub-activities, and department assignments'
                        : 'Manage activity types and sub-activities for your department'}
                </p>
            </div>
            <Button onClick={onCreateActivityType}>
                <Plus className="w-4 h-4 mr-2" />
                Add Activity Type
            </Button>
        </div>
    );
}

export default ActivityConfigurationHeader;

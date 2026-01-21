import { Fragment } from 'react';
import { Menu, Transition } from '@headlessui/react';
import { ChevronDown, Check, Loader2 } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { PageProps } from '../../types';
import { cn } from '../../lib/utils';
import { LazyLogo } from '../ui/LazyImage';
import { useBusinessUnit } from '../../hooks/useBusinessUnit';

export default function BusinessUnitSwitcher() {
    const { currentBusinessUnit, availableBusinessUnits } = usePage<PageProps>().props;
    const { switchBusinessUnit, isSwitching } = useBusinessUnit();

    // Hide if only one business unit or no business units
    if (!availableBusinessUnits || availableBusinessUnits.length <= 1) {
        return null;
    }

    const handleSwitch = async (businessUnitId: number) => {
        if (businessUnitId === currentBusinessUnit?.id || isSwitching) {
            return;
        }

        try {
            await switchBusinessUnit(businessUnitId);
        } catch {
            // Error is handled in the hook
        }
    };

    return (
        <Menu as="div" className="relative">
            <Menu.Button
                className={cn(
                    "flex items-center space-x-2 px-3 py-2 rounded-lg transition-colors",
                    isSwitching
                        ? "bg-gray-100 cursor-wait"
                        : "hover:bg-gray-100"
                )}
                disabled={isSwitching}
            >
                {isSwitching ? (
                    <Loader2 className="w-6 h-6 animate-spin text-indigo-600" />
                ) : (
                    <LazyLogo
                        src={currentBusinessUnit?.logo}
                        alt={currentBusinessUnit?.name || 'Business Unit'}
                        className="w-6 h-6"
                        fallbackText={currentBusinessUnit?.code}
                    />
                )}
                <div className="text-left">
                    <div className="text-sm font-medium text-gray-900">
                        {currentBusinessUnit?.name || 'Select Business Unit'}
                    </div>
                    {currentBusinessUnit?.code && (
                        <div className="text-xs text-gray-500">{currentBusinessUnit.code}</div>
                    )}
                </div>
                <ChevronDown className={cn(
                    "w-4 h-4 text-gray-500 transition-transform",
                    isSwitching && "opacity-50"
                )} />
            </Menu.Button>

            <Transition
                as={Fragment}
                enter="transition ease-out duration-100"
                enterFrom="transform opacity-0 scale-95"
                enterTo="transform opacity-100 scale-100"
                leave="transition ease-in duration-75"
                leaveFrom="transform opacity-100 scale-100"
                leaveTo="transform opacity-0 scale-95"
            >
                <Menu.Items className="absolute right-0 mt-2 w-64 origin-top-right bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                    <div className="py-1">
                        <div className="px-4 py-2 border-b border-gray-100">
                            <p className="text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Switch Business Unit
                            </p>
                        </div>
                        {/* Sort to show active BU first */}
                        {[...availableBusinessUnits]
                            .sort((a, b) => {
                                // Active BU goes first
                                if (a.id === currentBusinessUnit?.id) return -1;
                                if (b.id === currentBusinessUnit?.id) return 1;
                                return 0;
                            })
                            .map((bu) => (
                                <Menu.Item key={bu.id}>
                                    {({ active }) => (
                                        <button
                                            onClick={() => handleSwitch(bu.id)}
                                            disabled={isSwitching}
                                            className={cn(
                                                'w-full flex items-center px-4 py-2.5 text-sm transition-colors',
                                                active ? 'bg-gray-50' : '',
                                                bu.id === currentBusinessUnit?.id
                                                    ? 'text-indigo-600 bg-indigo-50/50'
                                                    : 'text-gray-700',
                                                isSwitching && 'opacity-50 cursor-not-allowed'
                                            )}
                                        >
                                            <LazyLogo
                                                src={bu.logo}
                                                alt={bu.name}
                                                className="w-8 h-8 mr-3"
                                                fallbackText={bu.code}
                                            />
                                            <div className="flex-1 text-left">
                                                <div className="font-medium">{bu.name}</div>
                                                <div className="text-xs text-gray-500">{bu.code}</div>
                                            </div>
                                            {bu.id === currentBusinessUnit?.id && (
                                                <Check className="w-4 h-4 text-indigo-600" />
                                            )}
                                        </button>
                                    )}
                                </Menu.Item>
                            ))}
                    </div>
                </Menu.Items>
            </Transition>
        </Menu>
    );
}

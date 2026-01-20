import { Fragment } from 'react';
import { Menu, Transition } from '@headlessui/react';
import { ChevronDown, Check } from 'lucide-react';
import { router, usePage } from '@inertiajs/react';
import { PageProps } from '../../types';
import { cn } from '../../lib/utils';
import { LazyLogo } from '../ui/LazyImage';

export default function BusinessUnitSwitcher() {
    const { currentBusinessUnit, availableBusinessUnits } = usePage<PageProps>().props;

    // Hide if only one business unit or no business units
    if (!availableBusinessUnits || availableBusinessUnits.length <= 1) {
        return null;
    }

    const handleSwitch = (businessUnitId: number) => {
        if (businessUnitId === currentBusinessUnit?.id) {
            return;
        }

        router.post(
            '/api/business-unit/switch',
            { business_unit_id: businessUnitId },
            {
                preserveState: false,
                preserveScroll: false,
                onSuccess: () => {
                    // Page will reload with new business unit context
                },
            }
        );
    };

    return (
        <Menu as="div" className="relative">
            <Menu.Button className="flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                <LazyLogo
                    src={currentBusinessUnit?.logo}
                    alt={currentBusinessUnit?.name || 'Business Unit'}
                    className="w-6 h-6"
                    fallbackText={currentBusinessUnit?.code}
                />
                <div className="text-left">
                    <div className="text-sm font-medium text-gray-900">
                        {currentBusinessUnit?.name || 'Select Business Unit'}
                    </div>
                    {currentBusinessUnit?.code && (
                        <div className="text-xs text-gray-500">{currentBusinessUnit.code}</div>
                    )}
                </div>
                <ChevronDown className="w-4 h-4 text-gray-500" />
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
                <Menu.Items className="absolute right-0 mt-2 w-64 origin-top-right bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                    <div className="py-1">
                        {availableBusinessUnits.map((bu) => (
                            <Menu.Item key={bu.id}>
                                {({ active }) => (
                                    <button
                                        onClick={() => handleSwitch(bu.id)}
                                        className={cn(
                                            'w-full flex items-center px-4 py-2 text-sm',
                                            active ? 'bg-gray-100' : '',
                                            bu.id === currentBusinessUnit?.id ? 'text-indigo-600' : 'text-gray-700'
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

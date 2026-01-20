import { Fragment } from 'react';
import { Menu, Transition } from '@headlessui/react';
import { User, LogOut, Settings } from 'lucide-react';
import { Link, router, usePage } from '@inertiajs/react';
import { PageProps } from '../../types';
import { cn } from '../../lib/utils';
import { LazyAvatar } from '../ui/LazyImage';

export default function UserMenu() {
    const { auth } = usePage<PageProps>().props;
    const user = auth.user;

    if (!user) {
        return null;
    }

    const handleLogout = () => {
        router.post('/logout');
    };

    return (
        <Menu as="div" className="relative">
            <Menu.Button className="flex items-center space-x-2 p-1 rounded-lg hover:bg-gray-100 transition-colors">
                <LazyAvatar
                    src={user.avatar_url}
                    alt={user.name}
                    name={user.name}
                    size="md"
                />
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
                <Menu.Items className="absolute right-0 mt-2 w-56 origin-top-right bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                    {/* User Info */}
                    <div className="px-4 py-3 border-b border-gray-100">
                        <div className="text-sm font-medium text-gray-900">{user.name}</div>
                        <div className="text-xs text-gray-500">{user.email}</div>
                        {user.role && (
                            <div className="mt-1">
                                <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                    {user.role}
                                </span>
                            </div>
                        )}
                    </div>

                    {/* Menu Items */}
                    <div className="py-1">
                        <Menu.Item>
                            {({ active }) => (
                                <Link
                                    href="/profile"
                                    className={cn(
                                        'flex items-center px-4 py-2 text-sm',
                                        active ? 'bg-gray-100 text-gray-900' : 'text-gray-700'
                                    )}
                                >
                                    <User className="w-4 h-4 mr-3" />
                                    Profile
                                </Link>
                            )}
                        </Menu.Item>

                        <Menu.Item>
                            {({ active }) => (
                                <button
                                    onClick={handleLogout}
                                    className={cn(
                                        'w-full flex items-center px-4 py-2 text-sm',
                                        active ? 'bg-gray-100 text-gray-900' : 'text-gray-700'
                                    )}
                                >
                                    <LogOut className="w-4 h-4 mr-3" />
                                    Logout
                                </button>
                            )}
                        </Menu.Item>
                    </div>
                </Menu.Items>
            </Transition>
        </Menu>
    );
}

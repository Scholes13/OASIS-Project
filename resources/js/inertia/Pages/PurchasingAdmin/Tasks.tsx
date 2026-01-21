import React, { useState, useMemo } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import {
    List,
    LayoutGrid,
    Calendar,
    GitBranch,
    RefreshCw,
} from 'lucide-react';
import { PageProps } from '../../types';
import { cn } from '../../lib/utils';
import { showToast } from '@/components/ui/toast';
import { PurchasingTaskList } from '@/components/purchasing-admin/PurchasingTaskList';
import { PurchasingTaskBoard } from '@/components/purchasing-admin/PurchasingTaskBoard';
import { PurchasingTaskCalendar } from '@/components/purchasing-admin/PurchasingTaskCalendar';
import { PurchasingTaskTimeline } from '@/components/purchasing-admin/PurchasingTaskTimeline';
import type { AdminTask, TaskFilters, TaskCounts, ViewMode } from '@/components/purchasing-admin/types';

interface TasksProps extends PageProps {
    tasks: {
        data: AdminTask[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
    };
    allTasks: AdminTask[];
    filters: TaskFilters;
    counts: TaskCounts;
    debug?: {
        session_bu_id: number | null;
        user_id: number;
        total_admin_tasks: number;
        tasks_in_current_bu: number;
        pending_in_current_bu: number;
        all_bu_ids_in_tasks: number[];
    };
}

const viewModes = [
    { id: 'list' as ViewMode, icon: List, label: 'List' },
    { id: 'board' as ViewMode, icon: LayoutGrid, label: 'Board' },
    { id: 'calendar' as ViewMode, icon: Calendar, label: 'Calendar' },
    { id: 'timeline' as ViewMode, icon: GitBranch, label: 'Timeline' },
];

export default function Tasks({ tasks, allTasks, filters, counts, debug }: TasksProps) {
    // Get view from URL or default to 'list'
    const url = new URL(window.location.href);
    const initialView = (url.searchParams.get('view') as ViewMode) || 'list';
    const [viewMode, setViewMode] = useState<ViewMode>(initialView);
    const [isRefreshing, setIsRefreshing] = useState(false);

    // Handle view mode change
    const handleViewChange = (view: ViewMode) => {
        setViewMode(view);
        // Update URL without full page reload
        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('view', view);
        window.history.pushState({}, '', newUrl.toString());
    };

    // Handle refresh
    const handleRefresh = () => {
        setIsRefreshing(true);
        router.reload({
            onFinish: () => {
                setIsRefreshing(false);
                showToast.success('Refreshed', 'Tasks have been updated');
            },
        });
    };

    // Handle claim task
    const handleClaim = (taskId: number) => {
        router.post(route('purchasing.admin.tasks.claim', { taskId }), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showToast.success('Task claimed', 'You have claimed this task');
            },
            onError: () => {
                showToast.error('Failed to claim task');
            },
        });
    };

    // Handle start task
    const handleStart = (taskId: number) => {
        router.post(route('purchasing.admin.tasks.start', { taskId }), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showToast.success('Task started', 'Task is now in progress');
            },
            onError: () => {
                showToast.error('Failed to start task');
            },
        });
    };

    // All tasks for board/calendar/timeline come from backend without status filter
    // This ensures Board/Calendar/Timeline always show all tasks grouped by status
    const allTasksData = allTasks || [];

    return (
        <>
            <Head title="Task Management" />
            <div className="py-6">
                <div className="w-full px-4 sm:px-6 lg:px-8">
                    {/* Debug Info - Remove after confirmation */}
                    {debug && (
                        <div className="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-sm">
                            <h3 className="font-bold text-yellow-800 mb-2">🔍 Debug Info</h3>
                            <div className="grid grid-cols-2 md:grid-cols-3 gap-2 text-yellow-700">
                                <div>Session BU ID: <span className="font-mono">{debug.session_bu_id ?? 'NULL'}</span></div>
                                <div>User ID: <span className="font-mono">{debug.user_id}</span></div>
                                <div>Total AdminTasks: <span className="font-mono">{debug.total_admin_tasks}</span></div>
                                <div>Tasks in Current BU: <span className="font-mono">{debug.tasks_in_current_bu}</span></div>
                                <div>Pending in Current BU: <span className="font-mono">{debug.pending_in_current_bu}</span></div>
                                <div>All BU IDs: <span className="font-mono">[{debug.all_bu_ids_in_tasks.join(', ')}]</span></div>
                            </div>
                        </div>
                    )}

                    {/* Header */}
                    <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Task Management</h1>
                            <p className="mt-1 text-sm text-gray-600">
                                Manage and track your purchasing tasks
                            </p>
                        </div>

                        <div className="flex items-center gap-3">
                            {/* View Mode Toggle */}
                            <div className="flex items-center bg-gray-100 rounded-lg p-1">
                                {viewModes.map((mode) => (
                                    <button
                                        key={mode.id}
                                        onClick={() => handleViewChange(mode.id)}
                                        className={cn(
                                            'flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md transition-all',
                                            viewMode === mode.id
                                                ? 'bg-white text-indigo-600 shadow-sm'
                                                : 'text-gray-600 hover:text-gray-900'
                                        )}
                                        title={mode.label}
                                    >
                                        <mode.icon className="h-4 w-4" />
                                        <span className="hidden sm:inline">{mode.label}</span>
                                    </button>
                                ))}
                            </div>

                            {/* Refresh Button */}
                            <button
                                onClick={handleRefresh}
                                disabled={isRefreshing}
                                className={cn(
                                    'p-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors',
                                    isRefreshing && 'animate-spin'
                                )}
                                title="Refresh"
                            >
                                <RefreshCw className="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    {/* View Content */}
                    {viewMode === 'list' && (
                        <PurchasingTaskList
                            tasks={tasks}
                            filters={filters}
                            counts={counts}
                            onClaim={handleClaim}
                            onStart={handleStart}
                        />
                    )}

                    {viewMode === 'board' && (
                        <PurchasingTaskBoard
                            tasks={allTasksData}
                            onClaim={handleClaim}
                            onStart={handleStart}
                        />
                    )}

                    {viewMode === 'calendar' && (
                        <PurchasingTaskCalendar
                            tasks={allTasksData}
                        />
                    )}

                    {viewMode === 'timeline' && (
                        <PurchasingTaskTimeline
                            tasks={allTasksData}
                        />
                    )}
                </div>
            </div>
        </>
    );
}

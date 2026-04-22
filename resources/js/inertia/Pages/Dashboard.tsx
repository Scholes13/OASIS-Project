import { Head, Link, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    Calendar,
    Check,
    CheckCircle2,
    ChevronRight,
    CircleDashed,
    ClipboardCheck,
    ClipboardList,
    FileClock,
    FilePlus2,
    Package,
    ShoppingCart,
    Box,
    Calculator,
} from 'lucide-react';
import { PageProps } from '@/types';

interface QuickAction {
    title: string;
    description: string;
    icon: string;
    url: string;
    color: string;
    badge?: number;
}

interface Activity {
    type: string;
    title: string;
    status: string;
    date: string;
    url: string;
}

interface DashboardProps {
    stats: {
        my_purchase_requests: number;
        my_stock_requests: number;
        pending_approvals: number;
        my_tasks: number;
    };
    recentActivities: Activity[];
    pendingApprovalsCount: number;
    quickActions: QuickAction[];
}

const quickIconMap: Record<string, any> = {
    'shopping-cart': FilePlus2,
    'package': Box,
    'clipboard-check': ClipboardCheck,
    'calendar': Calendar,
    'clipboard-list': Calculator,
};

const priorityBadgeMap: Record<string, string> = {
    in_approval: 'bg-red-100 text-red-700',
    submitted: 'bg-amber-100 text-amber-700',
    draft: 'bg-slate-100 text-slate-700',
    approved: 'bg-emerald-100 text-emerald-700',
    rejected: 'bg-red-100 text-red-700',
    voided: 'bg-slate-100 text-slate-700',
};

const statusToneMap: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-700',
    submitted: 'bg-blue-100 text-blue-700',
    in_approval: 'bg-amber-100 text-amber-700',
    approved: 'bg-emerald-100 text-emerald-700',
    rejected: 'bg-red-100 text-red-700',
    voided: 'bg-slate-100 text-slate-600',
};

export default function Dashboard({ stats, recentActivities, pendingApprovalsCount, quickActions }: DashboardProps) {
    const { auth } = usePage<PageProps>().props;

    const firstName = auth.user?.name?.split(' ')[0] ?? 'there';
    const currentHour = new Date().getHours();

    let greetLabel = 'Good Evening';
    if (currentHour < 12) greetLabel = 'Good Morning';
    else if (currentHour < 17) greetLabel = 'Good Afternoon';

    const overviewCards = [
        {
            title: 'Pending Approvals',
            value: stats.pending_approvals,
            helper: pendingApprovalsCount > 0 ? `${pendingApprovalsCount} waiting review` : 'No pending approval',
            icon: FileClock,
            iconBox: 'bg-[#dbeafe] text-[#1e40af]',
        },
        {
            title: 'My Active Tasks',
            value: stats.my_tasks,
            helper: stats.my_tasks > 0 ? `${stats.my_tasks} task(s) in progress` : 'All tasks completed or planned',
            icon: CheckCircle2,
            iconBox: 'bg-[#e0f2fe] text-[#0369a1]',
        },
        {
            title: 'My Purchase Requests',
            value: stats.my_purchase_requests,
            helper: 'Track request progress',
            icon: ShoppingCart,
            iconBox: 'bg-[#ecfdf5] text-[#047857]',
        },
        {
            title: 'My Stock Requests',
            value: stats.my_stock_requests,
            helper: 'Awaiting update status',
            icon: Package,
            iconBox: 'bg-slate-100 text-slate-600',
        },
    ];

    const priorityTasks = recentActivities.slice(0, 3);
    const activityTimeline = recentActivities.slice(0, 5);
    const quickLinks = quickActions.slice(0, 3);

    return (
        <>
            <Head title="Dashboard" />
            <div className="w-full px-6 py-8 lg:px-8 2xl:px-10">
                <div className="mx-auto w-full max-w-screen-2xl space-y-8">
                    <div className="space-y-1">
                        <p className="text-sm font-semibold text-slate-700">Dashboard Overview</p>
                        <h1 className="text-[2rem] font-bold text-foreground">{greetLabel}, {firstName}!</h1>
                        <p className="text-sm text-muted-foreground">Here's what's happening across your modules today.</p>
                    </div>

                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                        {overviewCards.map((card, index) => {
                            const Icon = card.icon;
                            return (
                                <motion.div
                                    key={card.title}
                                    initial={{ opacity: 0, y: 12 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ duration: 0.25, delay: index * 0.06 }}
                                    className="rounded-2xl border border-border bg-card p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)]"
                                >
                                    <div className={`mb-4 flex h-10 w-10 items-center justify-center rounded-lg text-base ${card.iconBox}`}>
                                        <Icon className="h-5 w-5" />
                                    </div>
                                    <p className="text-sm font-medium text-slate-500">{card.title}</p>
                                    <p className="mt-1 text-4xl font-bold text-foreground">{card.value}</p>
                                    <p className="mt-2 text-xs text-muted-foreground">{card.helper}</p>
                                </motion.div>
                            );
                        })}
                    </div>

                    <div className="grid grid-cols-1 gap-6 xl:grid-cols-3">
                        <div className="space-y-6 xl:col-span-2">
                            <motion.section
                                initial={{ opacity: 0, y: 12 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.25, delay: 0.25 }}
                                className="rounded-2xl border border-border bg-card p-6"
                            >
                                <div className="mb-4 flex items-center justify-between">
                                    <h2 className="text-xl font-semibold text-foreground">My Priority Tasks</h2>
                                    <Link href="/activity/tasks" className="text-sm font-semibold text-primary hover:opacity-80">
                                        View All Tasks
                                    </Link>
                                </div>

                                <div className="space-y-1">
                                    {priorityTasks.length === 0 ? (
                                        <div className="rounded-xl border border-dashed border-border bg-slate-50 p-4 text-sm text-muted-foreground">
                                            No priority items right now.
                                        </div>
                                    ) : (
                                        priorityTasks.map((task) => (
                                            <Link
                                                key={task.title}
                                                href={task.url}
                                                className="flex items-start gap-3 rounded-lg px-2 py-3 transition-colors hover:bg-slate-50"
                                            >
                                                <span className="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-md border border-border text-transparent">
                                                    <Check className="h-3.5 w-3.5" />
                                                </span>
                                                <span className="flex-1">
                                                    <span className="block text-sm font-semibold text-foreground">{task.title}</span>
                                                    <span className="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                                        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase ${priorityBadgeMap[task.status] || 'bg-slate-100 text-slate-700'}`}>
                                                            {task.status.replace('_', ' ')}
                                                        </span>
                                                        <span>{task.type === 'purchase_request' ? 'Purchase' : 'Stock'}</span>
                                                        <span>{task.date}</span>
                                                    </span>
                                                </span>
                                                <span className="mt-1 text-slate-400">...</span>
                                            </Link>
                                        ))
                                    )}
                                </div>
                            </motion.section>

                            <motion.section
                                initial={{ opacity: 0, y: 12 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.25, delay: 0.32 }}
                                className="rounded-2xl border border-border bg-card p-6"
                            >
                                <h2 className="mb-4 text-xl font-semibold text-foreground">Recent Activity</h2>

                                {activityTimeline.length === 0 ? (
                                    <div className="rounded-xl border border-dashed border-border bg-slate-50 p-4 text-sm text-muted-foreground">
                                        Activity feed is empty.
                                    </div>
                                ) : (
                                    <div className="relative pl-3">
                                        <div className="absolute bottom-2 left-[17px] top-2 w-px bg-border" />
                                        <div className="space-y-5">
                                            {activityTimeline.map((activity) => (
                                                <Link key={`${activity.type}-${activity.title}-${activity.date}`} href={activity.url} className="relative flex gap-3">
                                                    <span className="relative z-10 mt-0.5 inline-flex h-7 w-7 items-center justify-center rounded-full border-2 border-card bg-[#e8f3ff] text-primary">
                                                        {activity.status === 'approved' ? (
                                                            <CheckCircle2 className="h-4 w-4" />
                                                        ) : activity.status === 'in_approval' ? (
                                                            <CircleDashed className="h-4 w-4" />
                                                        ) : (
                                                            <ClipboardList className="h-4 w-4" />
                                                        )}
                                                    </span>
                                                    <span>
                                                        <span className="block text-sm text-foreground">
                                                            {activity.type === 'purchase_request' ? 'Purchase Request' : 'Stock Request'} <span className="font-semibold">{activity.title}</span>
                                                        </span>
                                                        <span className="mt-1 inline-flex items-center gap-2 text-xs text-muted-foreground">
                                                            <span className={`rounded-full px-2 py-0.5 ${statusToneMap[activity.status] || statusToneMap.draft}`}>
                                                                {activity.status.replace('_', ' ')}
                                                            </span>
                                                            <span>{activity.date}</span>
                                                        </span>
                                                    </span>
                                                </Link>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </motion.section>
                        </div>

                        <div className="space-y-6">
                            <motion.section
                                initial={{ opacity: 0, y: 12 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.25, delay: 0.35 }}
                                className="rounded-2xl border border-border bg-card p-5"
                            >
                                <h2 className="mb-4 text-xl font-semibold text-foreground">Quick Access</h2>
                                <div className="space-y-3">
                                    {quickLinks.length === 0 ? (
                                        <div className="rounded-lg border border-dashed border-border bg-slate-50 p-3 text-sm text-muted-foreground">
                                            No quick actions available.
                                        </div>
                                    ) : (
                                        quickLinks.map((action) => {
                                            const Icon = quickIconMap[action.icon] || FilePlus2;
                                            return (
                                                <Link
                                                    key={action.title}
                                                    href={action.url}
                                                    className="flex items-center gap-3 rounded-lg border border-transparent bg-slate-50 p-3 transition-all hover:border-[#dbeafe]"
                                                >
                                                    <span className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-card text-primary">
                                                        <Icon className="h-4 w-4" />
                                                    </span>
                                                    <span className="flex-1">
                                                        <span className="block text-sm font-semibold text-foreground">{action.title}</span>
                                                        <span className="block text-xs text-muted-foreground">{action.description}</span>
                                                    </span>
                                                    <ChevronRight className="h-4 w-4 text-slate-400" />
                                                </Link>
                                            );
                                        })
                                    )}
                                </div>
                            </motion.section>

                            <motion.section
                                initial={{ opacity: 0, y: 12 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.25, delay: 0.42 }}
                                className="rounded-2xl border border-transparent bg-primary p-5 text-primary-foreground"
                            >
                                <h2 className="text-xl font-semibold text-white">Need Help?</h2>
                                <p className="mt-2 text-sm text-white/90">
                                    Check out the documentation for the latest Purchasing workflows.
                                </p>
                                <Link
                                    href="/docs-help"
                                    className="mt-4 inline-flex items-center rounded-lg bg-white/20 px-3 py-2 text-sm font-semibold text-white transition hover:bg-white/30"
                                >
                                    View Documentation
                                </Link>
                            </motion.section>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

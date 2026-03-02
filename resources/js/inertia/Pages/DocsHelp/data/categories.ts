import type { Category } from './types';

export const categories: Category[] = [
    {
        key: 'getting-started',
        label: 'Getting Started',
        description: 'Learn the basics of OASIS, account setup, and quick navigation tips.',
        icon: 'Zap',
        color: 'indigo',
    },
    {
        key: 'purchase-request',
        label: 'Purchase Request',
        description: 'Guides on creating purchase requests and submission workflow.',
        icon: 'ShoppingCart',
        color: 'indigo',
    },
    {
        key: 'stock-request',
        label: 'Stock Request',
        description: 'Learn how to request consumable items and office supplies.',
        icon: 'Package',
        color: 'emerald',
    },
    {
        key: 'approvals',
        label: 'Approvals',
        description: 'Understanding the approval process, sequential flows, and offline approvals.',
        icon: 'CheckCircle',
        color: 'amber',
    },
    {
        key: 'activity-tracking',
        label: 'Activity Tracking',
        description: 'Managing tasks, timelines, team assignments, and reporting.',
        icon: 'ClipboardList',
        color: 'purple',
    },
    {
        key: 'dashboard',
        label: 'Dashboard',
        description: 'Understanding statistics, exports, and data filters.',
        icon: 'BarChart3',
        color: 'blue',
    },
    {
        key: 'faq',
        label: 'FAQ',
        description: 'Frequently asked questions and troubleshooting guides.',
        icon: 'HelpCircle',
        color: 'indigo',
    },
];

import type { Article } from '../types';

export const ActivityTrackingArticles: Article[] = [
    {
        id: 'activity-tracking-overview',
        category: 'activity-tracking',
        title: 'Activity Tracking',
        description: 'Mencatat, menugaskan, dan memantau pekerjaan/tugas di dalam tim secara real-time.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        toc: [
            { id: 'fitur-utama', label: 'Fitur Utama' },
            { id: 'views', label: 'Kanban vs List View' },
        ],
        content: [
            {
                type: 'heading',
                id: 'fitur-utama',
                level: 2,
                text: 'Fitur Utama',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong>Task Assignment:</strong> Memberikan tugas kepada anggota tim tertentu dengan tenggat waktu (due date).',
                    '<strong>Status Update:</strong> Mengubah status tugas dari <em>To Do</em>, <em>In Progress</em>, hingga <em>Done</em> menggunakan kanban board atau list view.',
                    '<strong>Time Logging:</strong> Mencatat waktu yang dihabiskan untuk menyelesaikan suatu tugas guna memonitor efisiensi dan beban kerja.',
                    '<strong>Backdated Task:</strong> Mencatat tugas yang sudah diselesaikan di masa lalu (bergantung pada konfigurasi kebijakan hari, umumnya maksimal mundur 3 hari).',
                ],
            },
            {
                type: 'heading',
                id: 'views',
                level: 2,
                text: 'Kanban vs List View',
            },
            {
                type: 'paragraph',
                html: 'OASIS menyediakan dua cara untuk melihat dan mengelola tugas:',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong>Kanban Board:</strong> Tampilan kolom visual (To Do → In Progress → Done). Cocok untuk melihat gambaran besar status semua tugas.',
                    '<strong>List View:</strong> Tampilan tabel dengan fitur sorting dan filter. Cocok untuk pencarian spesifik dan pengelolaan detail.',
                ],
            },
        ],
    },
    {
        id: 'backdated-task',
        category: 'activity-tracking',
        title: 'How to Create a Backdated Task',
        description: 'Cara mencatat tugas yang sudah dikerjakan di masa lalu.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        popular: true,
        toc: [
            { id: 'overview', label: 'Overview' },
            { id: 'prerequisites', label: 'Prerequisites' },
            { id: 'step-by-step-guide', label: 'Step-by-step Guide' },
            { id: 'troubleshooting', label: 'Troubleshooting' },
        ],
        content: [
            {
                type: 'paragraph',
                id: 'overview',
                html: 'Sometimes you might need to log work that was completed in the past. OASIS allows you to create tasks with a past date, subject to your department\'s configuration and approval policies.',
            },
            {
                type: 'callout',
                variant: 'info',
                title: 'Important Note',
                body: 'By default, you can only backdate tasks up to 3 working days. If you need to log activity older than that, you will be required to submit a Request Backdate Approval form.',
            },
            {
                type: 'heading',
                id: 'prerequisites',
                level: 2,
                text: 'Prerequisites',
            },
            {
                type: 'unordered-list',
                items: [
                    'You must have an active employee account.',
                    'You must be assigned to the relevant department for the task category.',
                ],
            },
            {
                type: 'heading',
                id: 'step-by-step-guide',
                level: 2,
                text: 'Step-by-step Guide',
            },
            {
                type: 'ordered-list',
                intro: 'Follow these steps to log a task that has already been completed:',
                items: [
                    'Navigate to My Tasks or the Activity Dashboard.',
                    'Click on the Create Task button in the top right corner.',
                    'In the Task Form, fill out the Basic Info (Title, Description).',
                    'Locate the Task Date field. Click the calendar icon and select the past date.',
                    'Save the task. If within 3 days, it will be automatically approved.',
                ],
            },
            {
                type: 'heading',
                id: 'troubleshooting',
                level: 2,
                text: 'Troubleshooting',
            },
            {
                type: 'paragraph',
                html: 'If you cannot select a past date, check with your department admin if your permissions have been restricted or if you have pending unresolved backdated requests.',
            },
        ],
    },

    // ──────────────────────────────────────────────
    // CASHFLOW PROJECTION
    // ──────────────────────────────────────────────
];

# Design Document - Activity Tracking Module

## Overview

Employee Task Tracking Module adalah modul BARU yang dibangun 100% dengan React/Inertia.js. Module ini menggunakan shared task model dimana satu task bisa dikerjakan bersama-sama oleh multiple users dengan status dan timestamp yang synchronized.

**CRITICAL:** This is a NEW module, NOT a migration. Everything must be React/Inertia - NO Livewire, NO Laravel Queue, NO cron jobs.

### Technology Stack Utilization

This module leverages ALL available React libraries in package.json:

#### Core Framework
1. **React 19.2.3 + Inertia.js 2.3.8** - Modern SPA experience, NO Livewire
2. **TypeScript** - Type-safe development
3. **Tailwind CSS 3.1.0** - Utility-first styling

#### UI & Animation Libraries
4. **framer-motion 12.26.0** - Page transitions, modal animations, smooth interactions
5. **@headlessui/react 2.2.9** - Accessible UI components (modals, dropdowns, tabs)
6. **lucide-react 0.562.0** - Modern icon library (NOT Heroicons)
7. **sonner 2.0.7** - Toast notifications (client-side only)

#### Data Visualization
8. **recharts 3.6.0** - Analytics charts (NOT Chart.js!)
9. **@fullcalendar/react 6.1.20** - Calendar view with daygrid, timegrid, list, interaction plugins

#### Data Management
10. **@tanstack/react-table 8.21.3** - Powerful data tables with sorting, filtering, pagination
11. **@dnd-kit (core, sortable, utilities)** - Drag & drop for Kanban board
12. **zustand 5.0.10** - Client-side state management

#### Utilities
13. **date-fns 4.1.0** - Date manipulation and formatting
14. **clsx + tailwind-merge** - Conditional className utilities
15. **class-variance-authority** - Component variant management
16. **cmdk 1.1.1** - Command palette for quick actions

#### Backend Integration
17. **Spatie Activity Log** - Audit trails (backend only)
18. **Spatie Permission** - Role-based access control (backend only)
19. **Laravel API Controllers** - JSON endpoints ONLY (NO Blade views, NO Livewire)

### ❌ NOT USED (Explicitly Excluded)
- ❌ Livewire components
- ❌ Laravel Queue Jobs
- ❌ Scheduled tasks (cron jobs)
- ❌ Chart.js (use recharts instead)
- ❌ Traditional Blade views
- ❌ Laravel Notifications (use sonner toast instead)
- ❌ Background workers
- ❌ Kernel.php scheduled tasks

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Browser (Client)                         │
│  ┌───────────────────────────────────────────────────────┐  │
│  │           React/Inertia Activity Pages                │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  │  │
│  │  │  Dashboard  │  │  Task List  │  │  Calendar   │  │  │
│  │  │  (recharts) │  │  (TanStack) │  │(FullCalendar│  │  │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  │  │
│  │                                                       │  │
│  │  ┌──────────────────────────────────────────────┐   │  │
│  │  │      Zustand Store (Client State)            │   │  │
│  │  │  - Filters  - UI State  - Cache              │   │  │
│  │  └──────────────────────────────────────────────┘   │  │
│  │                                                       │  │
│  │  ┌──────────────────────────────────────────────┐   │  │
│  │  │      React Polling (useEffect + setInterval) │   │  │
│  │  │  - Overdue checks  - Permission expiry       │   │  │
│  │  └──────────────────────────────────────────────┘   │  │
│  └───────────────────────────────────────────────────────┘  │
│                           ↕ Inertia.js                       │
└─────────────────────────────────────────────────────────────┘
                            ↕ HTTP/JSON
┌─────────────────────────────────────────────────────────────┐
│                    Laravel Backend                           │
│  ┌───────────────────────────────────────────────────────┐  │
│  │              API Controllers (JSON only)              │  │
│  │  - ActivityController                                 │  │
│  │  - TaskController                                     │  │
│  │  - BackdateController                                 │  │
│  │  - AnalyticsController                                │  │
│  └───────────────────────────────────────────────────────┘  │
│                           ↕                                  │
│  ┌───────────────────────────────────────────────────────┐  │
│  │              Services & Models                        │  │
│  │  - TaskService, BackdatePermissionService             │  │
│  │  - EmployeeTask, TaskParticipant, ActivityType        │  │
│  └───────────────────────────────────────────────────────┘  │
│                           ↕                                  │
│  ┌───────────────────────────────────────────────────────┐  │
│  │                   Database                            │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### Module Structure

```
app/
├── Models/Modules/Activity/
│   ├── EmployeeTask.php
│   ├── TaskParticipant.php
│   ├── TaskAttachment.php
│   ├── ActivityType.php
│   ├── SubActivity.php
│   └── BackdatePermission.php
├── Http/Controllers/Modules/Activity/
│   ├── ActivityController.php (Inertia pages)
│   ├── TaskController.php (JSON API)
│   ├── BackdateController.php (JSON API)
│   └── AnalyticsController.php (JSON API)
├── Services/Modules/Activity/
│   ├── TaskService.php
│   ├── BackdatePermissionService.php
│   └── TaskAnalyticsService.php
└── Http/Controllers/Admin/
    ├── ActivityTypeController.php (Inertia pages)
    └── SubActivityController.php (Inertia pages)

resources/js/inertia/
├── Pages/Activity/
│   ├── Dashboard.tsx (Personal analytics with recharts)
│   ├── TaskList.tsx (TanStack Table)
│   ├── TaskForm.tsx (Create/Edit with framer-motion)
│   ├── TaskDetail.tsx (View with actions)
│   ├── CalendarView.tsx (FullCalendar)
│   ├── KanbanView.tsx (@dnd-kit)
│   ├── DepartmentTasks.tsx (Joinable tasks)
│   ├── BackdateRequests.tsx (Employee view)
│   ├── BackdateApprovals.tsx (Department head view)
│   └── Analytics/
│       ├── DepartmentAnalytics.tsx (recharts)
│       └── BusinessUnitAnalytics.tsx (recharts)
├── Pages/Admin/Activity/
│   ├── ActivityTypes/Index.tsx
│   ├── ActivityTypes/Form.tsx
│   ├── SubActivities/Index.tsx
│   └── SubActivities/Form.tsx
├── components/activity/
│   ├── TaskCard.tsx
│   ├── TaskStatusBadge.tsx
│   ├── TaskFilters.tsx
│   ├── TaskActions.tsx
│   ├── ParticipantList.tsx
│   ├── AttachmentUpload.tsx
│   ├── DurationWarning.tsx
│   └── BackdatePermissionBanner.tsx
├── stores/
│   └── activityStore.ts (Zustand)
└── hooks/
    ├── useTaskPolling.ts
    ├── useBackdatePermission.ts
    └── useTaskAnalytics.ts

database/migrations/modules/activity/
├── 2025_12_29_100000_create_activity_types_table.php
├── 2025_12_29_100001_create_sub_activities_table.php
├── 2025_12_29_100002_create_employee_tasks_table.php
├── 2025_12_29_100003_create_task_participants_table.php
├── 2025_12_29_100004_create_task_attachments_table.php
├── 2026_01_22_100000_create_backdate_permissions_table.php
└── 2026_01_22_110000_add_task_date_to_employee_tasks_table.php
```



## Database Design

### ERD

```
┌──────────────────┐       ┌──────────────────┐       ┌──────────────────┐
│  business_units  │       │      users       │       │   departments    │
└────────┬─────────┘       └────────┬─────────┘       └────────┬─────────┘
         │                          │                          │
         ▼                          ▼                          ▼
┌────────────────────────────────────────────────────────────────────────────┐
│                         employee_tasks                                      │
├────────────────────────────────────────────────────────────────────────────┤
│ id (PK)                                                                     │
│ business_unit_id (FK)                                                       │
│ department_id (FK)                                                          │
│ created_by (FK → users)                                                     │
│ activity_type_id (FK)                                                       │
│ sub_activity_id (FK, nullable)                                              │
│ task_title (varchar 255)                                                    │
│ task_date (date) - for backdate support                                    │
│ due_date (date)                                                             │
│ started_at (timestamp, nullable)                                            │
│ completed_at (timestamp, nullable)                                          │
│ completed_by (FK → users, nullable)                                         │
│ status (enum: planned, in_progress, completed, cancelled)                  │
│ duration_minutes (int, nullable)                                            │
│ notes (text, nullable)                                                      │
│ cancellation_reason (varchar, nullable)                                     │
│ created_at, updated_at                                                      │
└────────────────────────────────────────────────────────────────────────────┘
         │
         │ N:M
         ▼
┌────────────────────────────────────────────────────────────────────────────┐
│                    task_participants                                        │
├────────────────────────────────────────────────────────────────────────────┤
│ id (PK)                                                                     │
│ employee_task_id (FK)                                                       │
│ user_id (FK)                                                                │
│ is_owner (boolean, default false)                                          │
│ joined_at (timestamp)                                                       │
│ created_at, updated_at                                                      │
│ UNIQUE(employee_task_id, user_id)                                          │
└────────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────────┐
│                      task_attachments                                       │
├────────────────────────────────────────────────────────────────────────────┤
│ id (PK)                                                                     │
│ employee_task_id (FK)                                                       │
│ file_name (varchar)                                                         │
│ file_path (varchar)                                                         │
│ file_type (varchar)                                                         │
│ file_size (int)                                                             │
│ uploaded_by (FK → users)                                                    │
│ created_at                                                                  │
└────────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────────┐
│                      activity_types                                         │
├────────────────────────────────────────────────────────────────────────────┤
│ id (PK)                                                                     │
│ code (varchar, unique)                                                      │
│ name (varchar)                                                              │
│ color (varchar)                                                             │
│ is_active (boolean, default true)                                          │
│ sort_order (int)                                                            │
│ created_at, updated_at                                                      │
└────────────────────────────────────────────────────────────────────────────┘
         │
         │ 1:N
         ▼
┌────────────────────────────────────────────────────────────────────────────┐
│                       sub_activities                                        │
├────────────────────────────────────────────────────────────────────────────┤
│ id (PK)                                                                     │
│ activity_type_id (FK)                                                       │
│ code (varchar)                                                              │
│ name (varchar)                                                              │
│ is_active (boolean, default true)                                          │
│ sort_order (int)                                                            │
│ created_at, updated_at                                                      │
│ UNIQUE(activity_type_id, code)                                             │
└────────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────────┐
│                    backdate_permissions                                     │
├────────────────────────────────────────────────────────────────────────────┤
│ id (PK)                                                                     │
│ user_id (FK)                                                                │
│ business_unit_id (FK)                                                       │
│ department_id (FK)                                                          │
│ requested_date (date)                                                       │
│ reason (text)                                                               │
│ status (enum: pending, approved, rejected, expired)                        │
│ approved_by (FK → users, nullable)                                          │
│ approved_at (timestamp, nullable)                                           │
│ rejected_by (FK → users, nullable)                                          │
│ rejected_at (timestamp, nullable)                                           │
│ rejection_reason (text, nullable)                                           │
│ granted_until (timestamp, nullable)                                         │
│ created_at, updated_at                                                      │
└────────────────────────────────────────────────────────────────────────────┘
```

### Default Activity Types & Sub-Activities

| Activity Type | Code | Color | Sub-Activities |
|---------------|------|-------|----------------|
| Meeting | MEETING | #3B82F6 (blue) | Meeting Client, Meeting RAB, Meeting PNL, Meeting Internal, Meeting Vendor |
| Web Development | WEBDEV | #6366F1 (indigo) | Fix Bug, Update UI, New Feature, Code Review, Deployment |
| Event | EVENT | #A855F7 (purple) | Event Planning, Event Execution, Event Follow-up |
| Internal Meeting | INTERNAL | #6B7280 (gray) | Daily Standup, Weekly Review, Monthly Report |
| Administrative | ADMIN | #F59E0B (amber) | Documentation, Email, Report Writing |
| Training | TRAINING | #10B981 (emerald) | Internal Training, External Training, Self Learning |



## React Component Design

### 1. Dashboard Page (recharts)

**File:** `resources/js/inertia/Pages/Activity/Dashboard.tsx`

**Props:**
```typescript
interface DashboardProps {
  stats: {
    total: number;
    completed: number;
    in_progress: number;
    overdue: number;
    total_hours: number;
  };
  activityBreakdown: Array<{
    activity_type: string;
    count: number;
    hours: number;
    color: string;
  }>;
  weeklyTrend: Array<{
    date: string;
    completed: number;
    created: number;
  }>;
  recentTasks: Task[];
  backdatePermission?: BackdatePermission;
}
```

**Features:**
- **framer-motion** page transitions
- **recharts** for PieChart (activity breakdown) and LineChart (weekly trend)
- **sonner** toast for notifications
- **lucide-react** icons
- Real-time stats with polling (useEffect + setInterval)

**Layout:**
```tsx
<motion.div
  initial={{ opacity: 0, y: 20 }}
  animate={{ opacity: 1, y: 0 }}
  transition={{ duration: 0.3 }}
>
  {/* Stats Grid */}
  <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
    <StatCard icon={CheckCircle} title="Completed" value={stats.completed} />
    <StatCard icon={Clock} title="In Progress" value={stats.in_progress} />
    <StatCard icon={AlertCircle} title="Overdue" value={stats.overdue} />
    <StatCard icon={Timer} title="Total Hours" value={stats.total_hours} />
  </div>

  {/* Charts */}
  <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    {/* Activity Breakdown - PieChart */}
    <Card>
      <CardHeader>Activity Breakdown</CardHeader>
      <CardContent>
        <ResponsiveContainer width="100%" height={300}>
          <PieChart>
            <Pie data={activityBreakdown} dataKey="count" nameKey="activity_type" />
            <Tooltip />
            <Legend />
          </PieChart>
        </ResponsiveContainer>
      </CardContent>
    </Card>

    {/* Weekly Trend - LineChart */}
    <Card>
      <CardHeader>Weekly Trend</CardHeader>
      <CardContent>
        <ResponsiveContainer width="100%" height={300}>
          <LineChart data={weeklyTrend}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="date" />
            <YAxis />
            <Tooltip />
            <Legend />
            <Line type="monotone" dataKey="completed" stroke="#10B981" />
            <Line type="monotone" dataKey="created" stroke="#3B82F6" />
          </LineChart>
        </ResponsiveContainer>
      </CardContent>
    </Card>
  </div>

  {/* Backdate Permission Banner */}
  {backdatePermission && (
    <BackdatePermissionBanner permission={backdatePermission} />
  )}

  {/* Recent Tasks */}
  <Card className="mt-6">
    <CardHeader>Recent Tasks</CardHeader>
    <CardContent>
      {recentTasks.map(task => (
        <TaskCard key={task.id} task={task} />
      ))}
    </CardContent>
  </Card>
</motion.div>
```

### 2. Task List Page (@tanstack/react-table)

**File:** `resources/js/inertia/Pages/Activity/TaskList.tsx`

**Features:**
- **@tanstack/react-table** for data table with sorting, filtering, pagination
- **zustand** for filter state persistence
- **lucide-react** icons
- **sonner** toast notifications
- **framer-motion** for row animations

**Columns:**
```typescript
const columns: ColumnDef<Task>[] = [
  {
    accessorKey: 'task_title',
    header: 'Task',
    cell: ({ row }) => (
      <div>
        <p className="font-medium">{row.original.task_title}</p>
        <p className="text-sm text-gray-500">{row.original.activity_type.name}</p>
      </div>
    ),
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => <TaskStatusBadge status={row.original.status} />,
  },
  {
    accessorKey: 'due_date',
    header: 'Due Date',
    cell: ({ row }) => format(new Date(row.original.due_date), 'dd MMM yyyy'),
  },
  {
    accessorKey: 'participants',
    header: 'Participants',
    cell: ({ row }) => <ParticipantList participants={row.original.participants} />,
  },
  {
    id: 'actions',
    cell: ({ row }) => <TaskActions task={row.original} />,
  },
];
```

### 3. Calendar View (@fullcalendar/react)

**File:** `resources/js/inertia/Pages/Activity/CalendarView.tsx`

**Features:**
- **@fullcalendar/react** with daygrid, timegrid, list, interaction plugins
- **@headlessui/react** Dialog for task details
- **framer-motion** for modal animations
- **date-fns** for date formatting

**Implementation:**
```tsx
<FullCalendar
  plugins={[dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin]}
  initialView="dayGridMonth"
  headerToolbar={{
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,listWeek'
  }}
  events={tasks.map(task => ({
    id: task.id,
    title: task.task_title,
    start: task.task_date,
    backgroundColor: task.activity_type.color,
    extendedProps: { task }
  }))}
  eventClick={(info) => {
    setSelectedTask(info.event.extendedProps.task);
    setIsModalOpen(true);
  }}
  dateClick={(info) => {
    router.visit(route('activity.create', { date: info.dateStr }));
  }}
/>
```

### 4. Kanban View (@dnd-kit)

**File:** `resources/js/inertia/Pages/Activity/KanbanView.tsx`

**Features:**
- **@dnd-kit/core** for drag and drop
- **@dnd-kit/sortable** for sortable lists
- **framer-motion** for drag animations
- **sonner** toast for status updates

**Columns:**
- Planned
- In Progress
- Completed
- Cancelled

**Implementation:**
```tsx
<DndContext
  sensors={sensors}
  collisionDetection={closestCenter}
  onDragEnd={handleDragEnd}
>
  <div className="grid grid-cols-4 gap-4">
    {['planned', 'in_progress', 'completed', 'cancelled'].map(status => (
      <KanbanColumn key={status} status={status} tasks={tasksByStatus[status]} />
    ))}
  </div>
</DndContext>
```

### 5. Task Form (framer-motion)

**File:** `resources/js/inertia/Pages/Activity/TaskForm.tsx`

**Features:**
- **@headlessui/react** Combobox for activity type/sub-activity selection
- **framer-motion** for form animations
- **date-fns** for date validation
- **sonner** toast for success/error messages
- **lucide-react** icons

**Form Fields:**
- Task Title (required)
- Activity Type (required, Combobox)
- Sub-Activity (optional, Combobox - filtered by activity type)
- Task Date (date picker with backdate validation)
- Due Date (required)
- Notes (optional, textarea)
- Attachments (file upload, max 5 files, 5MB each)

**Backdate Validation:**
```typescript
const useBackdatePermission = () => {
  const [permission, setPermission] = useState<BackdatePermission | null>(null);
  
  useEffect(() => {
    // Poll for permission status every 30 seconds
    const interval = setInterval(async () => {
      const response = await axios.get('/api/activity/backdate-permission');
      setPermission(response.data);
    }, 30000);
    
    return () => clearInterval(interval);
  }, []);
  
  const getMinDate = () => {
    if (permission && permission.status === 'approved') {
      return new Date(permission.requested_date);
    }
    // Default: 1 day backdate (yesterday)
    return subDays(new Date(), 1);
  };
  
  return { permission, minDate: getMinDate() };
};
```



### 6. Backdate Request Page

**File:** `resources/js/inertia/Pages/Activity/BackdateRequests.tsx`

**Features:**
- **@tanstack/react-table** for request list
- **@headlessui/react** Dialog for request form
- **framer-motion** for modal animations
- **sonner** toast for notifications
- **date-fns** for date formatting

**Props:**
```typescript
interface BackdateRequestsProps {
  requests: BackdatePermission[];
  activePermission?: BackdatePermission;
}
```

**Features:**
- View all backdate requests (pending, approved, rejected, expired)
- Submit new backdate request (modal form)
- Real-time countdown for active permission
- Status badges with colors

**Request Form:**
```tsx
<Dialog open={isOpen} onClose={() => setIsOpen(false)}>
  <motion.div
    initial={{ opacity: 0, scale: 0.95 }}
    animate={{ opacity: 1, scale: 1 }}
  >
    <DialogTitle>Request Backdate Access</DialogTitle>
    <form onSubmit={handleSubmit}>
      <div>
        <label>Requested Date</label>
        <input
          type="date"
          max={format(subDays(new Date(), 2), 'yyyy-MM-dd')}
          required
        />
      </div>
      <div>
        <label>Reason</label>
        <textarea required />
      </div>
      <button type="submit">Submit Request</button>
    </form>
  </motion.div>
</Dialog>
```

### 7. Backdate Approval Page (Department Head)

**File:** `resources/js/inertia/Pages/Activity/BackdateApprovals.tsx`

**Features:**
- **@tanstack/react-table** for approval list
- **@headlessui/react** Dialog for rejection reason
- **framer-motion** for animations
- **sonner** toast for notifications

**Props:**
```typescript
interface BackdateApprovalsProps {
  pendingRequests: BackdatePermission[];
  history: BackdatePermission[];
}
```

**Actions:**
- Approve (instant, grants until end of day)
- Reject (requires reason modal)
- View history with filters

**Approval Handler:**
```typescript
const handleApprove = async (requestId: number) => {
  try {
    await router.post(`/activity/backdate/${requestId}/approve`);
    toast.success('Backdate request approved');
  } catch (error) {
    toast.error('Failed to approve request');
  }
};

const handleReject = async (requestId: number, reason: string) => {
  try {
    await router.post(`/activity/backdate/${requestId}/reject`, { reason });
    toast.success('Backdate request rejected');
  } catch (error) {
    toast.error('Failed to reject request');
  }
};
```

### 8. Analytics Pages (recharts)

**File:** `resources/js/inertia/Pages/Activity/Analytics/DepartmentAnalytics.tsx`

**Features:**
- **recharts** for BarChart, LineChart, PieChart
- **@headlessui/react** Tabs for different views
- **date-fns** for date range selection
- **lucide-react** icons

**Charts:**
1. **Team Workload** - BarChart showing tasks per team member
2. **Completion Rate** - LineChart showing on-time vs overdue over time
3. **Activity Distribution** - PieChart showing activity type breakdown
4. **Average Duration** - BarChart showing avg duration per activity type

**Implementation:**
```tsx
<Tabs>
  <TabList>
    <Tab>Workload</Tab>
    <Tab>Completion Rate</Tab>
    <Tab>Activity Distribution</Tab>
    <Tab>Duration</Tab>
  </TabList>
  
  <TabPanel>
    <ResponsiveContainer width="100%" height={400}>
      <BarChart data={workloadData}>
        <CartesianGrid strokeDasharray="3 3" />
        <XAxis dataKey="user_name" />
        <YAxis />
        <Tooltip />
        <Legend />
        <Bar dataKey="total_tasks" fill="#3B82F6" />
        <Bar dataKey="completed_tasks" fill="#10B981" />
      </BarChart>
    </ResponsiveContainer>
  </TabPanel>
  
  {/* Other panels... */}
</Tabs>
```

## Zustand Store Design

**File:** `resources/js/inertia/stores/activityStore.ts`

```typescript
interface ActivityStore {
  // Filters
  filters: {
    search: string;
    activity_type_id: number | null;
    status: string | null;
    date_from: string | null;
    date_to: string | null;
  };
  setFilters: (filters: Partial<ActivityStore['filters']>) => void;
  resetFilters: () => void;
  
  // UI State
  viewMode: 'list' | 'calendar' | 'kanban';
  setViewMode: (mode: 'list' | 'calendar' | 'kanban') => void;
  
  // Cache
  activityTypes: ActivityType[];
  subActivities: Record<number, SubActivity[]>;
  cacheActivityTypes: (types: ActivityType[]) => void;
  cacheSubActivities: (activityTypeId: number, subActivities: SubActivity[]) => void;
  
  // Backdate Permission
  backdatePermission: BackdatePermission | null;
  setBackdatePermission: (permission: BackdatePermission | null) => void;
}

export const useActivityStore = create<ActivityStore>((set) => ({
  filters: {
    search: '',
    activity_type_id: null,
    status: null,
    date_from: null,
    date_to: null,
  },
  setFilters: (filters) => set((state) => ({
    filters: { ...state.filters, ...filters }
  })),
  resetFilters: () => set({
    filters: {
      search: '',
      activity_type_id: null,
      status: null,
      date_from: null,
      date_to: null,
    }
  }),
  
  viewMode: 'list',
  setViewMode: (mode) => set({ viewMode: mode }),
  
  activityTypes: [],
  subActivities: {},
  cacheActivityTypes: (types) => set({ activityTypes: types }),
  cacheSubActivities: (activityTypeId, subActivities) => set((state) => ({
    subActivities: { ...state.subActivities, [activityTypeId]: subActivities }
  })),
  
  backdatePermission: null,
  setBackdatePermission: (permission) => set({ backdatePermission: permission }),
}));
```

## Custom Hooks

### 1. useTaskPolling

**File:** `resources/js/inertia/hooks/useTaskPolling.ts`

```typescript
export const useTaskPolling = (interval: number = 60000) => {
  const [overdueCount, setOverdueCount] = useState(0);
  
  useEffect(() => {
    const checkOverdue = async () => {
      const response = await axios.get('/api/activity/overdue-count');
      setOverdueCount(response.data.count);
      
      if (response.data.count > 0) {
        toast.warning(`You have ${response.data.count} overdue tasks`);
      }
    };
    
    // Initial check
    checkOverdue();
    
    // Poll every interval
    const timer = setInterval(checkOverdue, interval);
    
    return () => clearInterval(timer);
  }, [interval]);
  
  return { overdueCount };
};
```

### 2. useBackdatePermission

**File:** `resources/js/inertia/hooks/useBackdatePermission.ts`

```typescript
export const useBackdatePermission = () => {
  const { backdatePermission, setBackdatePermission } = useActivityStore();
  
  useEffect(() => {
    const checkPermission = async () => {
      const response = await axios.get('/api/activity/backdate-permission');
      setBackdatePermission(response.data);
      
      // Check if permission is about to expire (< 1 hour)
      if (response.data && response.data.status === 'approved') {
        const expiresAt = new Date(response.data.granted_until);
        const now = new Date();
        const hoursLeft = differenceInHours(expiresAt, now);
        
        if (hoursLeft < 1 && hoursLeft > 0) {
          toast.warning(`Backdate permission expires in ${hoursLeft} hour(s)`);
        }
      }
    };
    
    // Initial check
    checkPermission();
    
    // Poll every 5 minutes
    const timer = setInterval(checkPermission, 300000);
    
    return () => clearInterval(timer);
  }, []);
  
  const getMinDate = () => {
    if (backdatePermission && backdatePermission.status === 'approved') {
      return new Date(backdatePermission.requested_date);
    }
    // Default: 1 day backdate (yesterday)
    return subDays(new Date(), 1);
  };
  
  const canBackdate = (date: Date) => {
    const minDate = getMinDate();
    return isAfter(date, minDate) || isSameDay(date, minDate);
  };
  
  return { backdatePermission, minDate: getMinDate(), canBackdate };
};
```

### 3. useTaskAnalytics

**File:** `resources/js/inertia/hooks/useTaskAnalytics.ts`

```typescript
export const useTaskAnalytics = (dateRange: { from: Date; to: Date }) => {
  const [analytics, setAnalytics] = useState<Analytics | null>(null);
  const [loading, setLoading] = useState(true);
  
  useEffect(() => {
    const fetchAnalytics = async () => {
      setLoading(true);
      try {
        const response = await axios.get('/api/activity/analytics', {
          params: {
            date_from: format(dateRange.from, 'yyyy-MM-dd'),
            date_to: format(dateRange.to, 'yyyy-MM-dd'),
          }
        });
        setAnalytics(response.data);
      } catch (error) {
        toast.error('Failed to load analytics');
      } finally {
        setLoading(false);
      }
    };
    
    fetchAnalytics();
  }, [dateRange]);
  
  return { analytics, loading };
};
```



## Backend Design

### 1. Laravel Models

#### EmployeeTask Model

```php
namespace App\Models\Modules\Activity;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EmployeeTask extends Model
{
    use LogsActivity;
    
    protected $fillable = [
        'business_unit_id', 'department_id', 'created_by',
        'activity_type_id', 'sub_activity_id', 'task_title',
        'task_date', 'due_date', 'started_at', 'completed_at',
        'completed_by', 'status', 'duration_minutes', 'notes',
        'cancellation_reason'
    ];
    
    protected $casts = [
        'task_date' => 'date',
        'due_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['task_title', 'status', 'due_date', 'task_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    
    // Relationships
    public function businessUnit() { return $this->belongsTo(BusinessUnit::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function completedByUser() { return $this->belongsTo(User::class, 'completed_by'); }
    public function activityType() { return $this->belongsTo(ActivityType::class); }
    public function subActivity() { return $this->belongsTo(SubActivity::class); }
    
    public function participants()
    {
        return $this->belongsToMany(User::class, 'task_participants')
            ->withPivot(['is_owner', 'joined_at'])
            ->withTimestamps();
    }
    
    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class, 'employee_task_id');
    }
    
    // Scopes
    public function scopeForBusinessUnit($query, int $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }
    
    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }
    
    public function scopeForParticipant($query, int $userId)
    {
        return $query->whereHas('participants', fn($q) => $q->where('user_id', $userId));
    }
    
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }
    
    // Helper Methods
    public function isOverdue(): bool
    {
        return !in_array($this->status, ['completed', 'cancelled']) 
            && $this->due_date->isPast();
    }
    
    public function isOwner(int $userId): bool
    {
        return $this->participants()
            ->where('user_id', $userId)
            ->where('is_owner', true)
            ->exists();
    }
    
    public function getDurationWarning(): ?string
    {
        if (!$this->duration_minutes) return null;
        
        $hours = $this->duration_minutes / 60;
        
        if ($hours > 72) {
            return 'critical'; // > 3 days
        } elseif ($hours > 24) {
            return 'warning'; // > 1 day
        }
        
        return null;
    }
}
```

### 2. Laravel Controllers

#### ActivityController (Inertia Pages)

```php
namespace App\Http\Controllers\Modules\Activity;

use App\Http\Controllers\Controller;
use App\Models\Modules\Activity\EmployeeTask;
use App\Models\Modules\Activity\ActivityType;
use App\Services\Modules\Activity\TaskAnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityController extends Controller
{
    public function __construct(
        protected TaskAnalyticsService $analyticsService
    ) {}
    
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $businessUnitId = session('current_business_unit_id');
        
        $stats = $this->analyticsService->getPersonalStats($user->id, $businessUnitId);
        $activityBreakdown = $this->analyticsService->getActivityBreakdown($user->id, $businessUnitId);
        $weeklyTrend = $this->analyticsService->getWeeklyTrend($user->id, $businessUnitId);
        
        $recentTasks = EmployeeTask::forBusinessUnit($businessUnitId)
            ->forParticipant($user->id)
            ->with(['activityType', 'subActivity', 'participants'])
            ->latest()
            ->take(10)
            ->get();
        
        $backdatePermission = $user->backdatePermissions()
            ->where('status', 'approved')
            ->where('granted_until', '>=', now())
            ->first();
        
        return Inertia::render('Activity/Dashboard', [
            'stats' => $stats,
            'activityBreakdown' => $activityBreakdown,
            'weeklyTrend' => $weeklyTrend,
            'recentTasks' => $recentTasks,
            'backdatePermission' => $backdatePermission,
        ]);
    }
    
    public function index(Request $request)
    {
        $user = $request->user();
        $businessUnitId = session('current_business_unit_id');
        
        $tasks = EmployeeTask::forBusinessUnit($businessUnitId)
            ->where(function ($query) use ($user) {
                $query->forDepartment($user->primary_department_id)
                    ->orWhere->forParticipant($user->id);
            })
            ->when($request->search, fn($q, $search) => 
                $q->where('task_title', 'like', "%{$search}%")
            )
            ->when($request->activity_type_id, fn($q, $typeId) => 
                $q->where('activity_type_id', $typeId)
            )
            ->when($request->status, fn($q, $status) => 
                $q->where('status', $status)
            )
            ->with(['activityType', 'subActivity', 'participants', 'creator'])
            ->latest()
            ->paginate(20);
        
        $activityTypes = ActivityType::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        
        return Inertia::render('Activity/TaskList', [
            'tasks' => $tasks,
            'activityTypes' => $activityTypes,
            'filters' => $request->only(['search', 'activity_type_id', 'status']),
        ]);
    }
    
    public function calendar(Request $request)
    {
        $user = $request->user();
        $businessUnitId = session('current_business_unit_id');
        
        $tasks = EmployeeTask::forBusinessUnit($businessUnitId)
            ->forParticipant($user->id)
            ->with(['activityType', 'subActivity', 'participants'])
            ->get();
        
        return Inertia::render('Activity/CalendarView', [
            'tasks' => $tasks,
        ]);
    }
    
    public function kanban(Request $request)
    {
        $user = $request->user();
        $businessUnitId = session('current_business_unit_id');
        
        $tasks = EmployeeTask::forBusinessUnit($businessUnitId)
            ->forParticipant($user->id)
            ->with(['activityType', 'subActivity', 'participants'])
            ->get()
            ->groupBy('status');
        
        return Inertia::render('Activity/KanbanView', [
            'tasksByStatus' => $tasks,
        ]);
    }
}
```

#### TaskController (JSON API)

```php
namespace App\Http\Controllers\Modules\Activity;

use App\Http\Controllers\Controller;
use App\Models\Modules\Activity\EmployeeTask;
use App\Services\Modules\Activity\TaskService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        protected TaskService $taskService
    ) {}
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'task_title' => 'required|string|max:255',
            'activity_type_id' => 'required|exists:activity_types,id',
            'sub_activity_id' => 'nullable|exists:sub_activities,id',
            'task_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:task_date',
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);
        
        // Validate backdate
        $this->validateBackdate($request->user(), $validated['task_date']);
        
        $task = $this->taskService->create($validated, $request->user());
        
        return response()->json([
            'message' => 'Task created successfully',
            'task' => $task->load(['activityType', 'subActivity', 'participants']),
        ], 201);
    }
    
    public function update(Request $request, EmployeeTask $task)
    {
        $this->authorize('update', $task);
        
        $validated = $request->validate([
            'task_title' => 'required|string|max:255',
            'activity_type_id' => 'required|exists:activity_types,id',
            'sub_activity_id' => 'nullable|exists:sub_activities,id',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);
        
        $task->update($validated);
        
        return response()->json([
            'message' => 'Task updated successfully',
            'task' => $task->load(['activityType', 'subActivity', 'participants']),
        ]);
    }
    
    public function start(EmployeeTask $task)
    {
        $this->authorize('update', $task);
        
        $this->taskService->start($task);
        
        return response()->json([
            'message' => 'Task started',
            'task' => $task->fresh(),
        ]);
    }
    
    public function complete(EmployeeTask $task, Request $request)
    {
        $this->authorize('update', $task);
        
        $this->taskService->complete($task, $request->user());
        
        return response()->json([
            'message' => 'Task completed',
            'task' => $task->fresh(),
        ]);
    }
    
    public function join(EmployeeTask $task, Request $request)
    {
        $this->taskService->join($task, $request->user());
        
        return response()->json([
            'message' => 'Joined task successfully',
            'task' => $task->fresh(['participants']),
        ]);
    }
    
    public function overdueCount(Request $request)
    {
        $user = $request->user();
        $businessUnitId = session('current_business_unit_id');
        
        $count = EmployeeTask::forBusinessUnit($businessUnitId)
            ->forParticipant($user->id)
            ->overdue()
            ->count();
        
        return response()->json(['count' => $count]);
    }
    
    protected function validateBackdate(User $user, string $taskDate)
    {
        $taskDate = Carbon::parse($taskDate);
        $yesterday = now()->subDay()->startOfDay();
        
        // Check if task_date is within default limit (yesterday to today)
        if ($taskDate->gte($yesterday)) {
            return; // OK
        }
        
        // Check for active backdate permission
        $permission = $user->backdatePermissions()
            ->where('status', 'approved')
            ->where('granted_until', '>=', now())
            ->first();
        
        if ($permission && $taskDate->gte($permission->requested_date)) {
            return; // OK
        }
        
        throw ValidationException::withMessages([
            'task_date' => 'Task date is outside allowed backdate range. Please request backdate permission.',
        ]);
    }
}
```



#### BackdateController (JSON API)

```php
namespace App\Http\Controllers\Modules\Activity;

use App\Http\Controllers\Controller;
use App\Models\Modules\Activity\BackdatePermission;
use App\Services\Modules\Activity\BackdatePermissionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BackdateController extends Controller
{
    public function __construct(
        protected BackdatePermissionService $service
    ) {}
    
    public function index(Request $request)
    {
        $user = $request->user();
        
        $requests = BackdatePermission::where('user_id', $user->id)
            ->with(['approvedBy', 'rejectedBy'])
            ->latest()
            ->get();
        
        $activePermission = $requests->firstWhere(function ($permission) {
            return $permission->status === 'approved' 
                && $permission->granted_until >= now();
        });
        
        return Inertia::render('Activity/BackdateRequests', [
            'requests' => $requests,
            'activePermission' => $activePermission,
        ]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'requested_date' => 'required|date|before:yesterday',
            'reason' => 'required|string|max:500',
        ]);
        
        // Check for pending request
        $hasPending = BackdatePermission::where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->exists();
        
        if ($hasPending) {
            return response()->json([
                'message' => 'You already have a pending backdate request',
            ], 422);
        }
        
        $permission = $this->service->createRequest(
            $request->user(),
            $validated['requested_date'],
            $validated['reason']
        );
        
        return response()->json([
            'message' => 'Backdate request submitted successfully',
            'permission' => $permission,
        ], 201);
    }
    
    public function approvals(Request $request)
    {
        $user = $request->user();
        
        // Only department heads can access
        if (!$user->isDepartmentHead()) {
            abort(403);
        }
        
        $pendingRequests = BackdatePermission::where('department_id', $user->primary_department_id)
            ->where('business_unit_id', session('current_business_unit_id'))
            ->where('status', 'pending')
            ->with(['user'])
            ->latest()
            ->get();
        
        $history = BackdatePermission::where('department_id', $user->primary_department_id)
            ->where('business_unit_id', session('current_business_unit_id'))
            ->whereIn('status', ['approved', 'rejected', 'expired'])
            ->with(['user', 'approvedBy', 'rejectedBy'])
            ->latest()
            ->paginate(20);
        
        return Inertia::render('Activity/BackdateApprovals', [
            'pendingRequests' => $pendingRequests,
            'history' => $history,
        ]);
    }
    
    public function approve(BackdatePermission $permission, Request $request)
    {
        $this->authorize('approve', $permission);
        
        $this->service->approve($permission, $request->user());
        
        return response()->json([
            'message' => 'Backdate request approved',
            'permission' => $permission->fresh(),
        ]);
    }
    
    public function reject(BackdatePermission $permission, Request $request)
    {
        $this->authorize('approve', $permission);
        
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);
        
        $this->service->reject($permission, $request->user(), $validated['reason']);
        
        return response()->json([
            'message' => 'Backdate request rejected',
            'permission' => $permission->fresh(),
        ]);
    }
    
    public function current(Request $request)
    {
        $user = $request->user();
        
        $permission = BackdatePermission::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('granted_until', '>=', now())
            ->first();
        
        return response()->json($permission);
    }
}
```

### 3. Services

#### TaskService

```php
namespace App\Services\Modules\Activity;

use App\Models\Core\User;
use App\Models\Modules\Activity\EmployeeTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TaskService
{
    public function create(array $data, User $user): EmployeeTask
    {
        return DB::transaction(function () use ($data, $user) {
            $task = EmployeeTask::create([
                'business_unit_id' => session('current_business_unit_id'),
                'department_id' => $user->primary_department_id,
                'created_by' => $user->id,
                'status' => 'planned',
                'task_title' => $data['task_title'],
                'activity_type_id' => $data['activity_type_id'],
                'sub_activity_id' => $data['sub_activity_id'] ?? null,
                'task_date' => $data['task_date'],
                'due_date' => $data['due_date'],
                'notes' => $data['notes'] ?? null,
            ]);
            
            // Auto-add creator as owner
            $task->participants()->attach($user->id, [
                'is_owner' => true,
                'joined_at' => $task->created_at
            ]);
            
            // Handle attachments
            if (isset($data['attachments'])) {
                foreach ($data['attachments'] as $file) {
                    $path = $file->store('task-attachments', 'public');
                    $task->attachments()->create([
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => $user->id,
                    ]);
                }
            }
            
            return $task;
        });
    }
    
    public function join(EmployeeTask $task, User $user): void
    {
        if ($task->isParticipant($user->id)) {
            throw new \Exception('User is already a participant');
        }
        
        // Verify same department
        if ($task->department_id !== $user->primary_department_id) {
            throw new \Exception('Can only join tasks from your department');
        }
        
        $task->participants()->attach($user->id, [
            'is_owner' => false,
            'joined_at' => now()
        ]);
    }
    
    public function start(EmployeeTask $task): void
    {
        if ($task->status !== 'planned') {
            throw new \Exception('Task can only be started from planned status');
        }
        
        $task->update([
            'status' => 'in_progress',
            'started_at' => now()
        ]);
    }
    
    public function complete(EmployeeTask $task, User $user): void
    {
        if (!in_array($task->status, ['planned', 'in_progress'])) {
            throw new \Exception('Task cannot be completed from current status');
        }
        
        $completedAt = now();
        $startedAt = $task->started_at ?? $completedAt;
        $duration = $startedAt->diffInMinutes($completedAt);
        
        $task->update([
            'status' => 'completed',
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'completed_by' => $user->id,
            'duration_minutes' => $duration
        ]);
    }
    
    public function cancel(EmployeeTask $task, string $reason, User $user): void
    {
        if (!$task->isOwner($user->id) && !$user->isSuperAdmin()) {
            throw new \Exception('Only task owner can cancel');
        }
        
        $task->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason
        ]);
    }
    
    public function updateDuration(EmployeeTask $task, string $startedAt, string $completedAt, User $user): void
    {
        if (!$task->isOwner($user->id) && !$user->isSuperAdmin()) {
            throw new \Exception('Only task owner can edit duration');
        }
        
        $started = Carbon::parse($startedAt);
        $completed = Carbon::parse($completedAt);
        $duration = $started->diffInMinutes($completed);
        
        $task->update([
            'started_at' => $started,
            'completed_at' => $completed,
            'duration_minutes' => $duration
        ]);
        
        // Log the change
        activity()
            ->performedOn($task)
            ->causedBy($user)
            ->withProperties([
                'old_started_at' => $task->getOriginal('started_at'),
                'new_started_at' => $started,
                'old_completed_at' => $task->getOriginal('completed_at'),
                'new_completed_at' => $completed,
            ])
            ->log('Duration manually adjusted');
    }
}
```

#### BackdatePermissionService

```php
namespace App\Services\Modules\Activity;

use App\Models\Core\User;
use App\Models\Modules\Activity\BackdatePermission;
use Carbon\Carbon;

class BackdatePermissionService
{
    public function createRequest(User $user, string $requestedDate, string $reason): BackdatePermission
    {
        return BackdatePermission::create([
            'user_id' => $user->id,
            'business_unit_id' => session('current_business_unit_id'),
            'department_id' => $user->primary_department_id,
            'requested_date' => $requestedDate,
            'reason' => $reason,
            'status' => 'pending',
        ]);
    }
    
    public function approve(BackdatePermission $permission, User $approver): void
    {
        // Expire any other active permissions for this user
        BackdatePermission::where('user_id', $permission->user_id)
            ->where('status', 'approved')
            ->where('id', '!=', $permission->id)
            ->update(['status' => 'expired']);
        
        $permission->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'granted_until' => now()->endOfDay(), // Until end of today
        ]);
    }
    
    public function reject(BackdatePermission $permission, User $rejector, string $reason): void
    {
        $permission->update([
            'status' => 'rejected',
            'rejected_by' => $rejector->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }
    
    public function expirePermissions(): int
    {
        return BackdatePermission::where('status', 'approved')
            ->where('granted_until', '<', now())
            ->update(['status' => 'expired']);
    }
}
```



#### TaskAnalyticsService

```php
namespace App\Services\Modules\Activity;

use App\Models\Modules\Activity\EmployeeTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TaskAnalyticsService
{
    public function getPersonalStats(int $userId, int $businessUnitId): array
    {
        $baseQuery = EmployeeTask::forBusinessUnit($businessUnitId)
            ->forParticipant($userId);
        
        return [
            'total' => (clone $baseQuery)->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'overdue' => (clone $baseQuery)->overdue()->count(),
            'total_hours' => round((clone $baseQuery)
                ->where('status', 'completed')
                ->sum('duration_minutes') / 60, 1),
        ];
    }
    
    public function getActivityBreakdown(int $userId, int $businessUnitId): array
    {
        return EmployeeTask::forBusinessUnit($businessUnitId)
            ->forParticipant($userId)
            ->join('activity_types', 'employee_tasks.activity_type_id', '=', 'activity_types.id')
            ->select(
                'activity_types.name as activity_type',
                'activity_types.color',
                DB::raw('COUNT(*) as count'),
                DB::raw('ROUND(SUM(duration_minutes) / 60, 1) as hours')
            )
            ->groupBy('activity_types.id', 'activity_types.name', 'activity_types.color')
            ->get()
            ->toArray();
    }
    
    public function getWeeklyTrend(int $userId, int $businessUnitId): array
    {
        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();
        
        $completed = EmployeeTask::forBusinessUnit($businessUnitId)
            ->forParticipant($userId)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(completed_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->pluck('count', 'date');
        
        $created = EmployeeTask::forBusinessUnit($businessUnitId)
            ->forParticipant($userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->pluck('count', 'date');
        
        $result = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $result[] = [
                'date' => Carbon::parse($date)->format('M d'),
                'completed' => $completed[$date] ?? 0,
                'created' => $created[$date] ?? 0,
            ];
        }
        
        return $result;
    }
    
    public function getDepartmentWorkload(int $departmentId, int $businessUnitId): array
    {
        return EmployeeTask::forBusinessUnit($businessUnitId)
            ->forDepartment($departmentId)
            ->join('task_participants', 'employee_tasks.id', '=', 'task_participants.employee_task_id')
            ->join('users', 'task_participants.user_id', '=', 'users.id')
            ->select(
                'users.id as user_id',
                'users.name as user_name',
                DB::raw('COUNT(DISTINCT employee_tasks.id) as total_tasks'),
                DB::raw('SUM(CASE WHEN employee_tasks.status = "completed" THEN 1 ELSE 0 END) as completed_tasks'),
                DB::raw('SUM(CASE WHEN employee_tasks.status = "in_progress" THEN 1 ELSE 0 END) as in_progress_tasks'),
                DB::raw('ROUND(SUM(employee_tasks.duration_minutes) / 60, 1) as total_hours')
            )
            ->groupBy('users.id', 'users.name')
            ->get()
            ->toArray();
    }
    
    public function getCompletionRate(int $departmentId, int $businessUnitId, Carbon $startDate, Carbon $endDate): array
    {
        $tasks = EmployeeTask::forBusinessUnit($businessUnitId)
            ->forDepartment($departmentId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN completed_at IS NOT NULL AND completed_at <= due_date THEN 1 ELSE 0 END) as on_time'),
                DB::raw('SUM(CASE WHEN completed_at IS NOT NULL AND completed_at > due_date THEN 1 ELSE 0 END) as late'),
                DB::raw('SUM(CASE WHEN status NOT IN ("completed", "cancelled") AND due_date < CURDATE() THEN 1 ELSE 0 END) as overdue')
            )
            ->groupBy('date')
            ->get();
        
        return $tasks->map(function ($item) {
            return [
                'date' => Carbon::parse($item->date)->format('M d'),
                'on_time' => $item->on_time,
                'late' => $item->late,
                'overdue' => $item->overdue,
                'on_time_rate' => $item->total > 0 ? round(($item->on_time / $item->total) * 100, 1) : 0,
            ];
        })->toArray();
    }
}
```

## Routes

```php
// routes/web.php

use App\Http\Controllers\Modules\Activity\ActivityController;
use App\Http\Controllers\Modules\Activity\TaskController;
use App\Http\Controllers\Modules\Activity\BackdateController;
use App\Http\Controllers\Modules\Activity\AnalyticsController;

Route::prefix('activity')
    ->name('activity.')
    ->middleware(['auth', 'verified', 'ensure.business.unit.selected'])
    ->group(function () {
        
        // Inertia Pages
        Route::get('/', [ActivityController::class, 'dashboard'])->name('dashboard');
        Route::get('/tasks', [ActivityController::class, 'index'])->name('tasks.index');
        Route::get('/calendar', [ActivityController::class, 'calendar'])->name('calendar');
        Route::get('/kanban', [ActivityController::class, 'kanban'])->name('kanban');
        
        // Backdate Pages
        Route::get('/backdate/requests', [BackdateController::class, 'index'])->name('backdate.requests');
        Route::get('/backdate/approvals', [BackdateController::class, 'approvals'])
            ->middleware('can:approve-backdate')
            ->name('backdate.approvals');
        
        // Analytics Pages
        Route::get('/analytics/department', [AnalyticsController::class, 'department'])
            ->middleware('can:view-department-analytics')
            ->name('analytics.department');
        Route::get('/analytics/business-unit', [AnalyticsController::class, 'businessUnit'])
            ->middleware('can:view-reports')
            ->name('analytics.business-unit');
    });

// JSON API Routes
Route::prefix('api/activity')
    ->name('api.activity.')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        
        // Task CRUD
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        
        // Task Actions
        Route::post('/tasks/{task}/start', [TaskController::class, 'start'])->name('tasks.start');
        Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
        Route::post('/tasks/{task}/cancel', [TaskController::class, 'cancel'])->name('tasks.cancel');
        Route::post('/tasks/{task}/join', [TaskController::class, 'join'])->name('tasks.join');
        Route::put('/tasks/{task}/duration', [TaskController::class, 'updateDuration'])->name('tasks.duration');
        
        // Task Polling
        Route::get('/overdue-count', [TaskController::class, 'overdueCount'])->name('overdue-count');
        
        // Backdate API
        Route::post('/backdate', [BackdateController::class, 'store'])->name('backdate.store');
        Route::post('/backdate/{permission}/approve', [BackdateController::class, 'approve'])->name('backdate.approve');
        Route::post('/backdate/{permission}/reject', [BackdateController::class, 'reject'])->name('backdate.reject');
        Route::get('/backdate-permission', [BackdateController::class, 'current'])->name('backdate.current');
        
        // Analytics API
        Route::get('/analytics', [AnalyticsController::class, 'data'])->name('analytics.data');
        
        // Sub-activities by activity type
        Route::get('/activity-types/{activityType}/sub-activities', function ($activityTypeId) {
            return SubActivity::where('activity_type_id', $activityTypeId)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        })->name('sub-activities');
    });

// Admin Routes (Inertia)
Route::prefix('admin/activity')
    ->name('admin.activity.')
    ->middleware(['auth', 'admin.access'])
    ->group(function () {
        Route::resource('activity-types', ActivityTypeController::class);
        Route::resource('sub-activities', SubActivityController::class);
    });
```

## Permission Matrix

| Role | View Own | View Dept | Edit Own | Edit Dept | Delete | Analytics | Approve Backdate |
|------|----------|-----------|----------|-----------|--------|-----------|------------------|
| Regular User | ✅ | ✅ | ✅ | ❌ | Own only | Personal | ❌ |
| Dept Head | ✅ | ✅ | ✅ | ✅ | Dept | Department | ✅ |
| Top Management | ✅ | ✅ | ❌ | ❌ | ❌ | Business Unit | ❌ |
| Super Admin | ✅ | ✅ | ✅ | ✅ | ✅ | All | ✅ |

## Implementation Phases

### Phase 1: Core Infrastructure (Backend)
- [ ] Create migrations (activity_types, sub_activities, employee_tasks, task_participants, task_attachments, backdate_permissions)
- [ ] Create models with relationships (Spatie Activity Log)
- [ ] Create services (TaskService, BackdatePermissionService, TaskAnalyticsService)
- [ ] Seed default activity types & sub-activities
- [ ] Setup permissions (Spatie Permission)

### Phase 2: React Components & Shared UI
- [ ] Setup Zustand store (activityStore.ts)
- [ ] Create custom hooks (useTaskPolling, useBackdatePermission, useTaskAnalytics)
- [ ] Create shared components (TaskCard, TaskStatusBadge, TaskFilters, ParticipantList, etc.)
- [ ] Setup framer-motion animations
- [ ] Setup sonner toast notifications

### Phase 3: Task Management Pages
- [ ] Dashboard page (recharts integration)
- [ ] Task List page (@tanstack/react-table)
- [ ] Task Form page (create/edit with backdate validation)
- [ ] Task Detail page (view with actions)
- [ ] Calendar View (@fullcalendar/react)
- [ ] Kanban View (@dnd-kit)

### Phase 4: Backdate Permission System
- [ ] Backdate Requests page (employee view)
- [ ] Backdate Approvals page (department head view)
- [ ] BackdateController (JSON API)
- [ ] BackdatePermissionService
- [ ] Client-side polling for permission expiry

### Phase 5: Analytics & Reporting
- [ ] Department Analytics page (recharts)
- [ ] Business Unit Analytics page (recharts)
- [ ] AnalyticsController (JSON API)
- [ ] TaskAnalyticsService
- [ ] Date range filters

### Phase 6: Admin Pages (Activity Types & Sub-Activities)
- [ ] Activity Types Index (Inertia)
- [ ] Activity Types Form (Inertia)
- [ ] Sub-Activities Index (Inertia)
- [ ] Sub-Activities Form (Inertia)
- [ ] Color picker component

### Phase 7: Integration & Testing
- [ ] Add to sidebar navigation
- [ ] Add to main dashboard widgets
- [ ] Test all CRUD operations
- [ ] Test backdate permission workflow
- [ ] Test collaborative tasks (join, shared status)
- [ ] Test analytics calculations
- [ ] Test polling mechanisms
- [ ] Performance optimization
- [ ] Permission testing for all roles

### Phase 8: Edge Cases & Polish
- [ ] User department transfer handling
- [ ] Multi-day task duration warnings
- [ ] Abnormal duration prevention (>24h, >72h)
- [ ] Duration manual edit with audit log
- [ ] Attachment upload validation
- [ ] Error handling and user feedback
- [ ] Accessibility testing
- [ ] Mobile responsiveness

## Key Differences from Traditional Approach

### ❌ What We're NOT Doing
1. **NO Livewire Components** - Everything is React/Inertia
2. **NO Laravel Queue Jobs** - All background tasks handled by React polling
3. **NO Scheduled Tasks** - No cron jobs in Kernel.php
4. **NO Chart.js** - Using recharts instead
5. **NO Laravel Notifications** - Using sonner toast for client-side notifications
6. **NO Blade Views** - Pure React components

### ✅ What We're Doing Instead
1. **React Polling** - useEffect + setInterval for overdue checks, permission expiry
2. **Zustand** - Client-side state management for filters, UI state, cache
3. **Sonner** - Toast notifications for user feedback
4. **Recharts** - Modern, React-native charting library
5. **@fullcalendar/react** - Calendar view with full React integration
6. **@dnd-kit** - Drag and drop for Kanban board
7. **@tanstack/react-table** - Powerful data tables
8. **framer-motion** - Smooth animations and transitions
9. **@headlessui/react** - Accessible UI components
10. **lucide-react** - Modern icon library

## Performance Considerations

### Client-Side Optimization
- **Debounced Search** - 300ms debounce on search inputs
- **Lazy Loading** - Code splitting for pages
- **Memoization** - useMemo for expensive calculations
- **Virtual Scrolling** - For large task lists (if needed)
- **Optimistic Updates** - Update UI before server response

### Server-Side Optimization
- **Eager Loading** - Load relationships to avoid N+1 queries
- **Query Optimization** - Indexed columns for filtering
- **Pagination** - Limit results per page
- **Caching** - Cache analytics calculations (optional)

### Polling Strategy
- **Overdue Check** - Every 60 seconds
- **Permission Check** - Every 5 minutes
- **Analytics Refresh** - On-demand only (no auto-refresh)

## Security Considerations

1. **Authorization** - Policy classes for all actions
2. **Validation** - Both client-side (Zod) and server-side (Laravel)
3. **CSRF Protection** - Inertia handles automatically
4. **File Upload** - Validate type, size, and sanitize filenames
5. **SQL Injection** - Use Eloquent ORM (parameterized queries)
6. **XSS Protection** - React escapes by default
7. **Backdate Validation** - Server-side check for permission

## Testing Strategy

### Unit Tests (PHPUnit)
- TaskService methods
- BackdatePermissionService methods
- TaskAnalyticsService calculations
- Model scopes and helpers

### Integration Tests (PHPUnit)
- API endpoints
- Authorization policies
- Backdate permission workflow
- Task lifecycle (create → start → complete)

### Component Tests (Vitest + React Testing Library)
- TaskCard rendering
- TaskForm validation
- TaskStatusBadge variants
- BackdatePermissionBanner countdown

### E2E Tests (Optional)
- Complete task creation flow
- Backdate request and approval flow
- Collaborative task (join and complete)
- Analytics page rendering


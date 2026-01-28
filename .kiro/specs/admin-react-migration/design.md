# Admin Panel React Migration - Design Document

## 1. Architecture Overview

### 1.1 High-Level Architecture
```
┌─────────────────────────────────────────────────────────────┐
│                     Browser (Client)                         │
│  ┌───────────────────────────────────────────────────────┐  │
│  │           React/Inertia Admin Pages                   │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  │  │
│  │  │  Dashboard  │  │    Users    │  │ Business    │  │  │
│  │  │             │  │             │  │   Units     │  │  │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  │  │
│  │                                                       │  │
│  │  ┌──────────────────────────────────────────────┐   │  │
│  │  │      Shared Admin Components                 │   │  │
│  │  │  - DataTable  - Forms  - Modals  - Cards    │   │  │
│  │  └──────────────────────────────────────────────┘   │  │
│  └───────────────────────────────────────────────────────┘  │
│                           ↕ Inertia.js                       │
└─────────────────────────────────────────────────────────────┘
                            ↕ HTTP
┌─────────────────────────────────────────────────────────────┐
│                    Laravel Backend                           │
│  ┌───────────────────────────────────────────────────────┐  │
│  │              Admin Controllers                        │  │
│  │  - AdminController                                    │  │
│  │  - UserManagementController                           │  │
│  │  - BusinessUnitController                             │  │
│  │  - DepartmentController                               │  │
│  │  - etc.                                               │  │
│  └───────────────────────────────────────────────────────┘  │
│                           ↕                                  │
│  ┌───────────────────────────────────────────────────────┐  │
│  │              Models & Services                        │  │
│  │  - User, BusinessUnit, Department                     │  │
│  │  - Validation, Authorization                          │  │
│  └───────────────────────────────────────────────────────┘  │
│                           ↕                                  │
│  ┌───────────────────────────────────────────────────────┐  │
│  │                   Database                            │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### 1.2 Component Hierarchy
```
AdminLayout
├── Sidebar (existing)
├── Header (existing)
└── AdminContent
    ├── AdminDashboard
    │   ├── StatsGrid
    │   │   ├── StatCard (reusable)
    │   │   └── StatCard
    │   ├── RecentUsersTable
    │   ├── BusinessUnitStats
    │   └── PRTrendsChart
    │
    ├── UserManagement
    │   ├── UserIndex
    │   │   ├── UserFilters
    │   │   ├── UserTable (DataTable)
    │   │   └── Pagination
    │   ├── UserCreate
    │   │   └── UserForm
    │   ├── UserEdit
    │   │   └── UserForm
    │   └── UserShow
    │       └── UserDetailCard
    │
    ├── BusinessUnitManagement
    │   ├── BusinessUnitIndex
    │   │   ├── BusinessUnitFilters
    │   │   ├── BusinessUnitGrid
    │   │   └── Pagination
    │   ├── BusinessUnitCreate
    │   │   └── BusinessUnitForm
    │   ├── BusinessUnitEdit
    │   │   └── BusinessUnitForm
    │   └── BusinessUnitShow
    │       └── BusinessUnitDetailCard
    │
    └── ... (other admin sections)
```

## 2. Component Design

### 2.1 Shared Components

#### 2.1.1 StatCard Component
**Purpose:** Display key metrics with icon and trend
**Props:**
```typescript
interface StatCardProps {
  title: string;
  value: number | string;
  icon: React.ComponentType<{ className?: string }>;
  trend?: {
    value: number;
    direction: 'up' | 'down';
  };
  color?: 'indigo' | 'emerald' | 'amber' | 'red';
  loading?: boolean;
}
```

**Design:**
```tsx
<div className="bg-white rounded-xl border border-gray-100 p-6">
  <div className="flex items-center justify-between">
    <div>
      <p className="text-sm text-gray-600">{title}</p>
      <p className="text-2xl font-bold text-gray-900 mt-1">{value}</p>
      {trend && (
        <p className="text-sm text-emerald-600 mt-2">
          ↑ {trend.value}% from last month
        </p>
      )}
    </div>
    <div className="p-3 bg-indigo-100 rounded-lg">
      <Icon className="w-6 h-6 text-indigo-600" />
    </div>
  </div>
</div>
```

#### 2.1.2 DataTable Component
**Purpose:** Reusable table with sorting, filtering, pagination
**Props:**
```typescript
interface DataTableProps<T> {
  data: T[];
  columns: ColumnDef<T>[];
  pagination?: PaginationData;
  onPageChange?: (page: number) => void;
  onSort?: (column: string, direction: 'asc' | 'desc') => void;
  loading?: boolean;
  emptyMessage?: string;
}
```

**Features:**
- TanStack Table integration
- Sortable columns
- Selectable rows
- Responsive design
- Loading skeleton
- Empty state

#### 2.1.3 FormModal Component
**Purpose:** Modal dialog for forms
**Props:**
```typescript
interface FormModalProps {
  isOpen: boolean;
  onClose: () => void;
  title: string;
  children: React.ReactNode;
  size?: 'sm' | 'md' | 'lg' | 'xl';
}
```

#### 2.1.4 ConfirmDialog Component
**Purpose:** Confirmation dialog for destructive actions
**Props:**
```typescript
interface ConfirmDialogProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: () => void;
  title: string;
  message: string;
  confirmText?: string;
  cancelText?: string;
  variant?: 'danger' | 'warning' | 'info';
  loading?: boolean;
}
```

#### 2.1.5 LogoUpload Component
**Purpose:** Image upload with preview and crop
**Props:**
```typescript
interface LogoUploadProps {
  value?: string;
  onChange: (file: File | null) => void;
  onRemove?: () => void;
  maxSize?: number; // in MB
  accept?: string;
  preview?: boolean;
}
```

**Features:**
- Drag and drop
- Image preview
- File validation
- Remove functionality
- Loading state

#### 2.1.6 ColorPicker Component
**Purpose:** Color selection for activity types
**Props:**
```typescript
interface ColorPickerProps {
  value: string;
  onChange: (color: string) => void;
  presetColors?: string[];
}
```

### 2.2 Page Components

#### 2.2.1 Admin Dashboard
**File:** `resources/js/inertia/Pages/Admin/Dashboard.tsx`

**Props:**
```typescript
interface AdminDashboardProps {
  stats: {
    total_users: number;
    active_users: number;
    super_admins: number;
    total_business_units: number;
    active_business_units: number;
    total_departments: number;
    total_assignments: number;
    total_purchase_requests: number;
    pending_approvals: number;
    active_sequences: number;
  };
  recentUsers: User[];
  businessUnitStats: BusinessUnit[];
  monthlyPRs: Record<string, number>;
}
```

**Layout:**
```
┌─────────────────────────────────────────────────────────┐
│  Admin Dashboard                                        │
├─────────────────────────────────────────────────────────┤
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐│
│  │ Total    │  │ Active   │  │ Business │  │ Pending  ││
│  │ Users    │  │ Users    │  │ Units    │  │ Approvals││
│  │  150     │  │  142     │  │    4     │  │    12    ││
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘│
├─────────────────────────────────────────────────────────┤
│  Recent Users                    │  Monthly PR Trends   │
│  ┌────────────────────────────┐  │  ┌─────────────────┐│
│  │ Name    Email    Role      │  │  │   Chart.js      ││
│  │ John    john@    Admin     │  │  │   Line Chart    ││
│  │ Jane    jane@    User      │  │  │                 ││
│  └────────────────────────────┘  │  └─────────────────┘│
├─────────────────────────────────────────────────────────┤
│  Business Unit Statistics                               │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐             │
│  │ WNS      │  │ UK       │  │ MRP      │             │
│  │ 45 users │  │ 32 users │  │ 28 users │             │
│  └──────────┘  └──────────┘  └──────────┘             │
└─────────────────────────────────────────────────────────┘
```

#### 2.2.2 User Management Index
**File:** `resources/js/inertia/Pages/Admin/Users/Index.tsx`

**Props:**
```typescript
interface UserIndexProps {
  users: PaginatedData<User>;
  businessUnits: BusinessUnit[];
  departments: Department[];
  filters: {
    search?: string;
    business_unit?: number;
    department?: number;
    global_role?: string;
  };
}
```

**Features:**
- Search by name/email
- Filter by business unit, department, role
- Sortable columns
- Pagination
- Bulk actions (future)
- Quick actions (edit, view, deactivate)

**Layout:**
```
┌─────────────────────────────────────────────────────────┐
│  User Management                          [+ Create]    │
├─────────────────────────────────────────────────────────┤
│  🔍 Search  [Business Unit ▼] [Department ▼] [Role ▼]  │
├─────────────────────────────────────────────────────────┤
│  Name          Email         Role      BU      Actions  │
│  ────────────────────────────────────────────────────── │
│  John Doe      john@...      Admin     WNS     [⋮]     │
│  Jane Smith    jane@...      User      UK      [⋮]     │
│  ...                                                     │
├─────────────────────────────────────────────────────────┤
│  Showing 1-15 of 150        [← 1 2 3 ... 10 →]         │
└─────────────────────────────────────────────────────────┘
```

#### 2.2.3 User Create/Edit Form
**File:** `resources/js/inertia/Pages/Admin/Users/Create.tsx`

**Props:**
```typescript
interface UserFormProps {
  user?: User; // undefined for create, defined for edit
  businessUnits: BusinessUnit[];
  users: User[]; // for supervisor selection
  errors?: Record<string, string>;
}
```

**Form Fields:**
- Name (required)
- Email (required, unique)
- Phone Number (optional)
- Password (required for create, optional for edit)
- Password Confirmation
- Global Role (super_admin | user)
- Supervisor (optional)
- Is Active (checkbox)
- Business Unit Assignments (dynamic array)
  - Business Unit (select)
  - Department (select, loaded dynamically)
  - Position (select, loaded dynamically)
  - Primary (radio button)

**Validation:**
- React Hook Form with Zod schema
- Real-time validation
- Server-side validation errors display

**Layout:**
```
┌─────────────────────────────────────────────────────────┐
│  Create User                                            │
├─────────────────────────────────────────────────────────┤
│  Basic Information                                      │
│  ┌─────────────────────────────────────────────────┐   │
│  │ Name: [________________]                        │   │
│  │ Email: [________________]                       │   │
│  │ Phone: [________________]                       │   │
│  │ Password: [________________]                    │   │
│  │ Confirm: [________________]                     │   │
│  │ Role: [User ▼]                                  │   │
│  │ Supervisor: [Select ▼]                          │   │
│  │ ☑ Active                                        │   │
│  └─────────────────────────────────────────────────┘   │
├─────────────────────────────────────────────────────────┤
│  Business Unit Assignments                              │
│  ┌─────────────────────────────────────────────────┐   │
│  │ ○ [WNS ▼] [IT Dept ▼] [Developer ▼]  [Remove] │   │
│  │ ● [UK ▼]  [HR Dept ▼] [Manager ▼]    [Remove] │   │
│  │                                                  │   │
│  │ [+ Add Business Unit]                           │   │
│  └─────────────────────────────────────────────────┘   │
├─────────────────────────────────────────────────────────┤
│                              [Cancel]  [Save User]      │
└─────────────────────────────────────────────────────────┘
```

#### 2.2.4 Business Unit Management
**File:** `resources/js/inertia/Pages/Admin/BusinessUnits/Index.tsx`

**Features:**
- Grid/List view toggle
- Search functionality
- Status filter (active/inactive)
- Logo preview
- Quick stats (departments, users, PRs)

**Layout (Grid View):**
```
┌─────────────────────────────────────────────────────────┐
│  Business Units                       [+ Create]        │
├─────────────────────────────────────────────────────────┤
│  🔍 Search  [Status ▼]  [Grid ⊞] [List ☰]             │
├─────────────────────────────────────────────────────────┤
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │ [WNS Logo]   │  │ [UK Logo]    │  │ [MRP Logo]   │ │
│  │ WNS          │  │ UK           │  │ MRP          │ │
│  │ 5 Depts      │  │ 4 Depts      │  │ 3 Depts      │ │
│  │ 45 Users     │  │ 32 Users     │  │ 28 Users     │ │
│  │ [Edit] [⋮]   │  │ [Edit] [⋮]   │  │ [Edit] [⋮]   │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────┘
```

## 3. State Management

### 3.1 Local State (useState)
- Form inputs
- Modal open/close
- Loading states
- UI toggles (grid/list view)

### 3.2 Form State (React Hook Form)
- Form values
- Validation errors
- Dirty fields
- Submit state

### 3.3 Global State (Zustand - if needed)
```typescript
interface AdminStore {
  // Filters persistence
  userFilters: UserFilters;
  setUserFilters: (filters: UserFilters) => void;
  
  // UI preferences
  businessUnitViewMode: 'grid' | 'list';
  setBusinessUnitViewMode: (mode: 'grid' | 'list') => void;
  
  // Cache
  departmentCache: Record<number, Department[]>;
  positionCache: Record<number, Position[]>;
  cacheDepartments: (businessUnitId: number, departments: Department[]) => void;
  cachePositions: (departmentId: number, positions: Position[]) => void;
}
```

## 4. Data Flow

### 4.1 Page Load Flow
```
1. User navigates to /admin/users
2. Laravel controller fetches data
3. Controller returns Inertia response with props
4. Inertia renders React component
5. Component displays data
6. No full page reload
```

### 4.2 Form Submission Flow
```
1. User fills form
2. React Hook Form validates
3. On submit, Inertia.post() sends data
4. Laravel validates and processes
5. Returns Inertia response (redirect or errors)
6. React updates UI (success message or errors)
7. No full page reload
```

### 4.3 Dynamic Data Loading (Departments/Positions)
```
1. User selects business unit
2. React triggers axios.get('/admin/business-units/{id}/departments')
3. Laravel returns JSON
4. React updates department dropdown
5. User selects department
6. React triggers axios.get('/admin/departments/{id}/positions')
7. Laravel returns JSON
8. React updates position dropdown
```

## 5. API Endpoints

### 5.1 Existing Endpoints (to be updated)
```php
// Return Inertia responses instead of Blade views
Route::get('/admin', [AdminController::class, 'index']); // Inertia::render('Admin/Dashboard')
Route::resource('/admin/users', UserManagementController::class); // Inertia responses
Route::resource('/admin/business-units', BusinessUnitController::class); // Inertia responses
// ... etc
```

### 5.2 New AJAX Endpoints
```php
// Keep existing AJAX endpoints
Route::get('/admin/business-units/{businessUnit}/departments', [UserManagementController::class, 'getDepartments']);
Route::get('/admin/departments/{department}/positions', [UserManagementController::class, 'getPositions']);

// Add new endpoints if needed
Route::get('/admin/api/users/search', [UserManagementController::class, 'search']);
Route::get('/admin/api/business-units/stats', [BusinessUnitController::class, 'stats']);
```

## 6. Validation

### 6.1 Frontend Validation (Zod)
```typescript
// User form schema
const userFormSchema = z.object({
  name: z.string().min(1, 'Name is required').max(255),
  email: z.string().email('Invalid email').max(255),
  phone_number: z.string().max(20).optional(),
  password: z.string().min(8, 'Password must be at least 8 characters').optional(),
  password_confirmation: z.string().optional(),
  global_role: z.enum(['super_admin', 'user']),
  supervisor_id: z.number().optional(),
  is_active: z.boolean(),
  business_units: z.array(z.object({
    business_unit_id: z.number(),
    department_id: z.number(),
    position_id: z.number(),
  })).min(1, 'At least one business unit assignment is required'),
  primary_business_unit: z.number(),
}).refine((data) => {
  if (data.password && data.password !== data.password_confirmation) {
    return false;
  }
  return true;
}, {
  message: 'Passwords do not match',
  path: ['password_confirmation'],
});
```

### 6.2 Backend Validation (Laravel)
```php
// Keep existing validation rules
$request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email',
    // ... etc
]);
```

## 7. Error Handling

### 7.1 Form Errors
```typescript
// Display validation errors from backend
{errors.name && (
  <p className="text-sm text-red-600 mt-1">{errors.name}</p>
)}
```

### 7.2 API Errors
```typescript
// Handle API errors with toast notifications
try {
  await router.post('/admin/users', formData);
  toast.success('User created successfully');
} catch (error) {
  toast.error('Failed to create user');
}
```

### 7.3 Error Boundaries
```typescript
// Wrap admin pages with error boundary
<ErrorBoundary fallback={<AdminErrorPage />}>
  <AdminDashboard {...props} />
</ErrorBoundary>
```

## 8. Performance Optimization

### 8.1 Code Splitting
```typescript
// Lazy load admin pages
const AdminDashboard = lazy(() => import('./Pages/Admin/Dashboard'));
const UserIndex = lazy(() => import('./Pages/Admin/Users/Index'));
// ... etc
```

### 8.2 Data Caching
```typescript
// Cache departments and positions to avoid repeated API calls
const useDepartmentCache = () => {
  const cache = useRef<Record<number, Department[]>>({});
  
  const getDepartments = async (businessUnitId: number) => {
    if (cache.current[businessUnitId]) {
      return cache.current[businessUnitId];
    }
    const response = await axios.get(`/admin/business-units/${businessUnitId}/departments`);
    cache.current[businessUnitId] = response.data;
    return response.data;
  };
  
  return { getDepartments };
};
```

### 8.3 Debounced Search
```typescript
// Debounce search input
const debouncedSearch = useMemo(
  () => debounce((value: string) => {
    router.get('/admin/users', { search: value }, { preserveState: true });
  }, 300),
  []
);
```

## 9. Accessibility

### 9.1 Keyboard Navigation
- Tab order follows logical flow
- Enter key submits forms
- Escape key closes modals
- Arrow keys navigate tables

### 9.2 ARIA Labels
```tsx
<button
  aria-label="Edit user"
  aria-describedby="user-edit-tooltip"
>
  <PencilIcon className="w-4 h-4" />
</button>
```

### 9.3 Focus Management
```typescript
// Focus first input on modal open
useEffect(() => {
  if (isOpen) {
    firstInputRef.current?.focus();
  }
}, [isOpen]);
```

## 10. Testing Strategy

### 10.1 Component Tests
```typescript
// Test StatCard component
describe('StatCard', () => {
  it('renders title and value', () => {
    render(<StatCard title="Total Users" value={150} icon={UserIcon} />);
    expect(screen.getByText('Total Users')).toBeInTheDocument();
    expect(screen.getByText('150')).toBeInTheDocument();
  });
  
  it('shows loading skeleton when loading', () => {
    render(<StatCard title="Total Users" value={0} icon={UserIcon} loading />);
    expect(screen.getByTestId('skeleton')).toBeInTheDocument();
  });
});
```

### 10.2 Integration Tests
```typescript
// Test user creation flow
describe('User Creation', () => {
  it('creates user successfully', async () => {
    render(<UserCreate businessUnits={mockBusinessUnits} users={mockUsers} />);
    
    // Fill form
    await userEvent.type(screen.getByLabelText('Name'), 'John Doe');
    await userEvent.type(screen.getByLabelText('Email'), 'john@example.com');
    // ... fill other fields
    
    // Submit
    await userEvent.click(screen.getByText('Save User'));
    
    // Assert
    await waitFor(() => {
      expect(mockRouter.post).toHaveBeenCalledWith('/admin/users', expect.any(Object));
    });
  });
});
```

## 11. Migration Checklist

### 11.1 Per Page Migration
- [ ] Create TypeScript types
- [ ] Create React page component
- [ ] Create shared components (if needed)
- [ ] Update controller to return Inertia response
- [ ] Test all CRUD operations
- [ ] Test validation (frontend + backend)
- [ ] Test error handling
- [ ] Test responsive design
- [ ] Test accessibility
- [ ] Update tests
- [ ] Update documentation

### 11.2 Final Steps
- [ ] Remove old Blade views
- [ ] Update navigation links
- [ ] Update breadcrumbs
- [ ] Performance testing
- [ ] User acceptance testing
- [ ] Production deployment

## 12. Rollout Plan

### 12.1 Feature Flag
```php
// config/features.php
return [
    'admin_react' => env('FEATURE_ADMIN_REACT', false),
];

// In controller
if (config('features.admin_react')) {
    return Inertia::render('Admin/Dashboard', $data);
} else {
    return view('admin.dashboard', $data);
}
```

### 12.2 Gradual Rollout
1. Enable for super admins only
2. Monitor for errors and performance
3. Enable for all admins
4. Collect feedback
5. Make adjustments
6. Remove feature flag
7. Delete old Blade views

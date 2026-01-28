# Admin Panel React Migration - Requirements

## 1. Overview

### 1.1 Purpose
Migrate all admin panel pages from Blade/Livewire to React/Inertia for consistent user experience, better performance, and modern UI/UX across the entire Oasis application.

### 1.2 Background
Currently, the admin panel uses traditional Blade templates while other modules (Purchasing, Activity Tracking) use React/Inertia. This creates inconsistency in:
- User experience and interaction patterns
- Code maintainability and structure
- Performance and loading behavior
- UI component reusability

### 1.3 Goals
- Unified tech stack across all application modules
- Consistent UI/UX patterns and components
- Improved performance with client-side routing
- Better developer experience with TypeScript
- Reusable React components for admin functionality

## 2. Scope

### 2.1 Pages to Migrate

#### 2.1.1 Admin Dashboard (`/admin`)
**Current State:** Blade view with Livewire components
**Features:**
- System statistics overview (users, business units, departments, PRs)
- Recent users list
- Business unit breakdown with user counts
- Monthly PR trends chart
- Quick action cards

**Acceptance Criteria:**
- AC 2.1.1.1: Display real-time system statistics with loading states
- AC 2.1.1.2: Show recent users table with pagination
- AC 2.1.1.3: Render business unit statistics cards
- AC 2.1.1.4: Display monthly PR trends chart using Chart.js
- AC 2.1.1.5: Provide quick navigation to admin sections
- AC 2.1.1.6: Support data refresh without full page reload

#### 2.1.2 User Management (`/admin/users`)
**Current State:** Blade CRUD views
**Features:**
- User list with filters (business unit, department, role, search)
- Create user form with multi-business unit assignment
- Edit user form with password update
- User detail view with assignments and relationships
- Deactivate user functionality

**Acceptance Criteria:**
- AC 2.1.2.1: Display paginated user list with real-time search
- AC 2.1.2.2: Filter users by business unit, department, and role
- AC 2.1.2.3: Create user with multi-business unit assignment form
- AC 2.1.2.4: Edit user with dynamic department/position loading
- AC 2.1.2.5: Show user details with all relationships
- AC 2.1.2.6: Deactivate users with confirmation dialog
- AC 2.1.2.7: Validate form inputs with real-time feedback
- AC 2.1.2.8: Handle AJAX requests for departments and positions

#### 2.1.3 Business Unit Management (`/admin/business-units`)
**Current State:** Blade CRUD views
**Features:**
- Business unit list with search and status filter
- Create business unit with logo upload
- Edit business unit with logo management
- Business unit detail view with statistics
- Toggle business unit status
- Delete business unit with validation

**Acceptance Criteria:**
- AC 2.1.3.1: Display business unit list with search and filters
- AC 2.1.3.2: Create business unit with logo upload preview
- AC 2.1.3.3: Edit business unit with logo removal option
- AC 2.1.3.4: Show business unit details with department/user stats
- AC 2.1.3.5: Toggle active/inactive status with confirmation
- AC 2.1.3.6: Delete business unit with validation checks
- AC 2.1.3.7: Display hierarchical parent-child relationships

#### 2.1.4 Department Management (`/admin/departments`)
**Current State:** Blade CRUD views
**Features:**
- Department list with business unit filter
- Create department form
- Edit department form
- Department detail view
- Purchasing configuration per department

**Acceptance Criteria:**
- AC 2.1.4.1: Display department list with business unit grouping
- AC 2.1.4.2: Create department with business unit selection
- AC 2.1.4.3: Edit department with position management
- AC 2.1.4.4: Show department details with user assignments
- AC 2.1.4.5: Configure purchasing settings per department
- AC 2.1.4.6: Manage department positions inline

#### 2.1.5 PR Category Management (`/admin/pr-categories`)
**Current State:** Blade CRUD views
**Features:**
- Category list with search
- Create category form
- Edit category form
- Delete category with validation

**Acceptance Criteria:**
- AC 2.1.5.1: Display category list with search functionality
- AC 2.1.5.2: Create category with validation
- AC 2.1.5.3: Edit category with real-time updates
- AC 2.1.5.4: Delete category with usage validation
- AC 2.1.5.5: Show category usage statistics

#### 2.1.6 Activity Type Management (`/admin/activity-types`)
**Current State:** Blade CRUD views
**Features:**
- Activity type list with color preview
- Create activity type with color picker
- Edit activity type
- Delete activity type with validation

**Acceptance Criteria:**
- AC 2.1.6.1: Display activity types with color indicators
- AC 2.1.6.2: Create activity type with color picker component
- AC 2.1.6.3: Edit activity type with preview
- AC 2.1.6.4: Delete activity type with sub-activity validation
- AC 2.1.6.5: Show activity type usage statistics

#### 2.1.7 Sub-Activity Management (`/admin/sub-activities`)
**Current State:** Blade CRUD views
**Features:**
- Sub-activity list grouped by activity type
- Create sub-activity form
- Edit sub-activity form
- Delete sub-activity

**Acceptance Criteria:**
- AC 2.1.7.1: Display sub-activities grouped by activity type
- AC 2.1.7.2: Create sub-activity with activity type selection
- AC 2.1.7.3: Edit sub-activity with validation
- AC 2.1.7.4: Delete sub-activity with usage validation
- AC 2.1.7.5: Show sub-activity usage statistics

#### 2.1.8 Notification Settings (`/admin/notification-settings`)
**Current State:** Blade form view
**Features:**
- SMTP configuration form
- Email settings management
- Test email functionality
- Email statistics view

**Acceptance Criteria:**
- AC 2.1.8.1: Display SMTP configuration form with validation
- AC 2.1.8.2: Update notification settings with real-time feedback
- AC 2.1.8.3: Send test email with loading state
- AC 2.1.8.4: Show email statistics dashboard
- AC 2.1.8.5: Mask sensitive data (passwords) in form
- AC 2.1.8.6: Validate SMTP settings before saving

#### 2.1.9 SLA Settings (`/admin/sla-settings`)
**Current State:** Blade form view
**Features:**
- SLA configuration per business unit
- Follow-up and completion time settings
- Email alert toggle

**Acceptance Criteria:**
- AC 2.1.9.1: Display SLA settings for all business units
- AC 2.1.9.2: Update SLA settings per business unit
- AC 2.1.9.3: Validate SLA time ranges (1-720 hours)
- AC 2.1.9.4: Toggle email alerts with confirmation
- AC 2.1.9.5: Show SLA compliance statistics

### 2.2 Out of Scope
- System Health page (placeholder, low priority)
- Number Sequence Management (placeholder, not implemented)
- Workflow Management (placeholder, not implemented)

## 3. Technical Requirements

### 3.1 Frontend Stack
- **Framework:** React 18+ with TypeScript
- **Routing:** Inertia.js for seamless SPA experience
- **Styling:** Tailwind CSS (consistent with existing pages)
- **Forms:** React Hook Form with Zod validation
- **State Management:** Zustand for global state (if needed)
- **Data Tables:** TanStack Table (React Table v8)
- **Charts:** Chart.js with react-chartjs-2
- **Icons:** Heroicons (consistent with existing)
- **File Upload:** React Dropzone for logo uploads

### 3.2 Backend Requirements
- **Controllers:** Update existing controllers to return Inertia responses
- **Validation:** Keep Laravel validation rules
- **Authorization:** Maintain existing middleware (admin.access)
- **API Endpoints:** Create AJAX endpoints for dynamic data (departments, positions)

### 3.3 Component Architecture
```
resources/js/inertia/
├── Pages/
│   └── Admin/
│       ├── Dashboard.tsx
│       ├── Users/
│       │   ├── Index.tsx
│       │   ├── Create.tsx
│       │   ├── Edit.tsx
│       │   └── Show.tsx
│       ├── BusinessUnits/
│       │   ├── Index.tsx
│       │   ├── Create.tsx
│       │   ├── Edit.tsx
│       │   └── Show.tsx
│       ├── Departments/
│       │   ├── Index.tsx
│       │   ├── Create.tsx
│       │   └── Edit.tsx
│       ├── PrCategories/
│       │   ├── Index.tsx
│       │   └── Form.tsx
│       ├── ActivityTypes/
│       │   ├── Index.tsx
│       │   └── Form.tsx
│       ├── SubActivities/
│       │   ├── Index.tsx
│       │   └── Form.tsx
│       ├── NotificationSettings/
│       │   ├── Index.tsx
│       │   └── Statistics.tsx
│       └── SlaSettings/
│           └── Index.tsx
├── components/
│   └── admin/
│       ├── StatCard.tsx
│       ├── UserTable.tsx
│       ├── BusinessUnitCard.tsx
│       ├── DepartmentForm.tsx
│       ├── ColorPicker.tsx
│       ├── LogoUpload.tsx
│       ├── SmtpConfigForm.tsx
│       └── SlaConfigForm.tsx
└── types/
    └── admin.ts
```

### 3.4 Type Definitions
```typescript
// User types
interface User {
  id: number;
  name: string;
  email: string;
  phone_number?: string;
  global_role: 'super_admin' | 'user';
  is_active: boolean;
  supervisor?: User;
  primary_department?: Department;
  primary_position?: Position;
  active_business_units: UserBusinessUnit[];
  created_at: string;
  updated_at: string;
}

// Business Unit types
interface BusinessUnit {
  id: number;
  name: string;
  code: string;
  logo?: string;
  description?: string;
  address?: string;
  phone?: string;
  email?: string;
  parent_id?: number;
  manager_id?: number;
  is_active: boolean;
  departments_count?: number;
  users_count?: number;
  purchase_requests_count?: number;
}

// Department types
interface Department {
  id: number;
  name: string;
  code: string;
  business_unit_id: number;
  business_unit?: BusinessUnit;
  is_active: boolean;
  positions?: Position[];
}

// And more...
```

## 4. User Experience Requirements

### 4.1 Navigation
- Maintain existing sidebar navigation structure
- Highlight active admin section
- Breadcrumb navigation for nested pages
- Back button functionality

### 4.2 Loading States
- Skeleton loaders for initial page load
- Spinner for form submissions
- Progress indicators for file uploads
- Optimistic UI updates where appropriate

### 4.3 Error Handling
- Display validation errors inline
- Show toast notifications for success/error
- Graceful error boundaries for React errors
- Maintain error state across navigation

### 4.4 Responsive Design
- Mobile-first approach
- Responsive tables with horizontal scroll
- Collapsible filters on mobile
- Touch-friendly form inputs

### 4.5 Accessibility
- ARIA labels for all interactive elements
- Keyboard navigation support
- Focus management in modals
- Screen reader friendly tables

## 5. Performance Requirements

### 5.1 Page Load
- Initial page load < 2 seconds
- Subsequent navigation < 500ms (Inertia SPA)
- Lazy load heavy components (charts, tables)

### 5.2 Data Fetching
- Implement pagination for large datasets
- Debounce search inputs (300ms)
- Cache frequently accessed data
- Prefetch related pages on hover

### 5.3 Bundle Size
- Code splitting per admin section
- Lazy load admin routes
- Optimize images and assets
- Tree-shake unused dependencies

## 6. Security Requirements

### 6.1 Authorization
- Maintain admin.access middleware
- Check super admin role for sensitive operations
- Validate permissions on both frontend and backend
- Prevent unauthorized API access

### 6.2 Data Protection
- Mask sensitive data (passwords, SMTP credentials)
- Sanitize user inputs
- Prevent XSS attacks
- CSRF protection (Laravel default)

### 6.3 File Upload Security
- Validate file types (images only for logos)
- Limit file size (2MB max)
- Sanitize file names
- Store uploads outside public directory

## 7. Testing Requirements

### 7.1 Unit Tests
- Test React components with Vitest
- Test form validation logic
- Test utility functions
- Test TypeScript types

### 7.2 Integration Tests
- Test Inertia page rendering
- Test form submissions
- Test API endpoints
- Test file uploads

### 7.3 E2E Tests (Optional)
- Test critical user flows
- Test admin CRUD operations
- Test multi-step forms

## 8. Migration Strategy

### 8.1 Phased Approach
**Phase 1: Foundation (Week 1)**
- Set up admin page structure
- Create shared components
- Define TypeScript types
- Migrate Admin Dashboard

**Phase 2: Core Management (Week 2-3)**
- Migrate User Management
- Migrate Business Unit Management
- Migrate Department Management

**Phase 3: Configuration (Week 4)**
- Migrate PR Category Management
- Migrate Activity Type Management
- Migrate Sub-Activity Management

**Phase 4: Settings (Week 5)**
- Migrate Notification Settings
- Migrate SLA Settings
- Final testing and polish

### 8.2 Rollback Plan
- Keep Blade views as backup
- Feature flag for React admin panel
- Gradual rollout to users
- Monitor error rates and performance

## 9. Success Metrics

### 9.1 Technical Metrics
- Page load time reduced by 40%
- Bundle size < 500KB per admin section
- Zero console errors
- 90%+ TypeScript coverage

### 9.2 User Metrics
- Admin task completion time reduced by 30%
- User satisfaction score > 4.5/5
- Zero critical bugs in production
- Positive feedback from super admins

## 10. Dependencies

### 10.1 External Dependencies
- Existing Inertia.js setup
- Existing React component library
- Existing Tailwind CSS configuration
- Chart.js for dashboard charts

### 10.2 Internal Dependencies
- Admin controllers must support Inertia responses
- API endpoints for dynamic data
- Existing authorization middleware
- Existing validation rules

## 11. Risks and Mitigation

### 11.1 Technical Risks
**Risk:** Breaking existing admin functionality
**Mitigation:** Thorough testing, feature flags, gradual rollout

**Risk:** Performance degradation with large datasets
**Mitigation:** Implement pagination, lazy loading, data caching

**Risk:** TypeScript type mismatches
**Mitigation:** Generate types from Laravel models, strict type checking

### 11.2 User Risks
**Risk:** User confusion with new UI
**Mitigation:** Maintain familiar patterns, provide user guide

**Risk:** Training required for new interface
**Mitigation:** Keep UI intuitive, provide tooltips and help text

## 12. Documentation Requirements

### 12.1 Technical Documentation
- Component API documentation
- TypeScript type definitions
- Inertia page props documentation
- API endpoint documentation

### 12.2 User Documentation
- Admin panel user guide
- Feature comparison (old vs new)
- Troubleshooting guide
- FAQ section

## 13. Acceptance Criteria Summary

The admin panel React migration is complete when:
1. All 9 admin sections are migrated to React/Inertia
2. All acceptance criteria for each section are met
3. No regressions in existing functionality
4. Performance metrics are achieved
5. All tests pass (unit, integration)
6. Documentation is complete
7. Super admin approval is obtained
8. Production deployment is successful

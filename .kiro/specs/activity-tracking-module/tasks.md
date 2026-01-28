# Implementation Tasks

## Phase 1: Core Infrastructure

- [x] 1. Database & Models Setup







  - [x] 1.1 Create migration: `activity_types` table in `database/migrations/modules/activity/`


    - _Requirements: 5.1, 5.2_

  - [x] 1.2 Create migration: `sub_activities` table with FK to activity_types

    - _Requirements: 5.2_

  - [x] 1.3 Create migration: `employee_tasks` table with all fields and FKs

    - _Requirements: 1.1, 1.2, 1.3, 1.4_

  - [x] 1.4 Create migration: `task_participants` pivot table

    - _Requirements: 2.2, 2.3_

  - [x] 1.5 Create migration: `task_attachments` table

    - _Requirements: 6.1, 6.2_

  - [x] 1.6 Create model: `ActivityType` in `app/Models/Modules/Activity/`

    - _Requirements: 5.1_

  - [x] 1.7 Create model: `SubActivity` with belongsTo ActivityType

    - _Requirements: 5.2, 5.4_

  - [x] 1.8 Create model: `EmployeeTask` with relationships, scopes, and activity logging

    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.3, 3.1, 3.2, 3.3_

  - [x] 1.9 Create model: `TaskParticipant` (pivot model if needed)

    - _Requirements: 2.2_
  - [x] 1.10 Create model: `TaskAttachment`


    - _Requirements: 6.1, 6.2, 6.3_
  - [x] 1.11 Create `TaskService` in `app/Services/Modules/Activity/` with create, join, start, complete, cancel


    - _Requirements: 1.1, 1.3, 2.2, 3.1, 3.2, 3.3, 3.4_
  - [x] 1.12 Create seeder: `ActivityTypeSeeder` with default types & sub-activities


    - _Requirements: 5.1, 5.2_
  - [x] 1.13 Run migrations and seeders



## Phase 2: Task CRUD & Livewire Components

- [x] 2. Task List & Form






  - [x] 2.1 Create `TaskIndex` Livewire component with BU switcher pattern

    - _Requirements: 4.1, 4.4_

  - [x] 2.2 Create `task-index.blade.php` view with filters and task cards

    - _Requirements: 4.1, 4.4_

  - [x] 2.3 Create `TaskForm` Livewire component (create/edit) with dynamic sub-activity dropdown

    - _Requirements: 1.1, 1.2, 5.4_
  - [x] 2.4 Create `task-form.blade.php` view with Tailwind styling


    - _Requirements: 1.1_
  - [x] 2.5 Create `TaskDetail` Livewire component with participant list and actions


    - _Requirements: 2.1, 3.1, 3.2, 3.4_

  - [x] 2.6 Create `task-detail.blade.php` view

    - _Requirements: 2.1, 6.3_

- [x] 3. Department Tasks & Collaboration





  - [x] 3.1 Create `DepartmentTasks` Livewire component showing joinable tasks


    - _Requirements: 2.1, 4.1, 4.2_
  - [x] 3.2 Create `department-tasks.blade.php` view


    - _Requirements: 2.1_
  - [x] 3.3 Implement join task functionality in TaskService


    - _Requirements: 2.2, 2.3, 2.4_
  - [x] 3.4 Implement start/complete/cancel actions with shared status


    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_
  - [x] 3.5 Add Activity routes to `web.php`


    - _Requirements: All_

## Phase 3: Analytics Dashboards

- [x] 4. Personal & Department Analytics




  - [x] 4.1 Create `PersonalDashboard` Livewire component
    - _Requirements: 7.1, 7.2, 7.3_
  - [x] 4.2 Create `personal-dashboard.blade.php` with stats cards and charts
    - _Requirements: 7.1, 7.2, 7.3_
  - [x] 4.3 Create `DepartmentAnalytics` Livewire component
    - _Requirements: 8.1, 8.2, 8.3, 8.4_
  - [x] 4.4 Create `department-analytics.blade.php` with team workload view
    - _Requirements: 8.1, 8.2, 8.3, 8.4_

- [x] 5. Business Unit Analytics





  - [x] 5.1 Create `BusinessUnitAnalytics` Livewire component


    - _Requirements: 9.1, 9.2, 9.3, 9.4_

  - [x] 5.2 Create `business-unit-analytics.blade.php` with cross-department view

    - _Requirements: 9.1, 9.2, 9.3, 9.4_

  - [x] 5.3 Add analytics routes with permission middleware






    - _Requirements: 4.3, 4.4_

## Phase 4: Admin Management

- [x] 6. Activity Type & Sub-Activity Admin





  - [x] 6.1 Create `ActivityTypeController` in `app/Http/Controllers/Admin/`


    - _Requirements: 5.1, 5.3_
  - [x] 6.2 Create activity type admin views (index, create, edit)


    - _Requirements: 5.1, 5.3_
  - [x] 6.3 Create `SubActivityController` for CRUD


    - _Requirements: 5.2_
  - [x] 6.4 Create sub-activity admin views


    - _Requirements: 5.2_

  - [x] 6.5 Add admin routes for activity type management

    - _Requirements: 5.1, 5.2, 5.3_

- [x] 7. Attachments






  - [x] 7.1 Implement file attachment upload in TaskForm

    - _Requirements: 6.1, 6.2_

  - [x] 7.2 Create attachment display component with preview/download

    - _Requirements: 6.3_

## Phase 5: Integration & Polish

- [x] 8. System Integration





  - [x] 8.1 Add Activity menu to sidebar (`app/Livewire/Layout/Sidebar.php`)


    - _Requirements: All_
  - [x] 8.2 Add task summary widget to main dashboard


    - _Requirements: 7.1_
  - [x] 8.3 Setup permissions: `view-department-analytics`, `view-activity-tasks`


    - _Requirements: 4.3, 4.4_

  - [x] 8.4 Update `product.md` steering file with Activity module info

    - _Requirements: All_

- [x] 9. Checkpoint - Ensure all tests pass
  - All Activity module tests pass (10 tests, 12 assertions)
  - Fixed BackdatePermissionService to use correct relationship name (`businessUnits` instead of `userBusinessUnits`)
  - Fixed access_level query to use string values (`executive`, `general_manager`, `department_head`) instead of numeric comparison

## Phase 6: Backdate Restriction & Approval Flow

- [x] 10. Backdate Permission Database & Models
  - [x] 10.1 Create migration: `backdate_permissions` table in `database/migrations/modules/activity/`
    - Fields: id, user_id, department_id, business_unit_id, requested_date, reason, status (pending/approved/rejected/expired), approved_by, approved_at, rejected_by, rejected_at, rejection_reason, granted_until, created_at, updated_at
    - Indexes: user_id, department_id, status, granted_until
    - _Requirements: 11.2, 11.3, 12.2, 12.3, 13.1_
  - [x] 10.2 Create model: `BackdatePermission` in `app/Models/Modules/Activity/`
    - Relationships: belongsTo User (requester), belongsTo User (approver), belongsTo Department, belongsTo BusinessUnit
    - Scopes: active(), pending(), forUser(), forDepartment()
    - Helper methods: isActive(), isExpired(), canBackdateTo($date)
    - _Requirements: 11.2, 12.1, 13.2_
  - [x] 10.3 Create `BackdatePermissionService` in `app/Services/Modules/Activity/`
    - Methods: requestPermission(), approveRequest(), rejectRequest(), checkUserPermission(), expireOldPermissions()
    - _Requirements: 11.2, 11.3, 12.2, 12.5, 13.1, 13.3_
  - [x] 10.4 Run migration

- [x] 11. Backdate Validation in Task Creation
  - [x] 11.1 Update `TaskForm` component to add backdate validation logic
    - Check user's active backdate permission
    - Calculate allowed date range (default: yesterday to today)
    - If active permission exists, extend range to granted_until
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_
  - [x] 11.2 Update `task-form.blade.php` to disable dates in date picker
    - Use Alpine.js to disable dates older than allowed range
    - Show helper text: "You can backdate up to [date]" or "Request backdate access for older dates"
    - _Requirements: 10.1, 10.2_
  - [x] 11.3 Add server-side validation in `TaskService::create()`
    - Validate task_date against user's backdate permission
    - Return validation error if date is too old
    - _Requirements: 10.3, 13.4_
  - [x] 11.4 Add "Request Backdate Access" button in task form
    - Show button when user needs older dates
    - Link to backdate request page
    - _Requirements: 11.1, 14.1_

- [x] 12. Backdate Request Management (Employee View)
  - [x] 12.1 Create `BackdateRequests` Livewire component in `app/Livewire/Modules/Activity/`
    - Show user's own backdate requests with status
    - Show "Request Backdate Access" button (disabled if pending request exists)
    - Show active permission countdown timer if exists
    - Handle request submission
    - _Requirements: 11.1, 14.1, 14.2, 14.3, 14.4, 14.5_
  - [x] 12.2 Create `backdate-requests.blade.php` view
    - Request list table with status badges (pending: yellow, approved: green, rejected: red, expired: gray)
    - Request form modal (requested_date, reason)
    - Active permission alert with countdown
    - Request detail modal showing rejection_reason if rejected
    - _Requirements: 14.2, 14.3, 14.6_
  - [x] 12.3 Implement request submission in BackdateRequests component
    - Validate: no pending request, requested_date not in future
    - Call BackdatePermissionService::requestPermission()
    - Notify department head
    - _Requirements: 11.2, 11.3, 11.4, 11.5_
  - [x] 12.4 Add route: `/activity/backdate-requests`
    - _Requirements: 14.1_

- [x] 13. Backdate Approval Management (Department Head View)
  - [x] 13.1 Create `BackdateApprovals` Livewire component in `app/Livewire/Modules/Activity/`
    - Show pending requests from department
    - Show history of approved/rejected requests with filters
    - Handle approve/reject actions
    - _Requirements: 12.1, 12.6, 15.1, 15.2, 15.6_
  - [x] 13.2 Create `backdate-approvals.blade.php` view
    - Pending requests table with requester info
    - Action buttons: Approve, Reject
    - Reject modal with rejection_reason textarea
    - History table with status filters
    - _Requirements: 15.2, 15.3, 15.4, 15.5, 15.6_
  - [x] 13.3 Implement approve action
    - Call BackdatePermissionService::approveRequest()
    - Set granted_until = end of today (23:59:59)
    - Expire previous active permission for same user
    - Notify requester
    - _Requirements: 12.2, 12.3, 12.4, 13.3_
  - [x] 13.4 Implement reject action
    - Require rejection_reason
    - Call BackdatePermissionService::rejectRequest()
    - Notify requester with reason
    - _Requirements: 12.5_
  - [x] 13.5 Add route: `/activity/backdate-approvals` with department head middleware
    - _Requirements: 15.1_

- [x] 14. Backdate Permission Lifecycle & Automation
  - [x] 14.1 Create scheduled command: `ExpireBackdatePermissions`
    - Run hourly to check and expire permissions where granted_until < now
    - Update status to 'expired'
    - _Requirements: 13.1_
  - [x] 14.2 Register command in `app/Console/Kernel.php`
    - Schedule to run every hour
    - _Requirements: 13.1_
  - [x] 14.3 Add permission check helper in `EmployeeTask` model
    - Method: canBackdateTo($date) - checks user's active permission
    - _Requirements: 13.2, 13.4, 13.5_

- [x] 15. Notifications
  - [x] 15.1 Create notification: `BackdateRequestSubmitted` for department heads
    - _Requirements: 11.3_
  - [x] 15.2 Create notification: `BackdateRequestApproved` for requester
    - _Requirements: 12.4_
  - [x] 15.3 Create notification: `BackdateRequestRejected` for requester
    - _Requirements: 12.5_

- [x] 16. Integration & UI Updates
  - [x] 16.1 Add backdate menu items to Activity sidebar section
    - "My Backdate Requests" for all users
    - "Backdate Approvals" for department heads only
    - _Requirements: 14.1, 15.1_
  - [x] 16.2 Add backdate permission indicator in task form
    - Show active permission status with countdown
    - Show "Request Access" button if needed
    - _Requirements: 10.4, 14.4_
  - [x] 16.3 Update `product.md` with backdate feature documentation
    - _Requirements: All backdate requirements_

- [x] 17. Checkpoint - Test backdate flow end-to-end
  - Test employee request submission
  - Test department head approval/rejection
  - Test permission expiration
  - Test backdate validation in task creation
  - Ensure all tests pass, ask the user if questions arise.

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

- [ ] 9. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

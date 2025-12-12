
# Implementation Plan

## Folder Structure Convention

**IMPORTANT**: All Admin-related files must follow the modular structure pattern:

```
app/
├── Models/Modules/Purchasing/
│   ├── PurchaseRequest/
│   ├── StockRequest/
│   └── Admin/              ✅ Admin feature folder
│       ├── AdminTask.php
│       └── SlaSettings.php
│
├── Services/Modules/Purchasing/
│   ├── PurchaseRequest/
│   ├── StockRequest/
│   └── Admin/              ✅ Admin feature folder
│       ├── AdminTaskService.php
│       ├── AdminTaskAssignmentService.php
│       ├── PriceEfficiencyService.php
│       └── SlaMonitoringService.php
│
├── Livewire/Modules/Purchasing/
│   ├── PurchaseRequest/
│   ├── StockRequest/
│   └── Admin/              ✅ Admin feature folder
│       ├── AdminDashboard.php
│       ├── TaskList.php
│       └── TaskDetail.php
│
└── Notifications/Purchasing/
    ├── PurchaseRequest/
    ├── StockRequest/
    └── Admin/              ✅ Admin feature folder
        ├── TaskAssigned.php
        └── SlaExceeded.php
```

**Namespace Convention**:
- Models: `App\Models\Modules\Purchasing\Admin\*`
- Services: `App\Services\Modules\Purchasing\Admin\*`
- Livewire: `App\Livewire\Modules\Purchasing\Admin\*`
- Notifications: `App\Notifications\Purchasing\Admin\*`

---

- [x] 1. Database migrations and model setup


  - Create admin_tasks table with polymorphic relationship to PR/ST
  - Extend departments table with purchasing flags
  - Extend user_business_units table with is_purchasing_admin flag
  - Create sla_settings table
  - Add indexes for performance
  - _Requirements: 1.1, 1.2, 2.1, 3.1-3.7, 10.1, 10.2_

- [ ]* 1.1 Write property test for department flag persistence
  - **Property 1: Department flag persistence**
  - **Validates: Requirements 1.1**

- [ ]* 1.2 Write property test for default admin persistence
  - **Property 2: Default admin persistence**
  - **Validates: Requirements 1.2**

- [ ]* 1.3 Write property test for admin flag persistence
  - **Property 5: Admin flag persistence**
  - **Validates: Requirements 2.1**

- [x] 2. Extend core models
  - Update Department model with purchasing relationships
  - Update UserBusinessUnit model with is_purchasing_admin cast
  - Create AdminTask model at `app/Models/Modules/Purchasing/Admin/AdminTask.php`
  - Create SlaSettings model at `app/Models/Modules/Purchasing/Admin/SlaSettings.php`
  - Add scopes for filtering (pending, inProgress, completed, forBusinessUnit)
  - Add activity logging to AdminTask
  - _Requirements: 1.1-1.5, 2.1-2.5_

- [ ]* 2.1 Write property test for estimated price copying
  - **Property 10: Estimated price copying**
  - **Validates: Requirements 3.4**

- [ ]* 2.2 Write property test for initial savings null state
  - **Property 12: Initial savings null state**
  - **Validates: Requirements 3.7**

- [x] 3. Create service layer
  - Implement AdminTaskService at `app/Services/Modules/Purchasing/Admin/AdminTaskService.php`
  - Implement AdminTaskAssignmentService at `app/Services/Modules/Purchasing/Admin/AdminTaskAssignmentService.php`
  - Implement PriceEfficiencyService at `app/Services/Modules/Purchasing/Admin/PriceEfficiencyService.php`
  - Implement SlaMonitoringService at `app/Services/Modules/Purchasing/Admin/SlaMonitoringService.php`
  - All services use namespace `App\Services\Modules\Purchasing\Admin`
  - _Requirements: 3.1-3.7, 5.1-5.8, 10.3-10.7_

- [ ]* 3.1 Write property test for follow-up time calculation
  - **Property 17: Follow-up time calculation**
  - **Validates: Requirements 5.2**

- [ ]* 3.2 Write property test for completion time calculation
  - **Property 20: Completion time calculation**
  - **Validates: Requirements 5.5**

- [ ]* 3.3 Write property test for savings amount calculation
  - **Property 21: Savings amount calculation**
  - **Validates: Requirements 5.6**

- [ ]* 3.4 Write property test for savings percentage calculation
  - **Property 22: Savings percentage calculation**
  - **Validates: Requirements 5.7**

- [x] 4. Implement event listeners for task creation




  - Create PurchaseRequestApproved event listener
  - Create StockRequestApproved event listener
  - Trigger AdminTaskService to create tasks on approval
  - Handle auto-assignment vs unassigned based on admin count
  - _Requirements: 3.1, 3.2, 3.5, 3.6_

- [ ]* 4.1 Write property test for task creation on PR approval
  - **Property 7: Task creation on PR approval**
  - **Validates: Requirements 3.1**

- [ ]* 4.2 Write property test for task creation on ST approval
  - **Property 8: Task creation on ST approval**
  - **Validates: Requirements 3.2**

- [ ]* 4.3 Write property test for auto-assignment logic
  - **Property 4: Auto-assignment uses default admin**
  - **Validates: Requirements 3.5**

- [ ]* 4.4 Write property test for multiple admin unassigned state
  - **Property 11: Multiple admin unassigned state**
  - **Validates: Requirements 3.6**

- [x] 5. Create access control gates and middleware





  - Define access-purchasing-admin Gate in AppServiceProvider
  - Check super admin, parent BU top management, and purchasing admin status
  - Create middleware for purchasing admin routes
  - Implement BU-switching access update logic
  - _Requirements: 11.1-11.7_

- [ ]* 5.1 Write property test for access control logic
  - **Property 3: Access control based on department and admin flag**
  - **Validates: Requirements 1.3, 2.2, 2.3, 11.1, 11.2**

- [ ]* 5.2 Write property test for multi-BU admin access
  - **Property 6: Multi-BU admin access**
  - **Validates: Requirements 2.5**

- [ ]* 5.3 Write property test for BU switch access update
  - **Property 45: BU switch access update**
  - **Validates: Requirements 11.5**

- [x] 6. Checkpoint - Ensure all tests pass





  - Ensure all tests pass, ask the user if questions arise.

- [x] 7. Build admin dashboard Livewire component




  - Create AdminDashboard component at `app/Livewire/Modules/Purchasing/Admin/AdminDashboard.php`
  - View at `resources/views/livewire/modules/purchasing/admin/admin-dashboard.blade.php`
  - Implement lazy loading with HasLazyLoading trait
  - Display task counts by status (pending, in_progress, done)
  - Add quick action buttons (claim task, start task)
  - Use REM-based Tailwind classes for responsive design
  - _Requirements: 6.1, 7.1_

- [ ]* 7.1 Write property test for task grouping by status
  - **Property 24: Task grouping by status**
  - **Validates: Requirements 6.1**

- [x] 8. Build task list Livewire component




  - Create TaskList component at `app/Livewire/Modules/Purchasing/Admin/TaskList.php`
  - View at `resources/views/livewire/modules/purchasing/admin/task-list.blade.php`
  - Implement pending, in-progress, completed tabs
  - Add date range and type filters
  - Display task cards with status badges
  - Handle business-unit-switched event
  - Use responsive grid layout with REM units
  - _Requirements: 6.2-6.6_

- [ ]* 8.1 Write property test for pending task filtering
  - **Property 25: Pending task filtering**
  - **Validates: Requirements 6.2**

- [ ]* 8.2 Write property test for in-progress task filtering
  - **Property 26: In-progress task filtering**
  - **Validates: Requirements 6.3**

- [ ]* 8.3 Write property test for completed task filtering
  - **Property 27: Completed task filtering**
  - **Validates: Requirements 6.4**

- [ ]* 8.4 Write property test for date range filtering
  - **Property 28: Date range filtering**
  - **Validates: Requirements 6.5**

- [ ]* 8.5 Write property test for type filtering
  - **Property 29: Type filtering**
  - **Validates: Requirements 6.6**

- [x] 9. Build task detail Livewire component





  - Create TaskDetail component at `app/Livewire/Modules/Purchasing/Admin/TaskDetail.php`
  - View at `resources/views/livewire/modules/purchasing/admin/task-detail.blade.php`
  - Implement claim task action with exclusivity check
  - Implement start task action with status transition
  - Implement complete task action with realized price input
  - Add validation for status transitions
  - Display task timeline and history
  - Use toast notifications for user feedback
  - _Requirements: 4.2, 4.3, 5.1, 5.3, 5.4_

- [ ]* 9.1 Write property test for task claiming assignment
  - **Property 14: Task claiming assignment**
  - **Validates: Requirements 4.2**

- [ ]* 9.2 Write property test for task claiming exclusivity
  - **Property 15: Task claiming exclusivity**
  - **Validates: Requirements 4.3**

- [ ]* 9.3 Write property test for start task status and timestamp
  - **Property 16: Start task status and timestamp**
  - **Validates: Requirements 5.1**

- [ ]* 9.4 Write property test for completion requires realized price
  - **Property 18: Completion requires realized price**
  - **Validates: Requirements 5.3**

- [ ]* 9.5 Write property test for complete task status and timestamp
  - **Property 19: Complete task status and timestamp**
  - **Validates: Requirements 5.4**

- [ ]* 9.6 Write property test for PR/ST status preservation
  - **Property 23: PR/ST status preservation**
  - **Validates: Requirements 5.8**

- [x] 10. Build performance metrics Livewire component




  - Create PerformanceMetrics component at `app/Livewire/Modules/Purchasing/Admin/PerformanceMetrics.php`
  - View at `resources/views/livewire/modules/purchasing/admin/performance-metrics.blade.php`
  - Display total tasks completed, average follow-up time, average completion time
  - Display total savings amount and average savings percentage
  - Implement Chart.js integration for trend charts (savings over time, tasks per month)
  - Use responsive card layout with REM-based sizing
  - Handle business-unit-switched event
  - _Requirements: 7.1-7.7_

- [ ]* 10.1 Write property test for admin completed task count
  - **Property 30: Admin completed task count**
  - **Validates: Requirements 7.1**

- [ ]* 10.2 Write property test for admin average follow-up time
  - **Property 31: Admin average follow-up time**
  - **Validates: Requirements 7.2**

- [ ]* 10.3 Write property test for admin average completion time
  - **Property 32: Admin average completion time**
  - **Validates: Requirements 7.3**

- [ ]* 10.4 Write property test for admin total savings
  - **Property 33: Admin total savings**
  - **Validates: Requirements 7.4**

- [ ]* 10.5 Write property test for admin average savings percentage
  - **Property 34: Admin average savings percentage**
  - **Validates: Requirements 7.5**

- [x] 11. Build department report Livewire component




  - Create DepartmentReport component at `app/Livewire/Modules/Purchasing/Admin/DepartmentReport.php`
  - View at `resources/views/livewire/modules/purchasing/admin/department-report.blade.php`
  - Display aggregated metrics for all admins in department
  - Show total savings, average follow-up/completion times
  - Display savings breakdown by PR and ST categories
  - Show performance comparison between admins
  - Implement trend charts for department performance
  - Use responsive table and chart layouts
  - _Requirements: 8.1-8.7_

- [ ]* 11.1 Write property test for department total savings
  - **Property 35: Department total savings**
  - **Validates: Requirements 8.2**

- [ ]* 11.2 Write property test for department average follow-up time
  - **Property 36: Department average follow-up time**
  - **Validates: Requirements 8.3**

- [ ]* 11.3 Write property test for department average completion time
  - **Property 37: Department average completion time**
  - **Validates: Requirements 8.4**

- [x] 12. Build consolidated report Livewire component





  - Create ConsolidatedReport component at `app/Livewire/Modules/Purchasing/Admin/ConsolidatedReport.php`
  - View at `resources/views/livewire/modules/purchasing/admin/consolidated-report.blade.php`
  - Display savings metrics for all child business units
  - Show total savings amount, average savings percentage per BU
  - Display average follow-up/completion times per BU
  - Implement comparative trend charts across BUs
  - Show total tasks completed per BU
  - Use responsive grid and chart layouts
  - _Requirements: 9.1-9.7_

- [ ]* 12.1 Write property test for business unit total savings
  - **Property 38: Business unit total savings**
  - **Validates: Requirements 9.2**

- [ ]* 12.2 Write property test for business unit average savings percentage
  - **Property 39: Business unit average savings percentage**
  - **Validates: Requirements 9.3**

- [ ]* 12.3 Write property test for business unit task count
  - **Property 40: Business unit task count**
  - **Validates: Requirements 9.7**

- [x] 13. Implement SLA configuration UI




  - Create SLA settings page for super admin
  - Add form for configuring followup_sla_hours and completion_sla_hours
  - Add toggle for email_alerts_enabled
  - Implement save functionality with validation
  - Display current SLA settings
  - _Requirements: 10.1, 10.2, 10.3, 10.4_

- [ ]* 13.1 Write property test for SLA configuration persistence
  - **Property 41: SLA configuration persistence**
  - **Validates: Requirements 10.1, 10.2**

- [x] 14. Implement SLA monitoring and alerts





  - Create scheduled job to check for SLA violations
  - Implement email notification for follow-up SLA exceeded
  - Implement email notification for completion SLA exceeded
  - Add visual indicators in UI for tasks exceeding SLA
  - Send alerts to assigned admin and department manager
  - _Requirements: 10.5, 10.6, 10.7_

- [ ]* 14.1 Write property test for SLA alert conditional sending
  - **Property 42: SLA alert conditional sending**
  - **Validates: Requirements 10.3, 10.4**

- [x] 15. Create email notification templates




  - Create TaskAssigned notification at `app/Notifications/Purchasing/Admin/TaskAssigned.php`
  - Create SlaExceeded notification at `app/Notifications/Purchasing/Admin/SlaExceeded.php`
  - Email views at `resources/views/emails/purchasing/admin/`
  - Use existing email layout structure
  - Include task details and action links
  - _Requirements: 10.5, 10.6_

- [x] 16. Implement audit history views





  - Create audit history page for super admin (all BUs)
  - Create audit history page for department managers (scoped to department)
  - Create personal task history for purchasing admins
  - Display all timestamps, admin attribution, price data, time metrics
  - Use responsive table layout with filtering
  - _Requirements: 12.1-12.7_

- [ ]* 16.1 Write property test for super admin audit access
  - **Property 46: Super admin audit access**
  - **Validates: Requirements 12.1**

- [ ]* 16.2 Write property test for department manager scoped audit access
  - **Property 47: Department manager scoped audit access**
  - **Validates: Requirements 12.2**

- [ ]* 16.3 Write property test for admin personal history access
  - **Property 48: Admin personal history access**
  - **Validates: Requirements 12.3**

- [x] 17. Add navigation menu integration





  - Add "Purchasing Admin" menu item to sidebar
  - Implement access control using Gate
  - Show/hide menu based on user permissions
  - Update menu active state for purchasing admin routes
  - Use existing sidebar styling and structure
  - _Requirements: 11.1-11.7_

- [x] 18. Create routes for purchasing admin module




  - Define routes for dashboard, task list, task detail
  - Define routes for performance metrics, department report, consolidated report
  - Define routes for SLA settings, audit history
  - Apply purchasing admin middleware
  - Group routes under /purchasing/admin prefix
  - _Requirements: All_

- [x] 19. Implement department configuration UI for super admin





  - Create department settings page
  - Add toggle for is_purchasing_department flag
  - Add dropdown to select default purchasing admin
  - Display list of purchasing admins in department
  - Add ability to assign/remove purchasing admin flag from users
  - _Requirements: 1.1-1.5, 2.1-2.5_

- [x] 20. Final checkpoint - Ensure all tests pass





  - Ensure all tests pass, ask the user if questions arise.

- [ ] 21. Integration testing and bug fixes
  - Test complete workflow: PR approval → task creation → claim → start → complete
  - Test business unit switching with access control
  - Test multi-admin claiming race conditions
  - Test SLA monitoring and email alerts
  - Test responsive design on mobile, tablet, desktop
  - Fix any bugs discovered during testing
  - _Requirements: All_

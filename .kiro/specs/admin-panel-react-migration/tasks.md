# Implementation Plan: Admin Panel React Migration

## Overview

This implementation plan breaks down the migration of all admin panel pages from Blade/Livewire to React/Inertia.js into discrete, manageable tasks. The migration follows a phased approach, starting with foundational components and progressing through each admin section systematically.

## Tasks

- [x] 1. Set up frontend infrastructure and shared components
  - Create TypeScript type definitions for all admin entities (User, BusinessUnit, Department, etc.)
  - Set up Vitest testing framework with React Testing Library and fast-check
  - Create AdminLayout component with sidebar, breadcrumbs, and error boundary
  - Reuse existing DataTable component or create admin-specific wrapper using TanStack Table
  - Reuse existing UI components (Button, Input, Select, Dialog, Card, Badge, Skeleton)
  - Create StatCard component for dashboard metrics with Framer Motion animations
  - Create ChartCard component wrapper for Recharts (following Activity module pattern)
  - Create FileUpload component using native HTML5 or existing patterns
  - Create ColorPicker component for activity types
  - Configure Sonner toast notifications (already available)
  - _Requirements: 10.1, 10.2, 10.3, 12.5, 12.6, 15.1, 15.2, 15.4, 15.6_

- [ ]* 1.1 Write property test for DataTable component
  - **Property 50: Table Column Sorting**
  - **Validates: Requirements 15.2**

- [ ]* 1.2 Write property test for pagination
  - **Property 51: Table Pagination Navigation**
  - **Validates: Requirements 15.4**

- [ ]* 1.3 Write property test for table empty state
  - **Property 53: Table Empty State**
  - **Validates: Requirements 15.6**

- [x] 2. Migrate Admin Dashboard
  - [x] 2.1 Create Dashboard page component with statistics grid
    - Implement StatCard components for total users, business units, departments, and PRs with Framer Motion animations
    - Add recent users table with pagination
    - Add business unit breakdown cards with hover effects
    - Add monthly PR trends chart using Recharts (LineChart or BarChart)
    - Add quick action navigation cards
    - Follow Activity module animation patterns (AnimatePresence, smooth transitions)
    - Use Lucide React icons consistently
    - _Requirements: 1.1, 1.3, 1.4, 1.5, 1.6_

  - [ ]* 2.2 Write property test for dashboard statistics display
    - **Property 1: Dashboard Statistics Display**
    - **Validates: Requirements 1.1**

  - [ ]* 2.3 Write property test for loading states
    - **Property 2: Loading State Consistency**
    - **Validates: Requirements 1.2, 11.1**

  - [ ]* 2.4 Write property test for chart rendering
    - **Property 5: Chart Rendering**
    - **Validates: Requirements 1.5**

  - [x] 2.5 Update AdminController to return Inertia response
    - Modify index method to return Inertia::render('Admin/Dashboard', $data)
    - Format statistics data as JSON-serializable arrays
    - Maintain existing authorization middleware
    - _Requirements: 19.1, 19.2, 19.4_

- [x] 3. Checkpoint - Verify dashboard migration
  - Ensure all tests pass, verify dashboard loads correctly, ask the user if questions arise.

- [x] 4. Migrate User Management pages
  - [x] 4.1 Create User Index page with filters and search
    - Implement user table with DataTable component
    - Add search input with 300ms debouncing
    - Add filters for business unit, department, and role
    - Add pagination controls
    - Add quick actions (edit, view, deactivate)
    - _Requirements: 2.1, 2.2, 2.3_

  - [ ]* 4.2 Write property test for user table search and filters
    - **Property 6: User Table with Search and Filters**
    - **Validates: Requirements 2.1, 2.3**

  - [ ]* 4.3 Write property test for search debouncing
    - **Property 7: Search Input Debouncing**
    - **Validates: Requirements 2.2**

  - [x] 4.4 Create User Create/Edit form pages
    - Implement user form with React Hook Form and Zod validation
    - Add multi-business unit assignment with dynamic department/position loading
    - Add password fields (required for create, optional for edit)
    - Add real-time validation with inline error messages
    - Implement AJAX endpoints for departments and positions
    - _Requirements: 2.4, 2.5, 2.6, 2.7_

  - [ ]* 4.5 Write property test for dynamic department/position loading
    - **Property 8: Dynamic Department/Position Loading**
    - **Validates: Requirements 2.5**

  - [ ]* 4.6 Write property test for form validation
    - **Property 9: Form Validation with Real-time Feedback**
    - **Validates: Requirements 2.6, 11.4**

  - [ ]* 4.7 Write property test for form pre-population
    - **Property 10: Form Pre-population**
    - **Validates: Requirements 2.7**

  - [x] 4.8 Create User Show page
    - Display user details with all relationships
    - Show business unit assignments table
    - Show roles and permissions
    - Add edit and deactivate buttons
    - _Requirements: 2.8_

  - [ ]* 4.9 Write property test for user relationships display
    - **Property 11: User Relationships Display**
    - **Validates: Requirements 2.8**

  - [x] 4.10 Update UserManagementController for Inertia
    - Modify all methods to return Inertia responses
    - Create AJAX endpoints for departments and positions
    - Maintain existing validation rules
    - _Requirements: 19.1, 19.2, 19.3, 19.5_

- [x] 5. Checkpoint - Verify user management migration
  - Ensure all tests pass, verify CRUD operations work correctly, ask the user if questions arise.

- [-] 6. Migrate Business Unit Management pages
  - [x] 6.1 Create Business Unit Index page
    - Implement business unit list with search and status filter
    - Add logo preview in table/grid
    - Show hierarchical parent-child relationships
    - Add quick actions (edit, view, toggle status, delete)
    - _Requirements: 3.1, 3.8_

  - [ ]* 6.2 Write property test for business unit list with filters
    - **Property 12: Business Unit List with Filters**
    - **Validates: Requirements 3.1**

  - [ ]* 6.3 Write property test for hierarchical display
    - **Property 16: Hierarchical Relationship Display**
    - **Validates: Requirements 3.8**

  - [x] 6.4 Create Business Unit Create/Edit form pages
    - Implement business unit form with logo upload using React Dropzone
    - Add file validation (image types, 2MB max)
    - Add image preview before upload
    - Add logo removal option for edit page
    - Add parent business unit selection
    - _Requirements: 3.2, 3.3, 3.4_

  - [ ]* 6.5 Write property test for file upload validation
    - **Property 13: File Upload Validation**
    - **Validates: Requirements 3.3**

  - [ ]* 6.6 Write property test for file preview
    - **Property 57: File Preview Display**
    - **Validates: Requirements 17.2**

  - [x] 6.7 Create Business Unit Show page
    - Display business unit details with statistics
    - Show department count and user count
    - Add edit, toggle status, and delete buttons
    - Implement deletion validation (prevent if users/departments assigned)
    - _Requirements: 3.5, 3.6, 3.7_

  - [ ]* 6.8 Write property test for business unit statistics
    - **Property 14: Business Unit Statistics Display**
    - **Validates: Requirements 3.5**

  - [ ]* 6.9 Write property test for deletion validation
    - **Property 15: Deletion Validation**
    - **Validates: Requirements 3.7**

  - [x] 6.10 Update BusinessUnitController for Inertia
    - Modify all methods to return Inertia responses
    - Implement file upload handling for logos
    - Add deletion validation logic
    - _Requirements: 19.1, 19.2, 19.3_

- [x] 7. Checkpoint - Verify business unit management migration
  - Ensure all tests pass, verify file uploads work, ask the user if questions arise.

- [x] 8. Migrate Department Management pages
  - [x] 8.1 Create Department Index page
    - Implement department list grouped by business unit
    - Add business unit filter
    - Add inline position management
    - Add quick actions (edit, view, configure purchasing)
    - _Requirements: 4.1_

  - [ ]* 8.2 Write property test for grouped department display
    - **Property 17: Grouped Department Display**
    - **Validates: Requirements 4.1**

  - [x] 8.3 Create Department Create/Edit form pages
    - Implement department form with business unit selection
    - Add dynamic position management (add/remove positions inline)
    - Add purchasing configuration toggle
    - _Requirements: 4.2, 4.3, 4.5, 4.6_

  - [ ]* 8.4 Write property test for position management
    - **Property 19: Position Management Operations**
    - **Validates: Requirements 4.6**

  - [x] 8.5 Create Department Show page
    - Display department details with user assignments
    - Show all users assigned to department
    - Add edit button
    - _Requirements: 4.4_

  - [ ]* 8.6 Write property test for user assignments display
    - **Property 18: User Assignments Display**
    - **Validates: Requirements 4.4**

  - [x] 8.7 Update DepartmentController for Inertia
    - Modify all methods to return Inertia responses
    - Maintain existing validation rules
    - _Requirements: 19.1, 19.2, 19.3_

- [x] 9. Migrate PR Category Management pages
  - [x] 9.1 Create PR Category Index page with inline forms
    - Implement category list with search
    - Add inline create/edit forms
    - Show usage statistics (number of PRs using category)
    - Add delete with validation (prevent if in use)
    - _Requirements: 5.1, 5.5_

  - [ ]* 9.2 Write property test for category list with search
    - **Property 20: Category List with Search**
    - **Validates: Requirements 5.1**

  - [ ]* 9.3 Write property test for category uniqueness validation
    - **Property 21: Category Name Uniqueness Validation**
    - **Validates: Requirements 5.2**

  - [ ]* 9.4 Write property test for category deletion validation
    - **Property 23: Category Deletion Validation**
    - **Validates: Requirements 5.4**

  - [ ]* 9.5 Write property test for usage statistics
    - **Property 24: Category Usage Statistics Display**
    - **Validates: Requirements 5.5**

  - [x] 9.6 Update PrCategoryController for Inertia
    - Modify all methods to return Inertia responses
    - Add usage count to category data
    - Implement deletion validation
    - _Requirements: 19.1, 19.2, 19.3_

- [x] 10. Migrate Activity Type Management pages
  - [x] 10.1 Create Activity Type Index page
    - Implement activity type list with color indicators
    - Add inline create/edit forms with color picker
    - Show usage statistics (number of tasks using type)
    - Add delete with validation (prevent if has sub-activities)
    - _Requirements: 6.1, 6.2, 6.3, 6.5_

  - [ ]* 10.2 Write property test for activity type color display
    - **Property 25: Activity Type Color Display**
    - **Validates: Requirements 6.1**

  - [ ]* 10.3 Write property test for activity type deletion validation
    - **Property 26: Activity Type Deletion Validation**
    - **Validates: Requirements 6.4**

  - [ ]* 10.4 Write property test for usage statistics
    - **Property 27: Activity Type Usage Statistics Display**
    - **Validates: Requirements 6.5**

  - [x] 10.5 Update ActivityTypeController for Inertia
    - Modify all methods to return Inertia responses
    - Add usage count and sub-activity count to data
    - Implement deletion validation
    - _Requirements: 19.1, 19.2, 19.3_

- [x] 11. Migrate Sub-Activity Management pages
  - [x] 11.1 Create Sub-Activity Index page
    - Implement sub-activity list grouped by activity type
    - Add activity type filter
    - Add inline create/edit forms
    - Show usage statistics (number of tasks using sub-activity)
    - Add delete with validation (prevent if in use)
    - _Requirements: 7.1, 7.2, 7.5_

  - [ ]* 11.2 Write property test for grouped sub-activity display
    - **Property 28: Grouped Sub-Activity Display**
    - **Validates: Requirements 7.1**

  - [ ]* 11.3 Write property test for sub-activity uniqueness validation
    - **Property 29: Sub-Activity Name Uniqueness Validation**
    - **Validates: Requirements 7.3**

  - [ ]* 11.4 Write property test for sub-activity deletion validation
    - **Property 30: Sub-Activity Deletion Validation**
    - **Validates: Requirements 7.4**

  - [ ]* 11.5 Write property test for usage statistics
    - **Property 31: Sub-Activity Usage Statistics Display**
    - **Validates: Requirements 7.5**

  - [x] 11.6 Update SubActivityController for Inertia
    - Modify all methods to return Inertia responses
    - Add usage count to data
    - Implement deletion validation
    - _Requirements: 19.1, 19.2, 19.3_

- [x] 12. Checkpoint - Verify category and activity management migrations
  - Ensure all tests pass, verify inline forms work correctly, ask the user if questions arise.

- [x] 13. Migrate Notification Settings page
  - [x] 13.1 Create Notification Settings page
    - Implement SMTP configuration form with React Hook Form
    - Add password masking for sensitive fields
    - Add test email button with loading state
    - Add email statistics dashboard
    - Implement validation before saving
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6_

  - [ ]* 13.2 Write property test for password masking
    - **Property 32: Password Masking**
    - **Validates: Requirements 8.2**

  - [ ]* 13.3 Write property test for SMTP validation
    - **Property 33: SMTP Settings Validation**
    - **Validates: Requirements 8.3**

  - [ ]* 13.4 Write property test for SMTP error messages
    - **Property 34: SMTP Error Messages**
    - **Validates: Requirements 8.6**

  - [x] 13.5 Update notification settings controller for Inertia
    - Modify methods to return Inertia responses
    - Implement test email functionality
    - Add email statistics calculation
    - _Requirements: 19.1, 19.2, 19.3_

- [x] 14. Migrate SLA Settings page
  - [x] 14.1 Create SLA Settings page
    - Implement SLA configuration form for all business units
    - Add time range validation (1-720 hours)
    - Add relational validation (follow-up < completion)
    - Add email alerts toggle with confirmation
    - Add compliance statistics display
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

  - [ ]* 14.2 Write property test for SLA settings display
    - **Property 35: SLA Settings for All Business Units**
    - **Validates: Requirements 9.1**

  - [ ]* 14.3 Write property test for time range validation
    - **Property 36: SLA Time Range Validation**
    - **Validates: Requirements 9.2**

  - [ ]* 14.4 Write property test for relational validation
    - **Property 37: SLA Relational Validation**
    - **Validates: Requirements 9.3**

  - [ ]* 14.5 Write property test for compliance statistics
    - **Property 38: SLA Compliance Statistics Display**
    - **Validates: Requirements 9.5**

  - [x] 14.6 Update SLA settings controller for Inertia
    - Modify methods to return Inertia responses
    - Add compliance statistics calculation
    - _Requirements: 19.1, 19.2, 19.3_

- [x] 15. Implement comprehensive error handling
  - [x] 15.1 Add error boundaries to all admin pages
    - Wrap pages with ErrorBoundary component
    - Implement fallback UI for React errors
    - Add error logging to backend
    - _Requirements: 11.6_

  - [ ]* 15.2 Write property test for error boundary
    - **Property 45: Error Boundary Fallback**
    - **Validates: Requirements 11.6**

  - [x] 15.3 Implement toast notification system
    - Use existing Sonner toast system (already configured in Activity module)
    - Add success toast for form submissions using toast.success()
    - Add error toast for server errors using toast.error()
    - Add loading toast for async operations using toast.loading()
    - Ensure Toaster component is included in admin layout
    - _Requirements: 11.5, 16.4_

  - [ ]* 15.4 Write property test for toast notifications
    - **Property 44: Server Error Toast Notifications**
    - **Validates: Requirements 11.5**

  - [ ]* 15.5 Write property test for success notifications
    - **Property 54: Success Toast Notification**
    - **Validates: Requirements 16.4**

  - [x] 15.4 Add form submission states
    - Disable submit buttons during submission
    - Show spinner during submission
    - Handle optimistic updates with rollback
    - _Requirements: 11.2, 11.7_

  - [ ]* 15.6 Write property test for form submission state
    - **Property 42: Form Submission State**
    - **Validates: Requirements 11.2**

  - [ ]* 15.7 Write property test for optimistic updates
    - **Property 46: Optimistic UI Updates**
    - **Validates: Requirements 11.7**

- [x] 16. Implement accessibility features
  - [x] 16.1 Add ARIA labels to all interactive elements
    - Add aria-label to buttons without text
    - Add aria-describedby for tooltips
    - Add role attributes where needed
    - _Requirements: 12.5_

  - [ ]* 16.2 Write property test for ARIA labels
    - **Property 47: ARIA Labels**
    - **Validates: Requirements 12.5**

  - [x] 16.3 Implement modal focus management
    - Trap focus within modals
    - Return focus to trigger element on close
    - Support Escape key to close
    - _Requirements: 12.6_

  - [ ]* 16.4 Write property test for modal focus management
    - **Property 48: Modal Focus Management**
    - **Validates: Requirements 12.6**

- [x] 17. Checkpoint - Verify settings pages and error handling
  - Ensure all tests pass, verify error handling works correctly, ask the user if questions arise.

- [x] 18. Performance optimization and testing
  - [x] 18.1 Implement code splitting for admin routes
    - Lazy load all admin page components
    - Configure Vite for optimal chunking
    - Add loading fallbacks for lazy components
    - _Requirements: 13.5, 13.6_

  - [x] 18.2 Implement pagination rendering optimization
    - Ensure only current page items are rendered
    - Add virtualization for very large tables (if needed)
    - _Requirements: 13.4_

  - [ ]* 18.3 Write property test for pagination rendering
    - **Property 49: Pagination Rendering**
    - **Validates: Requirements 13.4**

  - [x] 18.3 Add file upload progress indicators
    - Implement progress bar for file uploads
    - Show upload percentage
    - Handle upload errors with specific messages
    - _Requirements: 11.3, 17.7_

  - [ ]* 18.4 Write property test for file upload progress
    - **Property 43: File Upload Progress**
    - **Validates: Requirements 11.3**

  - [ ]* 18.5 Write property test for file upload errors
    - **Property 58: File Upload Error Messages**
    - **Validates: Requirements 17.7**

- [ ] 19. Integration testing and cleanup
  - [ ] 19.1 Run full test suite
    - Execute all unit tests
    - Execute all property tests (minimum 100 iterations each)
    - Verify 80%+ code coverage
    - Fix any failing tests
    - _Requirements: All_

  - [ ] 19.2 Update navigation and routes
    - Verify all admin routes point to Inertia pages
    - Update sidebar navigation links
    - Update breadcrumb generation
    - Test navigation flow between pages
    - _Requirements: 10.1, 10.2, 10.3_

  - [ ]* 19.3 Write property test for sidebar presence
    - **Property 39: Sidebar Presence**
    - **Validates: Requirements 10.1**

  - [ ]* 19.4 Write property test for active menu highlighting
    - **Property 40: Active Menu Highlighting**
    - **Validates: Requirements 10.2**

  - [ ]* 19.5 Write property test for breadcrumb navigation
    - **Property 41: Breadcrumb Navigation**
    - **Validates: Requirements 10.3**

  - [ ] 19.3 Remove old Blade views
    - Delete all admin Blade views from resources/views/admin
    - Remove Livewire admin components
    - Clean up unused routes
    - Update documentation
    - _Requirements: All_

- [ ] 20. Final checkpoint - Production readiness
  - Ensure all tests pass, verify all admin pages work correctly, perform user acceptance testing, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional property-based tests and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation throughout the migration
- Property tests validate universal correctness properties with minimum 100 iterations
- Unit tests validate specific examples and edge cases
- All admin pages maintain existing authorization (admin.access middleware)
- Migration follows phased approach: foundation → dashboard → management pages → settings

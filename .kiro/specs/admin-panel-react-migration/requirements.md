# Requirements Document: Admin Panel React Migration

## Introduction

This document specifies the requirements for migrating all admin panel pages from Blade/Livewire to React/Inertia.js to achieve a unified technology stack, consistent user experience, and improved performance across the Oasis application.

## Glossary

- **Admin_Panel**: The administrative interface for managing system configuration, users, business units, departments, and module settings
- **Inertia.js**: A framework that allows building single-page applications using classic server-side routing and controllers
- **React_Component**: A reusable UI component built with React and TypeScript
- **Blade_View**: Laravel's templating engine for server-side rendered views
- **Livewire_Component**: Laravel's full-stack framework for building dynamic interfaces
- **SPA**: Single Page Application - web application that loads a single HTML page and dynamically updates content
- **CRUD**: Create, Read, Update, Delete operations
- **Business_Unit**: An organizational entity within the Werkudara Group (WG, WNS, UK, MRP)
- **Super_Admin**: User role with full system access and administrative privileges
- **SMTP**: Simple Mail Transfer Protocol for email configuration
- **SLA**: Service Level Agreement - time-based performance targets
- **PR_Category**: Purchase Request category for organizing procurement items
- **Activity_Type**: Classification for employee tasks in the Activity Tracking module
- **Sub_Activity**: Detailed categorization under an Activity Type

## Requirements

### Requirement 1: Admin Dashboard Migration

**User Story:** As a super admin, I want to view system statistics and insights on a React-based dashboard, so that I can monitor the system with improved performance and consistent UX.

#### Acceptance Criteria

1. WHEN the admin dashboard loads, THE System SHALL display real-time statistics including total users, business units, departments, and purchase requests
2. WHEN statistics are loading, THE System SHALL show skeleton loaders to indicate loading state
3. WHEN the dashboard renders, THE System SHALL display a recent users table with pagination support
4. WHEN the dashboard renders, THE System SHALL show business unit breakdown cards with user counts per unit
5. WHEN the dashboard renders, THE System SHALL display a monthly PR trends chart using Chart.js
6. WHEN the dashboard renders, THE System SHALL provide quick action cards for navigating to admin sections
7. WHEN data is refreshed, THE System SHALL update statistics without full page reload using Inertia partial reloads
8. WHEN navigation occurs within admin sections, THE System SHALL use client-side routing for instant transitions

### Requirement 2: User Management Migration

**User Story:** As a super admin, I want to manage users through a React interface, so that I can perform CRUD operations with better form validation and user experience.

#### Acceptance Criteria

1. WHEN the user list page loads, THE System SHALL display a paginated table of users with real-time search functionality
2. WHEN a search query is entered, THE System SHALL debounce input by 300ms before filtering results
3. WHEN filters are applied, THE System SHALL filter users by business unit, department, and role without page reload
4. WHEN the create user form is opened, THE System SHALL display a multi-business unit assignment form with validation
5. WHEN departments are selected, THE System SHALL dynamically load available positions via AJAX
6. WHEN the user form is submitted, THE System SHALL validate all inputs with real-time feedback before submission
7. WHEN the edit user form is opened, THE System SHALL pre-populate all fields including multi-business unit assignments
8. WHEN the user detail view is opened, THE System SHALL display all user relationships including business units, departments, and roles
9. WHEN a user is deactivated, THE System SHALL show a confirmation dialog before processing
10. WHEN form validation fails, THE System SHALL display inline error messages for each invalid field

### Requirement 3: Business Unit Management Migration

**User Story:** As a super admin, I want to manage business units through a React interface, so that I can configure organizational structure with improved file upload and validation.

#### Acceptance Criteria

1. WHEN the business unit list loads, THE System SHALL display all business units with search and status filter capabilities
2. WHEN the create business unit form is opened, THE System SHALL provide a logo upload field with image preview
3. WHEN a logo file is selected, THE System SHALL validate file type (images only) and size (max 2MB)
4. WHEN the edit business unit form is opened, THE System SHALL display current logo with removal option
5. WHEN the business unit detail view is opened, THE System SHALL show statistics including department count and user count
6. WHEN a business unit status is toggled, THE System SHALL show confirmation dialog before changing active/inactive state
7. WHEN a business unit deletion is attempted, THE System SHALL validate that no users or departments are assigned before allowing deletion
8. WHEN business units are displayed, THE System SHALL show hierarchical parent-child relationships (WG as parent, WNS/UK/MRP as children)

### Requirement 4: Department Management Migration

**User Story:** As a super admin, I want to manage departments through a React interface, so that I can organize business unit structures with better position management.

#### Acceptance Criteria

1. WHEN the department list loads, THE System SHALL display departments grouped by business unit
2. WHEN the create department form is opened, THE System SHALL provide business unit selection with validation
3. WHEN the edit department form is opened, THE System SHALL allow inline position management for that department
4. WHEN the department detail view is opened, THE System SHALL display all user assignments to that department
5. WHEN purchasing configuration is accessed, THE System SHALL allow configuring purchasing settings per department
6. WHEN department positions are managed, THE System SHALL allow adding, editing, and removing positions inline

### Requirement 5: PR Category Management Migration

**User Story:** As a super admin, I want to manage PR categories through a React interface, so that I can organize purchase request items with improved validation.

#### Acceptance Criteria

1. WHEN the PR category list loads, THE System SHALL display all categories with search functionality
2. WHEN the create category form is submitted, THE System SHALL validate category name uniqueness
3. WHEN the edit category form is submitted, THE System SHALL update the category with real-time validation
4. WHEN a category deletion is attempted, THE System SHALL validate that no purchase requests are using the category
5. WHEN categories are displayed, THE System SHALL show usage statistics indicating how many PRs use each category

### Requirement 6: Activity Type Management Migration

**User Story:** As a super admin, I want to manage activity types through a React interface, so that I can configure task categories with improved color selection.

#### Acceptance Criteria

1. WHEN the activity type list loads, THE System SHALL display all activity types with color indicators
2. WHEN the create activity type form is opened, THE System SHALL provide a color picker component for selecting type color
3. WHEN the edit activity type form is opened, THE System SHALL show current color with live preview
4. WHEN an activity type deletion is attempted, THE System SHALL validate that no sub-activities are assigned to the type
5. WHEN activity types are displayed, THE System SHALL show usage statistics indicating how many tasks use each type

### Requirement 7: Sub-Activity Management Migration

**User Story:** As a super admin, I want to manage sub-activities through a React interface, so that I can organize detailed task categorization with better grouping.

#### Acceptance Criteria

1. WHEN the sub-activity list loads, THE System SHALL display sub-activities grouped by their parent activity type
2. WHEN the create sub-activity form is opened, THE System SHALL provide activity type selection dropdown
3. WHEN the edit sub-activity form is submitted, THE System SHALL validate sub-activity name uniqueness within the activity type
4. WHEN a sub-activity deletion is attempted, THE System SHALL validate that no tasks are using the sub-activity
5. WHEN sub-activities are displayed, THE System SHALL show usage statistics indicating how many tasks use each sub-activity

### Requirement 8: Notification Settings Migration

**User Story:** As a super admin, I want to configure notification settings through a React interface, so that I can manage SMTP configuration with improved security and validation.

#### Acceptance Criteria

1. WHEN the notification settings form loads, THE System SHALL display SMTP configuration fields with validation rules
2. WHEN sensitive data is displayed, THE System SHALL mask passwords and credentials in form fields
3. WHEN SMTP settings are updated, THE System SHALL validate all required fields before saving
4. WHEN the test email button is clicked, THE System SHALL send a test email with loading state indicator
5. WHEN the email statistics view is opened, THE System SHALL display email sending statistics dashboard
6. WHEN SMTP validation fails, THE System SHALL display specific error messages for connection issues

### Requirement 9: SLA Settings Migration

**User Story:** As a super admin, I want to configure SLA settings through a React interface, so that I can manage service level agreements per business unit with improved validation.

#### Acceptance Criteria

1. WHEN the SLA settings page loads, THE System SHALL display SLA configuration for all business units
2. WHEN SLA settings are updated, THE System SHALL validate time ranges are between 1 and 720 hours
3. WHEN follow-up time is set, THE System SHALL ensure it is less than completion time
4. WHEN email alerts are toggled, THE System SHALL show confirmation dialog before changing state
5. WHEN SLA settings are displayed, THE System SHALL show compliance statistics for each business unit

### Requirement 10: Consistent Navigation and Layout

**User Story:** As a super admin, I want consistent navigation across all admin pages, so that I can navigate efficiently with familiar patterns.

#### Acceptance Criteria

1. WHEN any admin page is accessed, THE System SHALL display the existing sidebar navigation structure
2. WHEN an admin section is active, THE System SHALL highlight the corresponding sidebar menu item
3. WHEN navigating between admin pages, THE System SHALL display breadcrumb navigation showing current location
4. WHEN the back button is clicked, THE System SHALL navigate to the previous page using browser history
5. WHEN navigation occurs, THE System SHALL use Inertia client-side routing for instant page transitions

### Requirement 11: Loading States and Error Handling

**User Story:** As a super admin, I want clear loading states and error messages, so that I understand system status and can resolve issues quickly.

#### Acceptance Criteria

1. WHEN a page is loading initially, THE System SHALL display skeleton loaders for content areas
2. WHEN a form is being submitted, THE System SHALL show a spinner and disable submit button
3. WHEN a file is being uploaded, THE System SHALL display a progress indicator
4. WHEN validation errors occur, THE System SHALL display inline error messages next to invalid fields
5. WHEN server errors occur, THE System SHALL display toast notifications with error details
6. WHEN React errors occur, THE System SHALL catch them with error boundaries and display fallback UI
7. WHEN optimistic updates are performed, THE System SHALL immediately update UI and rollback on failure

### Requirement 12: Responsive Design and Accessibility

**User Story:** As a super admin, I want admin pages to work on all devices and be accessible, so that I can manage the system from any device and ensure inclusivity.

#### Acceptance Criteria

1. WHEN admin pages are viewed on mobile devices, THE System SHALL use responsive layouts with mobile-first design
2. WHEN tables are displayed on mobile, THE System SHALL provide horizontal scroll for wide tables
3. WHEN filters are displayed on mobile, THE System SHALL show collapsible filter panels
4. WHEN forms are displayed on mobile, THE System SHALL use touch-friendly input controls
5. WHEN interactive elements are rendered, THE System SHALL include ARIA labels for screen readers
6. WHEN modals are opened, THE System SHALL manage focus and trap keyboard navigation within modal
7. WHEN keyboard navigation is used, THE System SHALL support tab navigation through all interactive elements

### Requirement 13: Performance Optimization

**User Story:** As a super admin, I want fast page loads and smooth interactions, so that I can work efficiently without delays.

#### Acceptance Criteria

1. WHEN the initial admin page loads, THE System SHALL complete loading in less than 2 seconds
2. WHEN navigating between admin pages, THE System SHALL complete transitions in less than 500ms using Inertia SPA
3. WHEN search inputs receive text, THE System SHALL debounce input by 300ms before triggering search
4. WHEN large datasets are displayed, THE System SHALL implement pagination to limit rendered items
5. WHEN heavy components are needed, THE System SHALL lazy load charts and complex tables
6. WHEN admin routes are accessed, THE System SHALL code-split bundles per admin section
7. WHEN related pages are hovered, THE System SHALL prefetch page data for instant navigation

### Requirement 14: Security and Authorization

**User Story:** As a super admin, I want secure admin operations with proper authorization, so that sensitive system configuration is protected.

#### Acceptance Criteria

1. WHEN any admin page is accessed, THE System SHALL verify admin.access middleware authorization
2. WHEN sensitive operations are performed, THE System SHALL verify super admin role on backend
3. WHEN sensitive data is displayed, THE System SHALL mask passwords and SMTP credentials
4. WHEN user inputs are processed, THE System SHALL sanitize inputs to prevent XSS attacks
5. WHEN file uploads are processed, THE System SHALL validate file types and limit size to 2MB
6. WHEN API requests are made, THE System SHALL include CSRF tokens for protection
7. WHEN unauthorized access is attempted, THE System SHALL redirect to login or show 403 error

### Requirement 15: Data Table Functionality

**User Story:** As a super admin, I want consistent data table functionality across all admin pages, so that I can efficiently browse, search, and filter data.

#### Acceptance Criteria

1. WHEN data tables are rendered, THE System SHALL use TanStack Table (React Table v8) for consistent behavior
2. WHEN table columns are clicked, THE System SHALL support sorting by that column
3. WHEN search is performed, THE System SHALL filter table data in real-time
4. WHEN pagination controls are used, THE System SHALL navigate between pages without full reload
5. WHEN table rows are clicked, THE System SHALL navigate to detail view or trigger row action
6. WHEN tables are empty, THE System SHALL display empty state message with helpful guidance
7. WHEN table data is loading, THE System SHALL show skeleton rows matching table structure

### Requirement 16: Form Validation and User Feedback

**User Story:** As a super admin, I want comprehensive form validation and clear feedback, so that I can submit valid data and understand any issues.

#### Acceptance Criteria

1. WHEN forms are rendered, THE System SHALL use React Hook Form for form state management
2. WHEN form fields are validated, THE System SHALL use Zod schemas for type-safe validation
3. WHEN validation errors occur, THE System SHALL display inline error messages immediately
4. WHEN forms are submitted successfully, THE System SHALL show success toast notification
5. WHEN forms are submitted with errors, THE System SHALL show error toast with summary
6. WHEN required fields are empty, THE System SHALL prevent form submission and highlight fields
7. WHEN form data changes, THE System SHALL validate fields in real-time as user types

### Requirement 17: File Upload Handling

**User Story:** As a super admin, I want intuitive file upload for logos and documents, so that I can easily manage visual assets.

#### Acceptance Criteria

1. WHEN file upload fields are rendered, THE System SHALL use React Dropzone for drag-and-drop support
2. WHEN files are selected, THE System SHALL show image preview before upload
3. WHEN files are validated, THE System SHALL check file type is image (jpg, png, gif, svg)
4. WHEN files are validated, THE System SHALL check file size does not exceed 2MB
5. WHEN files are uploading, THE System SHALL display progress bar showing upload percentage
6. WHEN existing files are displayed, THE System SHALL provide remove/delete option
7. WHEN file upload fails, THE System SHALL display specific error message about the failure reason

### Requirement 18: Chart and Data Visualization

**User Story:** As a super admin, I want visual charts and statistics, so that I can quickly understand system trends and metrics.

#### Acceptance Criteria

1. WHEN charts are rendered, THE System SHALL use Chart.js with react-chartjs-2 wrapper
2. WHEN the admin dashboard loads, THE System SHALL display monthly PR trends as a line or bar chart
3. WHEN chart data is loading, THE System SHALL show skeleton loader in chart area
4. WHEN charts are displayed, THE System SHALL use consistent color scheme matching Tailwind palette
5. WHEN charts are interactive, THE System SHALL show tooltips on hover with detailed data
6. WHEN charts are rendered on mobile, THE System SHALL scale appropriately for small screens

### Requirement 19: Backend Controller Updates

**User Story:** As a developer, I want backend controllers updated to support Inertia, so that React components receive properly formatted data.

#### Acceptance Criteria

1. WHEN admin routes are accessed, THE Controllers SHALL return Inertia responses instead of Blade views
2. WHEN data is returned, THE Controllers SHALL format data as JSON-serializable arrays or objects
3. WHEN validation occurs, THE Controllers SHALL maintain existing Laravel validation rules
4. WHEN authorization is checked, THE Controllers SHALL maintain existing middleware (admin.access)
5. WHEN AJAX endpoints are needed, THE Controllers SHALL create separate API endpoints for dynamic data
6. WHEN errors occur, THE Controllers SHALL return appropriate HTTP status codes and error messages

### Requirement 20: TypeScript Type Safety

**User Story:** As a developer, I want comprehensive TypeScript types, so that I can develop with confidence and catch errors early.

#### Acceptance Criteria

1. WHEN React components are created, THE System SHALL define TypeScript interfaces for all props
2. WHEN API responses are received, THE System SHALL define TypeScript types matching backend data structures
3. WHEN forms are created, THE System SHALL define Zod schemas that generate TypeScript types
4. WHEN shared types are needed, THE System SHALL define them in centralized type definition files
5. WHEN TypeScript compilation occurs, THE System SHALL achieve 90% or higher type coverage
6. WHEN type errors exist, THE System SHALL prevent compilation until resolved

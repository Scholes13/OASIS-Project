# Requirements Document

## Introduction

Migrasi komponen Livewire ke React dengan Inertia.js untuk meningkatkan performa, user experience, dan maintainability aplikasi Oasis. Migrasi ini mencakup komponen layout (Sidebar, Navbar) dan modul Purchase Request sebagai pilot project. Sistem akan menggunakan React dengan TypeScript, Inertia.js untuk komunikasi dengan Laravel backend, dan Tailwind CSS untuk styling yang konsisten.

## Glossary

- **Inertia.js**: Framework yang memungkinkan pembuatan SPA (Single Page Application) menggunakan routing server-side klasik
- **React**: Library JavaScript untuk membangun user interface dengan component-based architecture
- **TypeScript**: Superset JavaScript yang menambahkan static typing
- **Livewire**: Framework Laravel untuk membangun reactive interfaces tanpa JavaScript framework
- **NavigationService**: Service yang membangun struktur menu navigasi berdasarkan user permissions dan business unit context
- **Business Unit Context**: Konteks business unit aktif yang disimpan di session
- **Shared Props**: Data yang dibagikan ke semua halaman Inertia (auth, navigation, flash messages)
- **Layout Component**: Komponen React yang membungkus konten halaman (Sidebar, Navbar)
- **Page Component**: Komponen React yang merepresentasikan halaman individual

## Requirements

### Requirement 1: Inertia.js Setup and Configuration

**User Story:** As a developer, I want Inertia.js properly configured with React and TypeScript, so that I can build modern SPA interfaces while keeping Laravel backend routing.

#### Acceptance Criteria

1. WHEN Inertia.js is installed, THEN the system SHALL configure Inertia with React adapter and TypeScript support
2. WHEN a user visits any Inertia page, THEN the system SHALL load the root Inertia layout template
3. WHEN Inertia makes requests, THEN the system SHALL use HandleInertiaRequests middleware to share common props
4. WHEN shared props are defined, THEN the system SHALL include auth user data, current business unit, available business units, navigation menu, and flash messages
5. WHEN TypeScript is configured, THEN the system SHALL provide type definitions for all shared props and page props

### Requirement 2: NavigationService Implementation

**User Story:** As the system, I want a NavigationService that builds dynamic navigation menus, so that users see only menu items they have permission to access based on their role and business unit.

#### Acceptance Criteria

1. WHEN NavigationService builds a menu for a user, THEN the system SHALL check user permissions for each menu item
2. WHEN a user is a Super Admin, THEN the system SHALL display all menu items regardless of business unit
3. WHEN a user switches business units, THEN the system SHALL rebuild the navigation menu with items relevant to the new business unit
4. WHEN a menu item requires specific permissions, THEN the system SHALL hide that item if the user lacks the permission
5. WHEN building the menu, THEN the system SHALL organize items into logical sections (Dashboard, Purchasing, Activity Tracking, Sales CRM, Administration)
6. WHEN a user has purchasing admin privileges, THEN the system SHALL display the Purchasing Admin menu item
7. WHEN a user is top management in parent BU, THEN the system SHALL display report access menu items

### Requirement 3: React Layout Components

**User Story:** As a user, I want consistent layout components (Sidebar and Navbar) across all pages, so that navigation and branding remain familiar throughout the application.

#### Acceptance Criteria

1. WHEN a page loads, THEN the system SHALL render the Sidebar component with navigation menu items
2. WHEN a page loads, THEN the system SHALL render the Navbar component with business unit switcher and user menu
3. WHEN the Sidebar is rendered, THEN the system SHALL highlight the active menu item based on current route
4. WHEN the Navbar is rendered, THEN the system SHALL display current business unit logo and name
5. WHEN the layout is responsive, THEN the system SHALL collapse Sidebar on mobile and show hamburger menu
6. WHEN a user clicks a menu item, THEN the system SHALL navigate using Inertia.visit without full page reload
7. WHEN navigation occurs, THEN the system SHALL preserve scroll position for back/forward navigation

### Requirement 4: Business Unit Switcher Component

**User Story:** As a user assigned to multiple business units, I want to switch between business units seamlessly, so that I can access data and features specific to each business unit.

#### Acceptance Criteria

1. WHEN the Business Unit Switcher is rendered, THEN the system SHALL display all business units the user has access to
2. WHEN a user selects a different business unit, THEN the system SHALL update the session with the new business unit ID
3. WHEN business unit is switched, THEN the system SHALL reload the current page with updated data for the new business unit
4. WHEN business unit is switched, THEN the system SHALL update the navigation menu to reflect permissions in the new business unit
5. WHEN business unit is switched, THEN the system SHALL display a success toast notification
6. WHEN only one business unit is available, THEN the system SHALL hide the switcher dropdown

### Requirement 5: User Menu Component

**User Story:** As a user, I want a user menu in the Navbar with my profile information and logout option, so that I can manage my account and sign out securely.

#### Acceptance Criteria

1. WHEN the User Menu is rendered, THEN the system SHALL display the user's name and avatar
2. WHEN a user clicks the User Menu, THEN the system SHALL show a dropdown with profile and logout options
3. WHEN a user clicks logout, THEN the system SHALL sign out the user and redirect to login page
4. WHEN the User Menu displays avatar, THEN the system SHALL show user initials if no avatar image is available
5. WHEN the User Menu is open and user clicks outside, THEN the system SHALL close the dropdown

### Requirement 6: Purchase Request Index Page Migration

**User Story:** As a user, I want the Purchase Request list page migrated to React, so that I can experience faster navigation and better interactivity.

#### Acceptance Criteria

1. WHEN a user visits the Purchase Request index page, THEN the system SHALL render a React component displaying the PR list
2. WHEN the PR list is rendered, THEN the system SHALL display PR number, requester, department, status, total amount, and created date
3. WHEN a user filters PRs by status, THEN the system SHALL update the list without full page reload
4. WHEN a user searches PRs, THEN the system SHALL filter results in real-time
5. WHEN a user clicks a PR row, THEN the system SHALL navigate to the PR detail page using Inertia
6. WHEN the PR list is paginated, THEN the system SHALL use Inertia pagination without full page reload
7. WHEN a user switches business units, THEN the system SHALL reload the PR list with data from the new business unit

### Requirement 7: Purchase Request Create Page Migration

**User Story:** As a user, I want the Purchase Request creation form migrated to React, so that I can create PRs with better form validation and user experience.

#### Acceptance Criteria

1. WHEN a user visits the PR create page, THEN the system SHALL render a React form component
2. WHEN a user fills the form, THEN the system SHALL validate inputs in real-time with TypeScript type checking
3. WHEN a user adds PR items, THEN the system SHALL dynamically add item rows without page reload
4. WHEN a user removes PR items, THEN the system SHALL update the total amount calculation immediately
5. WHEN a user uploads item images, THEN the system SHALL preview images before submission
6. WHEN a user submits the form, THEN the system SHALL send data to Laravel backend via Inertia POST request
7. WHEN form submission succeeds, THEN the system SHALL redirect to PR detail page with success message
8. WHEN form submission fails, THEN the system SHALL display validation errors inline without page reload

### Requirement 8: Purchase Request Detail Page Migration

**User Story:** As a user, I want the Purchase Request detail page migrated to React, so that I can view PR information with better layout and interactivity.

#### Acceptance Criteria

1. WHEN a user visits a PR detail page, THEN the system SHALL render a React component displaying PR information
2. WHEN the PR detail is rendered, THEN the system SHALL display PR header, items table, approval history, and action buttons
3. WHEN a user has permission to approve, THEN the system SHALL display approve/reject buttons
4. WHEN a user clicks approve, THEN the system SHALL show a confirmation modal before submitting
5. WHEN approval is submitted, THEN the system SHALL update the PR status without full page reload
6. WHEN a user views approval history, THEN the system SHALL display timeline with approver names, timestamps, and comments
7. WHEN a user can edit the PR, THEN the system SHALL display an edit button that navigates to edit page

### Requirement 9: Toast Notification System

**User Story:** As a user, I want toast notifications for actions and feedback, so that I receive immediate visual confirmation of my actions.

#### Acceptance Criteria

1. WHEN an action succeeds, THEN the system SHALL display a success toast notification
2. WHEN an action fails, THEN the system SHALL display an error toast notification
3. WHEN a warning occurs, THEN the system SHALL display a warning toast notification
4. WHEN an info message is shown, THEN the system SHALL display an info toast notification
5. WHEN a toast is displayed, THEN the system SHALL auto-dismiss it after 5 seconds
6. WHEN multiple toasts are shown, THEN the system SHALL stack them vertically
7. WHEN a user clicks a toast close button, THEN the system SHALL dismiss that toast immediately

### Requirement 10: Loading States and Progress Indicators

**User Story:** As a user, I want loading indicators during navigation and data fetching, so that I know the system is processing my request.

#### Acceptance Criteria

1. WHEN Inertia navigates between pages, THEN the system SHALL display a progress bar at the top of the page
2. WHEN a form is submitting, THEN the system SHALL disable the submit button and show loading spinner
3. WHEN data is being fetched, THEN the system SHALL display skeleton loaders for content areas
4. WHEN navigation completes, THEN the system SHALL hide the progress bar
5. WHEN an error occurs during navigation, THEN the system SHALL hide loading indicators and show error message

### Requirement 11: Responsive Design and Mobile Support

**User Story:** As a mobile user, I want the React components to be fully responsive, so that I can use the application on any device.

#### Acceptance Criteria

1. WHEN viewed on mobile, THEN the system SHALL collapse the Sidebar and show a hamburger menu icon
2. WHEN a user taps the hamburger menu, THEN the system SHALL slide in the Sidebar overlay
3. WHEN viewed on tablet, THEN the system SHALL adjust layout to optimize screen space
4. WHEN viewed on desktop, THEN the system SHALL display full Sidebar and Navbar
5. WHEN tables are rendered on mobile, THEN the system SHALL make them horizontally scrollable
6. WHEN forms are rendered on mobile, THEN the system SHALL stack form fields vertically
7. WHEN modals are shown on mobile, THEN the system SHALL display them full-screen

### Requirement 12: TypeScript Type Safety

**User Story:** As a developer, I want TypeScript types for all props and data structures, so that I can catch errors at compile time and have better IDE support.

#### Acceptance Criteria

1. WHEN shared props are defined, THEN the system SHALL provide TypeScript interfaces for auth, navigation, and flash data
2. WHEN page props are passed, THEN the system SHALL define TypeScript interfaces for each page's props
3. WHEN API responses are received, THEN the system SHALL type the response data structures
4. WHEN forms are created, THEN the system SHALL type form data and validation errors
5. WHEN components receive props, THEN the system SHALL enforce prop types with TypeScript

### Requirement 13: Backward Compatibility and Gradual Migration

**User Story:** As a developer, I want the React migration to coexist with existing Livewire components, so that we can migrate gradually without breaking existing functionality.

#### Acceptance Criteria

1. WHEN a route uses Livewire, THEN the system SHALL continue to render Livewire components normally
2. WHEN a route uses Inertia, THEN the system SHALL render React components
3. WHEN navigating from Livewire to Inertia page, THEN the system SHALL perform a full page load
4. WHEN navigating from Inertia to Livewire page, THEN the system SHALL perform a full page load
5. WHEN both systems are active, THEN the system SHALL maintain session state correctly
6. WHEN authentication is checked, THEN the system SHALL work for both Livewire and Inertia routes

### Requirement 14: Performance Optimization

**User Story:** As a user, I want fast page loads and smooth interactions, so that the application feels responsive and modern.

#### Acceptance Criteria

1. WHEN React components are built, THEN the system SHALL code-split by route for optimal bundle size
2. WHEN assets are loaded, THEN the system SHALL use Vite for fast HMR in development
3. WHEN production build is created, THEN the system SHALL minify and optimize all JavaScript and CSS
4. WHEN images are loaded, THEN the system SHALL lazy load images below the fold
5. WHEN data is fetched, THEN the system SHALL cache responses where appropriate
6. WHEN navigation occurs, THEN the system SHALL prefetch linked pages on hover

### Requirement 15: Error Handling and Debugging

**User Story:** As a developer, I want comprehensive error handling and debugging tools, so that I can quickly identify and fix issues.

#### Acceptance Criteria

1. WHEN a React component errors, THEN the system SHALL display an error boundary with helpful message
2. WHEN an API request fails, THEN the system SHALL log the error and display user-friendly message
3. WHEN validation fails, THEN the system SHALL display field-level errors with clear messaging
4. WHEN in development mode, THEN the system SHALL show detailed error stack traces
5. WHEN in production mode, THEN the system SHALL log errors to server without exposing sensitive information

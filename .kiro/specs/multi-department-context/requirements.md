# Requirements Document

## Introduction

This feature enables users who belong to multiple departments within a single business unit to switch between their department contexts. Currently, the Activity Tracking module only uses `primary_department_id`, which limits users like Adiel Priyarama who work across multiple departments (e.g., IT and Marketing) within the same business unit. This enhancement introduces session-based department context switching, similar to the existing business unit switching mechanism.

## Glossary

- **Department_Context**: The currently active department for a user within their active business unit, stored in session as `current_department_id`
- **Multi_Department_User**: A user who has multiple `user_business_units` records with the same `business_unit_id` but different `department_id` values
- **Department_Switcher**: A UI component that allows users to switch between their assigned departments within the current business unit
- **Activity_Module**: The Employee Task Management module that tracks tasks scoped to departments

## Requirements

### Requirement 1

**User Story:** As a multi-department user, I want the system to automatically set my department context when I log in or switch business units, so that I can immediately start working without manual configuration.

#### Acceptance Criteria

1. WHEN a user logs in THEN the System SHALL set `current_department_id` in session to the user's primary department for the active business unit
2. WHEN a user switches business units THEN the System SHALL update `current_department_id` to the user's primary department in the new business unit
3. WHEN a user has only one department in the active business unit THEN the System SHALL automatically use that department without requiring selection
4. IF a user has no department assignment in the active business unit THEN the System SHALL set `current_department_id` to null and display an appropriate message

### Requirement 2

**User Story:** As a multi-department user, I want to switch between my assigned departments within the current business unit, so that I can manage tasks and activities for different teams.

#### Acceptance Criteria

1. WHEN a user has multiple departments in the active business unit THEN the System SHALL display a department switcher component in the UI
2. WHEN a user has only one department in the active business unit THEN the System SHALL hide the department switcher component
3. WHEN a user selects a different department from the switcher THEN the System SHALL update `current_department_id` in session immediately
4. WHEN a user switches departments THEN the System SHALL refresh the current page to reflect the new department context
5. WHEN displaying the department switcher THEN the System SHALL show the department name and indicate which is currently active

### Requirement 3

**User Story:** As a user, I want the Activity module to use my current department context, so that tasks I create are assigned to the correct department and I see relevant department tasks.

#### Acceptance Criteria

1. WHEN a user creates a new task THEN the System SHALL assign the task to `current_department_id` from session
2. WHEN displaying the task list THEN the System SHALL filter tasks by `current_department_id` in addition to user participation
3. WHEN displaying department analytics THEN the System SHALL show statistics for `current_department_id`
4. WHEN a user views department tasks THEN the System SHALL show tasks belonging to `current_department_id`
5. WHEN fetching department users for task participants THEN the System SHALL query users in `current_department_id`

### Requirement 4

**User Story:** As a user, I want to see my current department context in the UI, so that I always know which department I am working in.

#### Acceptance Criteria

1. WHEN a user is logged in THEN the System SHALL display the current department name in the shared Inertia props
2. WHEN the department context changes THEN the System SHALL update the displayed department name immediately
3. WHEN displaying the department in the UI THEN the System SHALL show both department name and code for clarity

### Requirement 5

**User Story:** As a developer, I want helper methods to access the current department context, so that I can easily use it throughout the application.

#### Acceptance Criteria

1. THE User model SHALL provide a `getCurrentDepartmentId()` method that returns the session department ID
2. THE User model SHALL provide a `getDepartmentsInCurrentBusinessUnit()` method that returns all user departments in the active business unit
3. THE User model SHALL provide a `hasMultipleDepartmentsInCurrentBusinessUnit()` method that returns true if user has more than one department
4. WHEN `current_department_id` is not set in session THEN the helper methods SHALL fall back to `primary_department_id`

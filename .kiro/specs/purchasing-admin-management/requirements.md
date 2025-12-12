# Requirements Document

## Introduction

The Purchasing Admin Management feature enables designated purchasing administrators to track, manage, and complete procurement follow-up tasks for approved Purchase Requests (PR) and Stock Requests (ST). The system tracks follow-up time, completion time, and price efficiency by comparing estimated prices with realized prices, providing performance metrics and savings reports across business units.

## Glossary

- **Purchasing Admin**: A user assigned to handle procurement follow-up tasks within a purchasing department
- **Purchasing Department**: A department flagged to handle purchasing administration tasks
- **Admin Task**: A procurement follow-up task created when a PR/ST is approved
- **Estimated Price**: The total amount from the original PR/ST request
- **Realized Price**: The actual procurement price achieved by the purchasing admin
- **Savings Percentage**: The percentage difference between estimated and realized prices: ((Estimated - Realized) / Estimated) × 100%
- **Follow-up Time**: Duration from task entry to when admin starts working (Pending → In Progress)
- **Completion Time**: Duration from start to completion (In Progress → Done)
- **Default Admin**: The designated admin for auto-assignment when only one admin exists
- **Task Pool**: Collection of unassigned tasks available for manual claiming
- **Parent BU**: Werkudara Group, the parent holding company
- **Child BU**: Business units under Werkudara Group (WNS, UK, MRP)
- **SLA**: Service Level Agreement for follow-up and completion time targets

## Requirements

### Requirement 1: Department Configuration

**User Story:** As a Super Admin, I want to configure which departments handle purchasing administration, so that only designated departments can access purchasing admin features.

#### Acceptance Criteria

1. WHEN a Super Admin flags a department as a purchasing department, THEN the system SHALL store the purchasing department flag in the departments table
2. WHEN a Super Admin assigns a default purchasing admin to a department, THEN the system SHALL store the default admin user ID in the departments table
3. WHEN a department is flagged as a purchasing department, THEN the system SHALL allow only users in that department with purchasing admin privileges to access purchasing admin features
4. WHEN a department has a default purchasing admin assigned, THEN the system SHALL use that admin for auto-assignment of new tasks
5. WHEN a Super Admin views department settings, THEN the system SHALL display the purchasing department flag and default admin assignment

### Requirement 2: Purchasing Admin Assignment

**User Story:** As a Super Admin, I want to assign specific users as purchasing admins within purchasing departments, so that only authorized personnel can manage procurement tasks.

#### Acceptance Criteria

1. WHEN a Super Admin assigns a user as a purchasing admin, THEN the system SHALL set the is_purchasing_admin flag to true in the user_business_units table
2. WHEN a user is assigned as a purchasing admin in a purchasing department, THEN the system SHALL grant access to the purchasing admin menu for that business unit
3. WHEN a user is not assigned as a purchasing admin, THEN the system SHALL hide the purchasing admin menu from that user
4. WHEN a Super Admin removes purchasing admin privileges from a user, THEN the system SHALL revoke access to purchasing admin features for that user
5. WHEN a user is assigned as purchasing admin in multiple business units, THEN the system SHALL grant access to purchasing admin features in each assigned business unit

### Requirement 3: Admin Task Creation

**User Story:** As the system, I want to automatically create admin tasks when PRs or STs are approved, so that purchasing admins can track and follow up on procurement activities.

#### Acceptance Criteria

1. WHEN a Purchase Request reaches approved status, THEN the system SHALL create an admin task record with status pending_followup
2. WHEN a Stock Request reaches approved status, THEN the system SHALL create an admin task record with status pending_followup
3. WHEN an admin task is created, THEN the system SHALL record the entry timestamp as entered_at
4. WHEN an admin task is created, THEN the system SHALL copy the estimated total price from the PR or ST
5. WHEN an admin task is created and the department has exactly one purchasing admin, THEN the system SHALL auto-assign the task to the default admin
6. WHEN an admin task is created and the department has multiple purchasing admins, THEN the system SHALL leave the task unassigned for manual claiming
7. WHEN an admin task is created, THEN the system SHALL initialize savings_amount and savings_percentage as null

### Requirement 4: Task Assignment and Claiming

**User Story:** As a Purchasing Admin, I want to claim available tasks from the unassigned pool, so that I can manage my workload and take ownership of procurement follow-ups.

#### Acceptance Criteria

1. WHEN a purchasing admin views the available tasks list, THEN the system SHALL display all unassigned tasks for the current business unit
2. WHEN a purchasing admin claims an unassigned task, THEN the system SHALL assign the task to that admin
3. WHEN a task is claimed by an admin, THEN the system SHALL prevent other admins from claiming the same task
4. WHEN only one purchasing admin exists in a department, THEN the system SHALL auto-assign new tasks to that admin without requiring manual claiming
5. WHEN multiple purchasing admins exist in a department, THEN the system SHALL require manual claiming for task assignment

### Requirement 5: Task Status Management

**User Story:** As a Purchasing Admin, I want to update task status from pending to in progress to done, so that the system can track my follow-up activities and completion times.

#### Acceptance Criteria

1. WHEN a purchasing admin starts working on a pending task, THEN the system SHALL update the task status to in_progress and record the started_at timestamp
2. WHEN a task status changes to in_progress, THEN the system SHALL calculate the follow-up time as the duration between entered_at and started_at
3. WHEN a purchasing admin completes a task, THEN the system SHALL require input of the realized total price before allowing status change to done
4. WHEN a purchasing admin inputs the realized price and completes a task, THEN the system SHALL update the task status to done and record the completed_at timestamp
5. WHEN a task status changes to done, THEN the system SHALL calculate the completion time as the duration between started_at and completed_at
6. WHEN a task is marked as done, THEN the system SHALL calculate savings_amount as estimated_total_price minus realized_total_price
7. WHEN a task is marked as done, THEN the system SHALL calculate savings_percentage as ((estimated_total_price - realized_total_price) / estimated_total_price) × 100
8. WHEN a task is marked as done, THEN the PR or ST status SHALL remain as approved

### Requirement 6: Task Viewing and Filtering

**User Story:** As a Purchasing Admin, I want to view and filter my tasks by status, so that I can manage my workload efficiently.

#### Acceptance Criteria

1. WHEN a purchasing admin accesses the admin dashboard, THEN the system SHALL display tasks grouped by status: pending_followup, in_progress, and done
2. WHEN a purchasing admin views pending tasks, THEN the system SHALL display all unassigned tasks and tasks assigned to that admin with pending_followup status
3. WHEN a purchasing admin views in-progress tasks, THEN the system SHALL display only tasks assigned to that admin with in_progress status
4. WHEN a purchasing admin views completed tasks, THEN the system SHALL display only tasks completed by that admin with done status
5. WHEN a purchasing admin filters tasks by date range, THEN the system SHALL display tasks where entered_at falls within the specified range
6. WHEN a purchasing admin filters tasks by PR or ST type, THEN the system SHALL display only tasks of the selected type

### Requirement 7: Performance Metrics and Reports

**User Story:** As a Purchasing Admin, I want to view my performance metrics, so that I can track my efficiency in follow-up time and price savings.

#### Acceptance Criteria

1. WHEN a purchasing admin views their performance dashboard, THEN the system SHALL display total tasks completed
2. WHEN a purchasing admin views their performance dashboard, THEN the system SHALL display average follow-up time across all completed tasks
3. WHEN a purchasing admin views their performance dashboard, THEN the system SHALL display average completion time across all completed tasks
4. WHEN a purchasing admin views their performance dashboard, THEN the system SHALL display total savings amount achieved
5. WHEN a purchasing admin views their performance dashboard, THEN the system SHALL display average savings percentage across all completed tasks
6. WHEN a purchasing admin views their performance dashboard, THEN the system SHALL display a trend chart showing savings percentage over time by month
7. WHEN a purchasing admin views their performance dashboard, THEN the system SHALL display a chart showing number of tasks completed per month

### Requirement 8: Department Manager Reports

**User Story:** As a Department Manager of a purchasing department, I want to view performance reports for my department, so that I can monitor team efficiency and savings achievements.

#### Acceptance Criteria

1. WHEN a department manager views the department report, THEN the system SHALL display performance metrics for all purchasing admins in that department
2. WHEN a department manager views the department report, THEN the system SHALL display total savings achieved by the department
3. WHEN a department manager views the department report, THEN the system SHALL display average follow-up time for the department
4. WHEN a department manager views the department report, THEN the system SHALL display average completion time for the department
5. WHEN a department manager views the department report, THEN the system SHALL display savings breakdown by PR and ST categories
6. WHEN a department manager views the department report, THEN the system SHALL display performance comparison between admins in the department
7. WHEN a department manager views the department report, THEN the system SHALL display trend charts for department performance over time

### Requirement 9: Business Unit Consolidated Reports

**User Story:** As a Super Admin or Top Management in the parent business unit, I want to view consolidated reports across all business units, so that I can compare performance and identify best practices.

#### Acceptance Criteria

1. WHEN a Super Admin or parent BU top management views the consolidated report, THEN the system SHALL display savings metrics for all child business units
2. WHEN viewing the consolidated report, THEN the system SHALL display total savings amount per business unit
3. WHEN viewing the consolidated report, THEN the system SHALL display average savings percentage per business unit
4. WHEN viewing the consolidated report, THEN the system SHALL display average follow-up time per business unit
5. WHEN viewing the consolidated report, THEN the system SHALL display average completion time per business unit
6. WHEN viewing the consolidated report, THEN the system SHALL display trend charts comparing business unit performance over time
7. WHEN viewing the consolidated report, THEN the system SHALL display total tasks completed per business unit

### Requirement 10: SLA Configuration and Alerts

**User Story:** As a Super Admin, I want to configure SLA targets for follow-up and completion times, so that the system can alert admins when tasks exceed target durations.

#### Acceptance Criteria

1. WHEN a Super Admin configures SLA settings, THEN the system SHALL store follow-up time target in hours
2. WHEN a Super Admin configures SLA settings, THEN the system SHALL store completion time target in hours
3. WHEN a Super Admin enables SLA email alerts, THEN the system SHALL send email notifications when tasks exceed SLA targets
4. WHEN a Super Admin disables SLA email alerts, THEN the system SHALL not send email notifications for SLA violations
5. WHEN a task exceeds the follow-up SLA target, THEN the system SHALL send an email alert to the assigned admin and department manager
6. WHEN a task exceeds the completion SLA target, THEN the system SHALL send an email alert to the assigned admin and department manager
7. WHEN viewing tasks, THEN the system SHALL visually indicate tasks that have exceeded SLA targets

### Requirement 11: Access Control and Menu Visibility

**User Story:** As the system, I want to control access to purchasing admin features based on user roles and assignments, so that only authorized users can view and manage procurement tasks.

#### Acceptance Criteria

1. WHEN a user is not a purchasing admin, THEN the system SHALL hide the purchasing admin menu from that user
2. WHEN a user is a purchasing admin in a purchasing department, THEN the system SHALL display the purchasing admin menu for that business unit
3. WHEN a user is a Super Admin, THEN the system SHALL display the purchasing admin menu with full access to all business units
4. WHEN a user is top management in the parent business unit, THEN the system SHALL display the purchasing admin menu with access to consolidated reports
5. WHEN a user switches business units, THEN the system SHALL update menu visibility based on purchasing admin status in the new business unit
6. WHEN a regular staff member in a purchasing department views the system, THEN the system SHALL not display any purchasing admin features
7. WHEN a department manager who is not a purchasing admin views the system, THEN the system SHALL display read-only access to department reports

### Requirement 12: Audit Trail and History

**User Story:** As a Super Admin or Department Manager, I want to view the complete history of admin task activities, so that I can audit procurement follow-up processes.

#### Acceptance Criteria

1. WHEN a Super Admin views audit history, THEN the system SHALL display all admin task activities across all business units
2. WHEN a department manager views audit history, THEN the system SHALL display admin task activities for their department only
3. WHEN a purchasing admin views their task history, THEN the system SHALL display all tasks they have handled
4. WHEN viewing task history, THEN the system SHALL display task creation timestamp, assignment timestamp, status change timestamps, and completion timestamp
5. WHEN viewing task history, THEN the system SHALL display which admin handled each task
6. WHEN viewing task history, THEN the system SHALL display estimated price, realized price, and savings achieved for completed tasks
7. WHEN viewing task history, THEN the system SHALL display follow-up time and completion time for each completed task

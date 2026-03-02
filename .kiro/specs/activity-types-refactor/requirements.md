# Requirements Document

## Introduction

Refactor Activity Types dan Sub Activities dari arsitektur per-department (dengan prefix seperti ACC_LEAVE, BAS_LEAVE) menjadi arsitektur master global. Super Admin mengelola semua activity types dan sub activities secara terpusat, kemudian assign ke department yang membutuhkan. Ini menghilangkan duplikasi data dan memberikan kontrol penuh kepada Super Admin.

## Glossary

- **Activity Type**: Kategori utama aktivitas karyawan (contoh: Leave, Training, Meeting, Administration) - dikelola global oleh Super Admin
- **Sub Activity**: Detail spesifik dari activity type (contoh: Leave → Sick Leave, Annual Leave, Permission) - dikelola global oleh Super Admin
- **Department Assignment**: Relasi many-to-many antara Activity Type dan Department melalui pivot table - dikelola oleh Super Admin
- **Super Admin**: User dengan akses penuh untuk mengelola semua activity types, sub activities, dan assignment ke department
- **Business Unit**: Entitas organisasi (WG, WNS, MRP, TEE, dll)

## Requirements

### Requirement 1

**User Story:** As a super admin, I want to manage master activity types globally, so that I can maintain a single source of truth for all activity categories across the organization.

#### Acceptance Criteria

1. WHEN a super admin accesses the Activity Types page THEN the system SHALL display all master activity types without department prefix
2. WHEN a super admin creates a new activity type THEN the system SHALL generate a unique code without department prefix (e.g., LEAVE, TRAINING, MEETING)
3. WHEN a super admin edits an activity type THEN the system SHALL update the master record
4. WHEN a super admin deletes an activity type THEN the system SHALL prevent deletion if the activity type has tasks or sub activities

### Requirement 2

**User Story:** As a super admin, I want to manage master sub activities globally, so that I can define detailed activity options once and reuse across departments.

#### Acceptance Criteria

1. WHEN a super admin accesses the Sub Activities page THEN the system SHALL display all sub activities grouped by their parent activity type
2. WHEN a super admin creates a sub activity THEN the system SHALL link it to a parent activity type
3. WHEN a super admin filters sub activities by activity type THEN the system SHALL show only sub activities belonging to that type
4. WHEN displaying sub activities THEN the system SHALL show the parent activity type name for context

### Requirement 3

**User Story:** As a super admin, I want to assign activity types to departments, so that each department has relevant activity options for their employees.

#### Acceptance Criteria

1. WHEN a super admin views activity type details THEN the system SHALL show which departments have this activity type assigned
2. WHEN a super admin assigns an activity type to a department THEN the system SHALL create a record in the department_activity_types pivot table
3. WHEN a super admin removes an activity type from a department THEN the system SHALL prevent removal if tasks exist using that activity type in the department
4. WHEN assigning activity types THEN the system SHALL allow setting a default activity type per department
5. WHEN a super admin bulk assigns activity types THEN the system SHALL allow selecting multiple departments at once

### Requirement 4

**User Story:** As a system administrator, I want to migrate existing prefixed activity types to global master records, so that duplicate data is consolidated.

#### Acceptance Criteria

1. WHEN running the migration THEN the system SHALL identify unique activity types by name (ignoring prefix)
2. WHEN consolidating duplicates THEN the system SHALL create one master record per unique activity type name
3. WHEN updating references THEN the system SHALL update all employee_tasks to point to the new master activity type IDs
4. WHEN updating department assignments THEN the system SHALL preserve existing department-activity relationships in the pivot table
5. WHEN migration completes THEN the system SHALL remove orphaned prefixed activity types that have been consolidated

### Requirement 5

**User Story:** As an employee, I want to select activity types relevant to my department when creating tasks, so that I can accurately categorize my work.

#### Acceptance Criteria

1. WHEN an employee creates a task THEN the system SHALL show only activity types assigned to their department
2. WHEN an employee selects an activity type THEN the system SHALL show sub activities belonging to that activity type
3. WHEN displaying activity options THEN the system SHALL order them by the department's configured sort order

### Requirement 6

**User Story:** As a super admin, I want to view activity types with their department assignments and usage statistics, so that I can understand how activities are used across the organization.

#### Acceptance Criteria

1. WHEN viewing activity types list THEN the system SHALL show count of departments using each activity type
2. WHEN viewing activity types list THEN the system SHALL show count of tasks using each activity type
3. WHEN filtering by business unit THEN the system SHALL show activity types assigned to departments in that business unit
4. WHEN viewing activity type details THEN the system SHALL list all departments with this activity type assigned

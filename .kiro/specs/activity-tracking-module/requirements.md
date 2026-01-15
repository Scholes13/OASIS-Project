# Requirements Document

## Introduction

Employee Task Tracking Module adalah modul untuk pencatatan aktivitas kerja karyawan di seluruh business unit Werkudara Group. Modul ini memungkinkan collaborative task dimana satu task bisa dikerjakan bersama-sama (shared task model seperti Trello/ClickUp).

Goals utama:
1. Management dapat mengetahui workload setiap employee
2. Tracking timestamp setiap task (created, started, completed)
3. Analisis kesibukan/persentase aktivitas per departemen dan business unit
4. Collaborative tasks - satu task bisa di-join oleh multiple users
5. Department-scoped visibility - user hanya melihat task dari departemen yang sama

## Glossary

- **Employee_Task**: Catatan aktivitas/task kerja yang dilakukan oleh employee
- **Task_Participant**: User yang terlibat dalam suatu task (owner atau joiner)
- **Activity_Type**: Kategori besar aktivitas (Meeting, Web Development, Event, dll)
- **Sub_Activity**: Sub-kategori dari activity type (Meeting Client, Fix Bug, dll)
- **Shared_Task**: Task yang dikerjakan bersama, status dan timestamp dibagi semua participant
- **Duration**: Lama waktu pengerjaan task (completed_at - started_at)

## Requirements

### Requirement 1: Task Creation

**User Story:** As an employee, I want to create a task to record my work activity.

#### Acceptance Criteria

1. WHEN an employee creates a task THEN the system SHALL require: task_title, activity_type, due_date
2. WHEN a task is created THEN the system SHALL auto-set created_at timestamp and status to 'planned'
3. WHEN a task is created THEN the system SHALL auto-add creator as participant with is_owner = true
4. WHEN a task is created THEN the system SHALL associate task with creator's current business_unit and department

### Requirement 2: Collaborative Task (Join)

**User Story:** As an employee, I want to join an existing task from my department, so that we can work together and share the same task record.

#### Acceptance Criteria

1. WHEN an employee views department tasks THEN the system SHALL show tasks from same department that they can join
2. WHEN an employee joins a task THEN the system SHALL add them as participant with joined_at = task.created_at
3. WHEN an employee joins a task THEN the system SHALL share all timestamps (started_at, completed_at) with them
4. WHERE a user is already a participant THEN the system SHALL NOT allow duplicate join

### Requirement 3: Shared Status Management

**User Story:** As a task participant, I want status changes to apply to all participants, so that we have synchronized task state.

#### Acceptance Criteria

1. WHEN any participant starts a task THEN the system SHALL set started_at for ALL participants
2. WHEN any participant completes a task THEN the system SHALL set completed_at for ALL participants and record completed_by
3. WHEN any participant completes a task THEN the system SHALL auto-calculate duration_minutes = completed_at - started_at
4. WHEN task owner cancels a task THEN the system SHALL set status to 'cancelled' for ALL participants
5. WHERE status is 'completed' or 'cancelled' THEN the system SHALL prevent further status changes

### Requirement 4: Department-Scoped Visibility

**User Story:** As an employee, I want to see only tasks from my department, so that I have relevant task visibility.

#### Acceptance Criteria

1. WHEN a regular user views task list THEN the system SHALL show only tasks where department_id matches user's department
2. WHEN a regular user views task list THEN the system SHALL also show tasks where user is a participant (regardless of department)
3. WHEN a department head views task list THEN the system SHALL show all tasks in their department
4. WHEN top management views analytics THEN the system SHALL show aggregated data across all departments in business unit

### Requirement 5: Activity Type & Sub-Activity Management

**User Story:** As a super admin, I want to manage activity types and sub-activities, so that employees have relevant categorization options.

#### Acceptance Criteria

1. WHEN super admin creates activity type THEN the system SHALL require: code, name, color
2. WHEN super admin creates sub-activity THEN the system SHALL require: activity_type_id, code, name
3. WHEN activity type is deactivated THEN the system SHALL hide from new task forms but preserve existing tasks
4. WHEN sub-activity dropdown loads THEN the system SHALL filter by selected activity_type

### Requirement 6: Task Attachments

**User Story:** As a task participant, I want to attach files to a task, so that I can include supporting documents.

#### Acceptance Criteria

1. WHEN a participant uploads attachment THEN the system SHALL validate file type (images, PDF, documents) and size (max 5MB)
2. WHEN a participant uploads attachment THEN the system SHALL allow up to 5 attachments per task
3. WHEN viewing a task THEN the system SHALL display attachments with preview for images and download links

### Requirement 7: Personal Task Dashboard

**User Story:** As an employee, I want to see my task summary, so that I can track my productivity.

#### Acceptance Criteria

1. WHEN employee views dashboard THEN the system SHALL show: total tasks, completed tasks, in-progress tasks, overdue tasks
2. WHEN employee views dashboard THEN the system SHALL show total hours worked (sum of duration_minutes)
3. WHEN employee views dashboard THEN the system SHALL show task breakdown by activity type

### Requirement 8: Department Analytics

**User Story:** As a department head, I want to see workload analytics for my team.

#### Acceptance Criteria

1. WHEN department head views analytics THEN the system SHALL show total tasks per team member
2. WHEN department head views analytics THEN the system SHALL show average completion time per activity type
3. WHEN department head views analytics THEN the system SHALL highlight overdue tasks (due_date < today AND status != completed)
4. WHEN department head views analytics THEN the system SHALL show on-time completion rate

### Requirement 9: Business Unit Analytics

**User Story:** As top management, I want to see activity analytics across the business unit.

#### Acceptance Criteria

1. WHEN management views analytics THEN the system SHALL show task summary per department
2. WHEN management views analytics THEN the system SHALL show productivity trends over time
3. WHEN management views analytics THEN the system SHALL show percentage of on-time vs overdue completions
4. WHEN management selects date range THEN the system SHALL filter all analytics accordingly

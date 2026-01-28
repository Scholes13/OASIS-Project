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
- **Backdate_Permission**: Izin untuk mengisi task dengan tanggal lebih dari 1 hari ke belakang (per-user)
- **Granted_Until**: Batas waktu berlakunya backdate permission (sampai akhir hari approval)
- **Default_Backdate_Limit**: Batas default backdate tanpa permission (1 hari ke belakang)
- **Department_Head**: HOD/Manager yang memiliki authority untuk approve backdate request
- **Requester**: User yang mengajukan backdate permission request
- **Department_Scope**: Task visibility terbatas pada department + business_unit combination (mandiri per BU)
- **Historical_Task**: Task yang dibuat sebelum user pindah department/BU (tetap visible di department lama)
- **Duration_Warning**: Alert UI jika task duration melebihi threshold tertentu (24 jam)

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

1. WHEN a regular user views task list THEN the system SHALL show only tasks where (department_id + business_unit_id) matches user's current department AND business unit
2. WHEN a regular user views task list THEN the system SHALL also show tasks where user is a participant (regardless of current department)
3. WHEN a department head views task list THEN the system SHALL show all tasks in their department within their business unit
4. WHEN top management views analytics THEN the system SHALL show aggregated data across all departments in business unit
5. WHERE user switches business unit THEN the system SHALL filter tasks to show only tasks from new business unit context
6. WHERE user has been reassigned to different department THEN the system SHALL still show their historical tasks from previous department (as participant)

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

### Requirement 10: Backdate Restriction

**User Story:** As a system administrator, I want to restrict backdate task entry to prevent data manipulation, so that task records maintain integrity.

#### Acceptance Criteria

1. WHEN a regular employee creates a task THEN the system SHALL allow task_date selection from yesterday (1 day backdate) up to today only
2. WHEN a regular employee attempts to select task_date older than yesterday THEN the system SHALL disable those dates in the date picker
3. WHEN a regular employee attempts to submit task_date older than yesterday THEN the system SHALL reject with validation error
4. WHERE a department has active backdate permission THEN the system SHALL allow task_date selection up to the granted_until date
5. WHEN backdate permission expires (granted_until < today) THEN the system SHALL revert to default 1-day backdate limit

### Requirement 11: Backdate Permission Request

**User Story:** As an employee, I want to request backdate permission from my HOD/Manager, so that I can fill in missed task entries.

#### Acceptance Criteria

1. WHEN an employee needs to enter older tasks THEN the system SHALL provide a "Request Backdate Access" button
2. WHEN an employee submits backdate request THEN the system SHALL require: requested_date, reason
3. WHEN backdate request is submitted THEN the system SHALL set status to 'pending' and notify department head
4. WHEN backdate request is submitted THEN the system SHALL validate requested_date is not in the future
5. WHERE employee already has pending request THEN the system SHALL prevent duplicate requests

### Requirement 12: Backdate Permission Approval

**User Story:** As a department head, I want to approve/reject backdate requests from my team, so that I can control data entry integrity.

#### Acceptance Criteria

1. WHEN department head views backdate requests THEN the system SHALL show all pending requests from their department
2. WHEN department head approves request THEN the system SHALL grant backdate access only to the requester until end of current day
3. WHEN department head approves request THEN the system SHALL set granted_until = end of today (23:59:59) for that specific user
4. WHEN department head approves request THEN the system SHALL notify the requester
5. WHEN department head rejects request THEN the system SHALL require rejection_reason and notify requester
6. WHERE user has active backdate permission THEN the system SHALL show remaining time in task creation form

### Requirement 13: Backdate Permission Lifecycle

**User Story:** As a system, I want to automatically expire backdate permissions, so that access is time-limited.

#### Acceptance Criteria

1. WHEN backdate permission granted_until passes THEN the system SHALL automatically set status to 'expired'
2. WHEN checking backdate eligibility THEN the system SHALL verify user has active permission with granted_until >= current datetime
3. WHEN new backdate request is approved for same user THEN the system SHALL expire previous active permission for that user
4. WHEN employee creates task THEN the system SHALL check user's active backdate permission first before applying default limit
5. WHERE no active permission exists for user THEN the system SHALL apply default 1-day backdate limit

### Requirement 14: Backdate Request Management Page (Employee View)

**User Story:** As an employee, I want to view and manage my backdate requests, so that I can track request status and submit new requests.

#### Acceptance Criteria

1. WHEN employee accesses backdate request page THEN the system SHALL show "Request Backdate Access" button
2. WHEN employee views request list THEN the system SHALL show their own requests with: requested_date, reason, status, created_at
3. WHEN employee views request list THEN the system SHALL show status badge (pending: yellow, approved: green, rejected: red, expired: gray)
4. WHERE employee has active permission THEN the system SHALL show granted_until countdown timer
5. WHERE employee has pending request THEN the system SHALL disable "Request Backdate Access" button
6. WHEN employee clicks request row THEN the system SHALL show request details including rejection_reason if rejected

### Requirement 15: Backdate Approval Management Page (Department Head View)

**User Story:** As a department head, I want to view and manage backdate requests from my team, so that I can approve or reject them efficiently.

#### Acceptance Criteria

1. WHEN department head accesses approval page THEN the system SHALL show all pending requests from their department
2. WHEN department head views request list THEN the system SHALL show: requester_name, requested_date, reason, created_at
3. WHEN department head views request list THEN the system SHALL provide "Approve" and "Reject" action buttons per request
4. WHEN department head clicks "Approve" THEN the system SHALL confirm and grant permission immediately
5. WHEN department head clicks "Reject" THEN the system SHALL show modal requiring rejection_reason
6. WHEN department head views page THEN the system SHALL also show history of approved/rejected requests with filters

### Requirement 16: Edge Case - User Department Transfer

**User Story:** As a system, I want to handle user department transfers correctly, so that historical data remains accurate.

#### Acceptance Criteria

1. WHEN user is reassigned to different department THEN the system SHALL preserve all historical task associations
2. WHEN user views task list after transfer THEN the system SHALL show tasks from NEW department + tasks where user is participant (old department)
3. WHEN user attempts to join task from OLD department THEN the system SHALL prevent join (department mismatch)
4. WHEN user creates new task after transfer THEN the system SHALL associate task with NEW department and business unit
5. WHERE analytics are calculated THEN the system SHALL count historical tasks under original department (not transferred)

### Requirement 17: Edge Case - Multi-Day Task Duration

**User Story:** As a system, I want to handle tasks that span multiple days correctly, so that duration calculation is accurate.

#### Acceptance Criteria

1. WHEN task is started at 10 PM and completed at 2 AM next day THEN the system SHALL calculate duration as 4 hours (240 minutes)
2. WHEN task spans multiple days THEN the system SHALL use task_date = date of started_at (not completed_at)
3. WHEN task duration exceeds 24 hours THEN the system SHALL display warning badge "Long Duration" in UI
4. WHEN task duration exceeds 72 hours THEN the system SHALL display critical warning "Verify Duration" in UI
5. WHERE task has abnormal duration THEN the system SHALL allow task owner to manually adjust started_at/completed_at with audit log

### Requirement 18: Edge Case - Abnormal Task Duration Prevention

**User Story:** As a system administrator, I want to prevent unrealistic task durations, so that data quality is maintained.

#### Acceptance Criteria

1. WHEN task remains in 'in_progress' status for > 24 hours THEN the system SHALL send notification to task owner
2. WHEN task duration exceeds 168 hours (7 days) THEN the system SHALL flag task for admin review
3. WHEN viewing task with duration > 24 hours THEN the system SHALL show "Edit Duration" button for task owner
4. WHEN task owner edits duration THEN the system SHALL log original and new timestamps in activity log
5. WHERE task is flagged for review THEN the system SHALL show in admin dashboard with "Verify" action

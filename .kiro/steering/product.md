# Product Overview

## Oasis (Office Administration System)

An enterprise-grade office administration platform designed for multi-business unit operations with modular architecture supporting various business processes.

### Core Purpose

Oasis streamlines office administration workflows across multiple business units under Werkudara Group with automated routing, role-based access control, and comprehensive audit trails.

### System Architecture

Oasis is built as a modular system with the following modules:

- **Purchasing Module** (Primary Module)
  - Purchase Request (PR) - Request creation and approval workflows
  - Stock Request - Inventory request management
  - Purchasing - Procurement operations
- **Activity Tracking Module** - Employee task and workload management
- **Sales CRM Module** - Customer relationship management
- **Core Administration** - User, department, and business unit management

### Key Features

#### Core System Features
- **Universal Multi-Business Unit Architecture**: Single codebase supporting multiple business units with dynamic routing and context switching
- **Modular Design**: Extensible module system for different business processes
- **Role-Based Access Control**: Hierarchical permissions using Spatie Permission package
- **Activity Logging**: Comprehensive audit trails using Spatie Activity Log
- **Real-time Dashboard**: Live statistics, charts, and activity feeds with date range filtering

#### Purchasing Module Features

- **Purchase Request (PR) Management**:
  - Automated approval workflows with rule-based routing
  - Sequential PR numbering with business unit-specific formats
  - QR code verification for document tracking
  - Multi-level approval chains (sequential/parallel)
  - Offline approval support for paper-based workflows
  - Email notifications for approval requests
  - Item-level tracking with images and specifications
  - Category-based organization
  - Draft, submit, approve, reject, void workflows
  - Real-time status tracking and history

- **Stock Request (ST) Management** *(In Development)*:
  - Automated approval workflows with rule-based routing
  - Sequential ST numbering with business unit-specific formats
  - QR code verification for document tracking
  - Multi-level approval chains (sequential/parallel)
  - Offline approval support for paper-based workflows
  - Email notifications for approval requests
  - Item-level tracking with images and specifications
  - Category-based organization
  - Draft, submit, approve, reject, void workflows
  - Real-time status tracking and history

- **Purchasing Operations** *(Planned)*:
  - Procurement workflow management
  - Vendor management
  - Purchase order processing

#### Activity Tracking Module Features

- **Employee Task Management**:
  - Create and track work activities/tasks
  - Collaborative tasks - multiple users can join and work on the same task
  - Shared status and timestamps across all participants
  - Department-scoped visibility - users see tasks from their department
  - Task lifecycle: planned → in_progress → completed/cancelled
  - Due date tracking with overdue alerts
  - File attachments support (images, PDF, documents)

- **Activity Types & Sub-Activities**:
  - Configurable activity categories (Meeting, Web Development, Event, etc.)
  - Sub-activities for detailed categorization
  - Color-coded activity types for visual organization
  - Admin-managed activity type configuration

- **Analytics & Dashboards**:
  - Personal Dashboard: Individual task summary, hours worked, activity breakdown
  - Department Analytics: Team workload, completion rates, overdue tracking
  - Business Unit Analytics: Cross-department productivity trends
  - Date range filtering for all analytics views

- **Collaboration Features**:
  - Join existing department tasks
  - Shared task status - any participant can start/complete
  - Participant tracking with owner designation
  - Activity logging for audit trails

### Business Units

#### Hierarchical Structure

**Parent Holding Company:**
- **WG** (Werkudara Group) - Parent holding company

**Child Business Units:**
- **WNS** (Werkudara Nirwana Sakti)
- **UK** (Utama Kalapana)
- **MRP** (Maharaja Pratama)

#### Business Unit Management

- Child business units are **dynamic and configurable** - new units can be added or removed
- Users can be assigned to **multiple business units** with different roles/positions
- Each user has a **primary business unit** assignment (not fixed to WNS)
- Users can **switch between assigned business units** via BusinessUnitSwitcher component
- All modules (PR, ST, etc.) are **business unit-aware** and filter data based on active context
- Approval workflows, numbering sequences, and permissions are **business unit-specific**

### User Roles & Positions

- Super Admin (full system access)
- General Manager, Director, CEO (top management - reports access)
- Finance Manager (financial oversight)
- Department Heads (approval authority)
- Regular Users (PR/ST creators)

### Workflow States

#### Purchase/Stock Request States
- **draft**: Initial creation, editable
- **submitted**: Sent for approval
- **in_approval**: Currently in approval chain
- **approved**: Fully approved
- **rejected**: Denied by approver
- **voided**: Cancelled/invalidated

#### Task States (Activity Module)
- **planned**: Task created, not yet started
- **in_progress**: Task actively being worked on
- **completed**: Task finished successfully
- **cancelled**: Task cancelled by owner

### Target Users

Enterprise organizations requiring:
- Structured purchase and stock request workflows with multi-level approvals
- Employee task tracking and workload management
- Audit compliance and cross-business unit operations
- Collaborative work tracking and productivity analytics

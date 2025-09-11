# Product Overview

This is a **Purchase Request Management System** built for enterprise use, specifically designed for WNS (Werkudara Nusantara Sejahtera) business operations.

## Core Features

- **User Management**: Multi-role user system with super admin, admin, and regular user roles
- **Purchase Request Processing**: Complete workflow for creating, approving, and tracking purchase requests
- **Business Unit Management**: Hierarchical organization structure with business units, departments, and positions
- **Role-Based Access Control**: Granular permissions using Spatie Permission package
- **Activity Logging**: Comprehensive audit trail for all user actions

## Business Context

The system manages purchase requests across multiple business units within the WNS organization. Users can create purchase requests that flow through approval workflows based on their department and business unit assignments.

## Key Business Rules

- Users must be assigned to business units to access most functionality
- Purchase requests require approval workflows based on organizational hierarchy
- Super admins have full system access, while regular users have limited scope based on their business unit assignments
- All user actions are logged for audit purposes
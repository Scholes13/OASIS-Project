# Implementation Plan

- [-] 1. Create Migration Service for Data Consolidation




  - [x] 1.1 Create ActivityTypeMigrationService class


    - Create service at `app/Services/Modules/Activity/ActivityTypeMigrationService.php`
    - Implement `consolidateActivityTypes()` method to identify unique names
    - Implement `consolidateSubActivities()` method
    - Implement `updateTaskReferences()` method
    - Implement `cleanupOrphans()` method
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [ ]* 1.2 Write property test for migration consolidation
    - **Property 6: Migration consolidation produces unique names**
    - **Validates: Requirements 4.1, 4.2**

  - [ ]* 1.3 Write property test for task reference integrity
    - **Property 7: Task references remain valid after migration**
    - **Validates: Requirements 4.3**

  - [ ]* 1.4 Write property test for department assignment preservation
    - **Property 8: Department assignments preserved after migration**
    - **Validates: Requirements 4.4**

- [x] 2. Create Artisan Command for Migration






  - [x] 2.1 Create migrate:activity-types command

    - Create command at `app/Console/Commands/MigrateActivityTypesCommand.php`
    - Add dry-run option to preview changes
    - Add force option to skip confirmation
    - Display progress and summary
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [x] 3. Checkpoint - Ensure migration service works





  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Update ActivityTypeController for Global Management





  - [x] 4.1 Update index method for super admin


    - Remove department filter for super admin
    - Show all activity types globally
    - Add department count and task count
    - Add business unit filter option
    - _Requirements: 1.1, 6.1, 6.2, 6.3_


  - [x] 4.2 Update store method for global activity types

    - Generate code without department prefix
    - Remove department_id requirement
    - _Requirements: 1.2_


  - [x] 4.3 Add assignDepartments method

    - Accept array of department IDs
    - Create pivot records
    - Support bulk assignment
    - _Requirements: 3.2, 3.5_


  - [x] 4.4 Add removeDepartment method

    - Check for existing tasks before removal
    - Return error if tasks exist
    - _Requirements: 3.3_



  - [x] 4.5 Update destroy method with proper validation
    - Check for sub activities
    - Check for tasks
    - Return appropriate error messages
    - _Requirements: 1.4_

  - [ ]* 4.6 Write property test for activity type code format
    - **Property 1: Activity type codes have no department prefix**
    - **Validates: Requirements 1.1, 1.2**

  - [ ]* 4.7 Write property test for deletion constraint
    - **Property 2: Deletion constraint for activity types with dependencies**
    - **Validates: Requirements 1.4**

  - [ ]* 4.8 Write property test for department assignment
    - **Property 4: Department assignment creates pivot record**
    - **Validates: Requirements 3.2**

  - [ ]* 4.9 Write property test for removal constraint
    - **Property 5: Removal constraint for assigned activity types with tasks**
    - **Validates: Requirements 3.3**

- [x] 5. Update SubActivityController for Global Management





  - [x] 5.1 Update index method


    - Show all sub activities globally
    - Group by activity type
    - Add activity type filter
    - _Requirements: 2.1, 2.3_


  - [x] 5.2 Update store method

    - Link to activity type without department context
    - Generate code without department prefix
    - _Requirements: 2.2_

  - [ ]* 5.3 Write property test for sub activity filter
    - **Property 3: Sub activity filter returns correct results**
    - **Validates: Requirements 2.3**

- [x] 6. Checkpoint - Ensure controller updates work





  - Ensure all tests pass, ask the user if questions arise.

- [x] 7. Update Frontend Components





  - [x] 7.1 Update Activity Types Index page


    - Remove department dropdown for super admin
    - Add business unit filter
    - Show department count column
    - Show task count column
    - _Requirements: 1.1, 6.1, 6.2, 6.3_


  - [x] 7.2 Update Activity Types Create/Edit form

    - Remove department selection
    - Keep name and color fields
    - _Requirements: 1.2, 1.3_


  - [x] 7.3 Create Department Assignment UI

    - Add "Assign to Departments" button on activity type row
    - Create modal with department checkboxes
    - Support bulk selection
    - Show current assignments
    - _Requirements: 3.1, 3.2, 3.5_


  - [x] 7.4 Update Sub Activities Index page

    - Add activity type filter dropdown
    - Show parent activity type in list
    - _Requirements: 2.1, 2.3, 2.4_

- [x] 8. Update Employee Task Creation






  - [x] 8.1 Update task creation to filter by department assignment

    - Query activity types via department_activity_types pivot
    - Order by department's sort_order
    - _Requirements: 5.1, 5.3_

  - [ ]* 8.2 Write property test for employee activity type visibility
    - **Property 9: Employee sees only assigned activity types**
    - **Validates: Requirements 5.1**

- [x] 9. Update Seeders





  - [x] 9.1 Create new GlobalActivityTypeSeeder


    - Create master activity types without prefix
    - Create master sub activities
    - _Requirements: 1.2, 2.2_



  - [ ] 9.2 Create DepartmentActivityAssignmentSeeder
    - Assign activity types to departments
    - Set default activity types per department


    - _Requirements: 3.2, 3.4_

  - [ ] 9.3 Deprecate old WNS/MRP seeders
    - Add deprecation notice
    - Keep for reference
    - _Requirements: 4.5_

- [ ] 10. Final Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

# Implementation Plan

- [x] 1. Add User Model Helper Methods





  - [x] 1.1 Implement `getCurrentDepartmentId()` method


    - Return `session('current_department_id')` with fallback to `primary_department_id`
    - _Requirements: 5.1, 5.4_
  - [x] 1.2 Implement `getDepartmentsInCurrentBusinessUnit()` method

    - Query `user_business_units` where `business_unit_id` equals session BU
    - Return collection of departments with id, name, code
    - _Requirements: 5.2_
  - [x] 1.3 Implement `hasMultipleDepartmentsInCurrentBusinessUnit()` method

    - Return boolean based on department count > 1
    - _Requirements: 5.3_
  - [ ]* 1.4 Write property tests for User model helper methods
    - **Property 7: getCurrentDepartmentId Fallback**
    - **Property 8: getDepartmentsInCurrentBusinessUnit Accuracy**
    - **Property 9: hasMultipleDepartmentsInCurrentBusinessUnit Correctness**
    - **Validates: Requirements 5.1, 5.2, 5.3, 5.4**

- [x] 2. Implement Session Department Initialization





  - [x] 2.1 Update login flow to set `current_department_id` in session


    - Modify `LoginController` or auth handler to set department session
    - Set to primary department in primary BU
    - _Requirements: 1.1_
  - [x] 2.2 Update `BusinessUnitController::switch` to also update department session


    - After BU switch, find user's primary department in new BU
    - Set `current_department_id`, `current_department_name`, `current_department_code`
    - _Requirements: 1.2, 1.3_
  - [ ]* 2.3 Write property tests for session initialization
    - **Property 1: Session Department Initialization**
    - **Property 2: Single Department Auto-Selection**
    - **Validates: Requirements 1.1, 1.2, 1.3, 1.4**

- [x] 3. Create Department Switch Endpoint






  - [x] 3.1 Create `DepartmentController` with `switch` method

    - Create `app/Http/Controllers/Api/DepartmentController.php`
    - Validate department exists and user has assignment in current BU
    - Update session with new department context
    - Return redirect back (Inertia pattern)
    - _Requirements: 2.3_

  - [x] 3.2 Add route for department switch

    - Add `POST /api/department/switch` route in `routes/web.php`
    - _Requirements: 2.3_
  - [ ]* 3.3 Write property test for department switch
    - **Property 3: Department Switch Updates Session**
    - **Validates: Requirements 2.3**

- [x] 4. Update HandleInertiaRequests Middleware





  - [x] 4.1 Add `currentDepartment` to shared props


    - Include id, name, code from session
    - _Requirements: 4.1, 4.3_
  - [x] 4.2 Add `availableDepartments` to shared props

    - Only include if user has multiple departments in current BU
    - Use `getDepartmentsInCurrentBusinessUnit()` method
    - _Requirements: 2.1, 2.2_
  - [x] 4.3 Update `auth.user` props to include current department info

    - Add `current_department_id` to user props
    - _Requirements: 4.1, 4.2_

- [x] 5. Create DepartmentSwitcher React Component




  - [x] 5.1 Create `DepartmentSwitcher.tsx` component


    - Create `resources/js/inertia/components/DepartmentSwitcher.tsx`
    - Render dropdown with current department and available options
    - Only render if `availableDepartments.length > 1`
    - Call `POST /api/department/switch` on selection
    - Use Inertia router for request
    - _Requirements: 2.1, 2.2, 2.4, 2.5_
  - [x] 5.2 Integrate DepartmentSwitcher into AppLayout


    - Add component to header/sidebar area near BU switcher
    - _Requirements: 2.1_


- [x] 6. Checkpoint - Ensure all tests pass




  - Ensure all tests pass, ask the user if questions arise.

- [x] 7. Update Activity Module to Use Session Department





  - [x] 7.1 Update `ActivityInertiaController::index` to use session department


    - Replace `$user->primary_department_id` with `$user->getCurrentDepartmentId()`
    - Update task list query filtering
    - _Requirements: 3.2_
  - [x] 7.2 Update `ActivityInertiaController::store` to use session department


    - Use `session('current_department_id')` for new task's department_id
    - _Requirements: 3.1_
  - [x] 7.3 Update `ActivityInertiaController::create` and `edit` for department users


    - Query users in `current_department_id` for participant suggestions
    - _Requirements: 3.5_
  - [x] 7.4 Update `ActivityInertiaController::department` method


    - Use session department for department tasks view
    - _Requirements: 3.4_
  - [x] 7.5 Update analytics methods to use session department


    - Update `getDepartmentStats`, `getDepartmentVisuals` calls
    - _Requirements: 3.3_
  - [x] 7.6 Update `TaskService::create` to use session department


    - Replace `$user->primary_department_id` with session value
    - _Requirements: 3.1_
  - [ ]* 7.7 Write property tests for Activity module integration
    - **Property 4: Task Creation Uses Session Department**
    - **Property 5: Task List Filtering by Department**
    - **Property 6: Department Users Query**
    - **Validates: Requirements 3.1, 3.2, 3.4, 3.5**

- [x] 8. Update Related Services





  - [x] 8.1 Update `ActivityTypePrioritizationService` to use session department


    - Replace `$user->primary_department_id` with `$user->getCurrentDepartmentId()`
    - _Requirements: 3.3_
  - [x] 8.2 Update `BackdatePermissionService` to use session department


    - Use session department for backdate permission requests
    - _Requirements: 3.1_

- [x] 9. Final Checkpoint - Ensure all tests pass





  - Ensure all tests pass, ask the user if questions arise.

# Implementation Plan

- [x] 1. Create database migration and model





  - [x] 1.1 Create migration for admin_task_item_realizations table


    - Create table with all columns: admin_task_id, item_type, item_id, item_name, quantity, unit, estimated/realized prices, savings, suppliers
    - Add foreign key to admin_tasks with cascade delete
    - Add indexes for admin_task_id and item_type/item_id
    - _Requirements: 4.1, 4.2, 4.3_
  - [x] 1.2 Create AdminTaskItemRealization model


    - Define fillable, casts, and relationships
    - Add adminTask() belongsTo relationship
    - Add item() morphTo relationship for PrItem/StItem
    - _Requirements: 4.1, 4.2_
  - [ ]* 1.3 Write property test for cascade delete
    - **Property 6: Cascade Delete Integrity**
    - **Validates: Requirements 4.3**

- [x] 2. Modify TaskList component for item-level completion





  - [x] 2.1 Add properties for item realization data


    - Add $completingTaskItems array to hold loaded items
    - Add $itemRealizations array to hold user input
    - _Requirements: 1.1, 1.2_
  - [x] 2.2 Implement loadTaskItems method


    - Load PR/ST items when modal opens
    - Pre-fill realized prices with estimated prices
    - Pre-fill realized supplier with original supplier
    - _Requirements: 1.1, 1.2, 2.1, 6.1_
  - [ ]* 2.3 Write property test for pre-fill values
    - **Property 3: Pre-fill Default Values**
    - **Validates: Requirements 2.1**
  - [x] 2.4 Implement updateItemRealization method


    - Handle real-time updates when admin edits values
    - Recalculate item total when unit price changes
    - Recalculate grand total when any item changes
    - _Requirements: 1.3, 2.2, 2.3_
  - [ ]* 2.5 Write property test for item total calculation
    - **Property 1: Item Total Calculation Consistency**
    - **Validates: Requirements 1.3, 2.2**
  - [ ]* 2.6 Write property test for grand total calculation
    - **Property 2: Grand Total Calculation Consistency**
    - **Validates: Requirements 1.5, 2.3**
  - [x] 2.7 Implement completeTaskWithItems method


    - Validate all item realizations
    - Calculate savings per item
    - Store AdminTaskItemRealization records
    - Update AdminTask with grand totals
    - _Requirements: 1.4, 1.5, 4.1, 4.2, 5.1, 5.2, 6.2, 6.3_
  - [ ]* 2.8 Write property test for savings calculation
    - **Property 5: Savings Calculation Correctness**
    - **Validates: Requirements 5.1, 5.2**
  - [ ]* 2.9 Write property test for realization persistence
    - **Property 4: Realization Persistence Round-Trip**
    - **Validates: Requirements 4.1, 4.2**
  - [ ]* 2.10 Write property test for supplier persistence
    - **Property 7: Supplier Persistence**
    - **Validates: Requirements 6.2, 6.3**

- [x] 3. Update task-list.blade.php completion modal





  - [x] 3.1 Redesign completion modal to show items table


    - Display item name, quantity, unit, estimated unit price, estimated total
    - Add input fields for realized unit price and realized supplier
    - Show calculated realized total per item
    - Show grand total at bottom
    - _Requirements: 1.1, 1.2, 1.3, 6.1_

  - [x] 3.2 Add real-time calculation with Alpine.js

    - Update item total when unit price changes
    - Update grand total when any item changes
    - _Requirements: 2.2, 2.3_

  - [x] 3.3 Add savings display with color coding

    - Show savings amount and percentage per item
    - Green for positive savings (realized < estimated)
    - Red for negative savings (realized > estimated)
    - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [x] 4. Checkpoint - Ensure completion flow works





  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Modify PersonalTaskHistory component for detail view





  - [x] 5.1 Add properties for detail modal


    - Add $showDetailModal, $detailTaskId, $detailItems
    - _Requirements: 3.2, 3.3_
  - [x] 5.2 Implement openDetailModal method


    - Load AdminTaskItemRealization records for task
    - _Requirements: 3.3, 3.4_
  - [x] 5.3 Implement closeDetailModal method


    - Reset modal state
    - _Requirements: 3.3_
  - [ ]* 5.4 Write property test for detail button visibility
    - **Property 8: Detail Button Visibility**
    - **Validates: Requirements 3.2**

- [x] 6. Update personal-task-history.blade.php





  - [x] 6.1 Add Detail button to task rows


    - Show button only for tasks with item realization data
    - _Requirements: 3.2_

  - [x] 6.2 Create detail modal view

    - Display table with all item realization data
    - Show item name, quantity, unit, estimated prices, realized prices
    - Show savings with color coding
    - Show original vs realized supplier if different
    - _Requirements: 3.3, 3.4, 5.3, 5.4, 6.4_

- [x] 7. Final Checkpoint - Ensure all tests pass





  - Ensure all tests pass, ask the user if questions arise.

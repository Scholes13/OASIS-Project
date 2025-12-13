# Requirements Document

## Introduction

This feature enhances the Purchasing Admin task completion workflow by allowing administrators to input realized prices per item instead of only the grand total. The current implementation only captures the total realized price, but business needs require tracking realization at the item level for better cost analysis and reporting. The personal task history report will continue to show grand total as the primary view, with a detail button to view per-item realization breakdown.

## Glossary

- **Admin Task**: A task assigned to purchasing administrators for follow-up on approved Purchase Requests or Stock Requests
- **Realization**: The actual price paid/negotiated by the admin for procurement items
- **Estimated Price**: The original price submitted by the requester in the PR/ST
- **Realized Price**: The actual price achieved by the admin during procurement
- **Savings**: The difference between estimated and realized prices
- **PR Item**: Individual line item in a Purchase Request
- **ST Item**: Individual line item in a Stock Request
- **Task Completion Modal**: The dialog shown when admin completes a task

## Requirements

### Requirement 1

**User Story:** As a purchasing admin, I want to input realized prices for each item when completing a task, so that I can track actual procurement costs at the item level.

#### Acceptance Criteria

1. WHEN an admin opens the task completion modal THEN the System SHALL display all items from the associated PR/ST with their estimated prices
2. WHEN displaying items in the completion modal THEN the System SHALL show item name, quantity, unit, estimated unit price, and estimated total price for each item
3. WHEN an admin inputs a realized unit price for an item THEN the System SHALL automatically calculate the realized total price for that item
4. WHEN an admin completes the task THEN the System SHALL store the realized unit price and realized total price for each item
5. WHEN an admin completes the task THEN the System SHALL calculate the grand total realized price as the sum of all item realized prices

### Requirement 2

**User Story:** As a purchasing admin, I want the system to pre-fill realized prices with estimated prices, so that I can quickly complete tasks where prices remain unchanged.

#### Acceptance Criteria

1. WHEN the task completion modal opens THEN the System SHALL pre-fill each item's realized unit price with its estimated unit price
2. WHEN an admin modifies a realized unit price THEN the System SHALL recalculate only that item's realized total price
3. WHEN any item's realized price changes THEN the System SHALL update the grand total realized price immediately

### Requirement 3

**User Story:** As a purchasing admin, I want to view item-level realization details in my task history, so that I can review past procurement performance at the item level.

#### Acceptance Criteria

1. WHEN viewing the personal task history page THEN the System SHALL display the grand total realized price as the primary value
2. WHEN a task has item-level realization data THEN the System SHALL display a "Detail" button in the task row
3. WHEN an admin clicks the "Detail" button THEN the System SHALL display a modal showing all items with their estimated and realized prices
4. WHEN displaying item details THEN the System SHALL show item name, quantity, unit, estimated unit price, realized unit price, estimated total, realized total, and savings per item

### Requirement 4

**User Story:** As a system administrator, I want item realization data to be stored persistently, so that historical data is preserved for reporting and auditing.

#### Acceptance Criteria

1. WHEN an admin completes a task with item realization THEN the System SHALL store realized_unit_price and realized_total_price for each item in the database
2. WHEN storing item realization THEN the System SHALL calculate and store savings_amount and savings_percentage per item
3. WHEN the associated PR/ST is deleted THEN the System SHALL cascade delete the item realization records

### Requirement 5

**User Story:** As a purchasing admin, I want to see savings calculations per item, so that I can identify which items contributed most to cost savings.

#### Acceptance Criteria

1. WHEN displaying item realization details THEN the System SHALL show savings amount (estimated - realized) for each item
2. WHEN displaying item realization details THEN the System SHALL show savings percentage ((estimated - realized) / estimated * 100) for each item
3. WHEN an item has negative savings (realized > estimated) THEN the System SHALL display the value in red color
4. WHEN an item has positive savings (realized < estimated) THEN the System SHALL display the value in green color

### Requirement 6

**User Story:** As a purchasing admin, I want to modify the supplier name for each item during task completion, so that I can record the actual supplier used for procurement.

#### Acceptance Criteria

1. WHEN the task completion modal displays items THEN the System SHALL show the original supplier name submitted by the requester as editable field
2. WHEN an admin modifies the supplier name for an item THEN the System SHALL store the new supplier name as the realized supplier
3. WHEN storing item realization THEN the System SHALL preserve both the original supplier (from PR/ST) and the realized supplier (from admin input)
4. WHEN displaying item realization details in history THEN the System SHALL show both original and realized supplier names if they differ

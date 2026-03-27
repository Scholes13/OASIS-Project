-- =============================================================================
-- Add Missing Reporting Activity Types for SO, HR, TEP departments
-- Database: numberwg
-- Date: 2026-03-05
--
-- This script adds "Reporting" as an Activity Type for 3 departments
-- at Werkudara Nirwana Sakti (BU #2) that are missing it:
--   - SO (Sales Operation) - Dept #11
--   - HR (Human Resource) - Dept #7
--   - TEP (Tour & Event Planning) - Dept #9
--
-- Pattern follows existing Reporting ATs (ACC_REPORTING, CFC_REPORTING, etc.)
-- Each gets: 1 activity type + 1 sub-activity + 1 dept pivot + 1 sub pivot
-- =============================================================================

-- Safety: Use transaction
START TRANSACTION;

-- =============================================================================
-- STEP 1: Create Activity Types in employee_activity_types (master table)
-- =============================================================================

INSERT INTO `employee_activity_types` (`code`, `name`, `color`, `is_active`, `sort_order`, `created_at`, `updated_at`)
VALUES
    ('SO_REPORTING', 'Reporting', '#6366f1', 1, 99, NOW(), NOW()),
    ('HR_REPORTING', 'Reporting', '#6366f1', 1, 99, NOW(), NOW()),
    ('TEP_REPORTING', 'Reporting', '#6366f1', 1, 99, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `is_active` = 1,
    `updated_at` = NOW();

-- Store the IDs for later use
SET @so_reporting_id = (SELECT `id` FROM `employee_activity_types` WHERE `code` = 'SO_REPORTING');
SET @hr_reporting_id = (SELECT `id` FROM `employee_activity_types` WHERE `code` = 'HR_REPORTING');
SET @tep_reporting_id = (SELECT `id` FROM `employee_activity_types` WHERE `code` = 'TEP_REPORTING');

-- Verify
SELECT 'Activity Types Created' AS step, @so_reporting_id AS so_id, @hr_reporting_id AS hr_id, @tep_reporting_id AS tep_id;

-- =============================================================================
-- STEP 2: Create Sub-Activities in employee_sub_activities
-- =============================================================================

INSERT INTO `employee_sub_activities` (`activity_type_id`, `code`, `name`, `is_active`, `sort_order`, `created_at`, `updated_at`)
VALUES
    (@so_reporting_id, 'SO_REPORTING', 'Reporting', 1, 1, NOW(), NOW()),
    (@hr_reporting_id, 'HR_REPORTING', 'Reporting', 1, 1, NOW(), NOW()),
    (@tep_reporting_id, 'TEP_REPORTING', 'Reporting', 1, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `is_active` = 1,
    `updated_at` = NOW();

-- Store sub-activity IDs
SET @so_sub_id = (SELECT `id` FROM `employee_sub_activities` WHERE `code` = 'SO_REPORTING' AND `activity_type_id` = @so_reporting_id);
SET @hr_sub_id = (SELECT `id` FROM `employee_sub_activities` WHERE `code` = 'HR_REPORTING' AND `activity_type_id` = @hr_reporting_id);
SET @tep_sub_id = (SELECT `id` FROM `employee_sub_activities` WHERE `code` = 'TEP_REPORTING' AND `activity_type_id` = @tep_reporting_id);

-- Verify
SELECT 'Sub-Activities Created' AS step, @so_sub_id AS so_sub, @hr_sub_id AS hr_sub, @tep_sub_id AS tep_sub;

-- =============================================================================
-- STEP 3: Assign Activity Types to Departments (department_activity_types pivot)
-- Department IDs: SO=#11, HR=#7, TEP=#9 (all in WNS BU #2)
-- =============================================================================

-- SO Department (ID: 11) - current max sort_order = 15, so new = 16
INSERT INTO `department_activity_types` (`department_id`, `activity_type_id`, `is_default`, `sort_order`, `created_at`, `updated_at`)
SELECT 11, @so_reporting_id, 0,
    COALESCE((SELECT MAX(`sort_order`) FROM `department_activity_types` WHERE `department_id` = 11), 0) + 1,
    NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `department_activity_types`
    WHERE `department_id` = 11 AND `activity_type_id` = @so_reporting_id
);

-- HR Department (ID: 7) - current max sort_order = 13, so new = 14
INSERT INTO `department_activity_types` (`department_id`, `activity_type_id`, `is_default`, `sort_order`, `created_at`, `updated_at`)
SELECT 7, @hr_reporting_id, 0,
    COALESCE((SELECT MAX(`sort_order`) FROM `department_activity_types` WHERE `department_id` = 7), 0) + 1,
    NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `department_activity_types`
    WHERE `department_id` = 7 AND `activity_type_id` = @hr_reporting_id
);

-- TEP Department (ID: 9) - current max sort_order = 14, so new = 15
INSERT INTO `department_activity_types` (`department_id`, `activity_type_id`, `is_default`, `sort_order`, `created_at`, `updated_at`)
SELECT 9, @tep_reporting_id, 0,
    COALESCE((SELECT MAX(`sort_order`) FROM `department_activity_types` WHERE `department_id` = 9), 0) + 1,
    NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `department_activity_types`
    WHERE `department_id` = 9 AND `activity_type_id` = @tep_reporting_id
);

-- =============================================================================
-- STEP 4: Assign Sub-Activities to Departments (department_sub_activities pivot)
-- =============================================================================

-- SO Department (ID: 11)
INSERT INTO `department_sub_activities` (`department_id`, `sub_activity_id`, `is_default`, `sort_order`, `created_at`, `updated_at`)
SELECT 11, @so_sub_id, 0,
    COALESCE((SELECT MAX(`sort_order`) FROM `department_sub_activities` WHERE `department_id` = 11), 0) + 1,
    NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `department_sub_activities`
    WHERE `department_id` = 11 AND `sub_activity_id` = @so_sub_id
);

-- HR Department (ID: 7)
INSERT INTO `department_sub_activities` (`department_id`, `sub_activity_id`, `is_default`, `sort_order`, `created_at`, `updated_at`)
SELECT 7, @hr_sub_id, 0,
    COALESCE((SELECT MAX(`sort_order`) FROM `department_sub_activities` WHERE `department_id` = 7), 0) + 1,
    NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `department_sub_activities`
    WHERE `department_id` = 7 AND `sub_activity_id` = @hr_sub_id
);

-- TEP Department (ID: 9)
INSERT INTO `department_sub_activities` (`department_id`, `sub_activity_id`, `is_default`, `sort_order`, `created_at`, `updated_at`)
SELECT 9, @tep_sub_id, 0,
    COALESCE((SELECT MAX(`sort_order`) FROM `department_sub_activities` WHERE `department_id` = 9), 0) + 1,
    NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `department_sub_activities`
    WHERE `department_id` = 9 AND `sub_activity_id` = @tep_sub_id
);

-- =============================================================================
-- STEP 5: Verification
-- =============================================================================

SELECT
    '=== VERIFICATION ===' AS info;

-- Check Activity Types
SELECT
    eat.id, eat.code, eat.name, eat.is_active
FROM `employee_activity_types` eat
WHERE eat.code IN ('SO_REPORTING', 'HR_REPORTING', 'TEP_REPORTING')
ORDER BY eat.code;

-- Check Sub-Activities
SELECT
    esa.id, esa.activity_type_id, esa.code, esa.name, esa.is_active
FROM `employee_sub_activities` esa
WHERE esa.code IN ('SO_REPORTING', 'HR_REPORTING', 'TEP_REPORTING')
ORDER BY esa.code;

-- Check Department Assignments
SELECT
    d.code AS dept_code, d.name AS dept_name,
    eat.code AS activity_code, eat.name AS activity_name,
    dat.sort_order, dat.is_default
FROM `department_activity_types` dat
JOIN `departments` d ON d.id = dat.department_id
JOIN `employee_activity_types` eat ON eat.id = dat.activity_type_id
WHERE d.id IN (7, 9, 11) AND eat.code LIKE '%REPORTING%'
ORDER BY d.code;

-- Check Department Sub-Activity Assignments
SELECT
    d.code AS dept_code,
    esa.code AS sub_code, esa.name AS sub_name,
    dsa.sort_order, dsa.is_default
FROM `department_sub_activities` dsa
JOIN `departments` d ON d.id = dsa.department_id
JOIN `employee_sub_activities` esa ON esa.id = dsa.sub_activity_id
WHERE d.id IN (7, 9, 11) AND esa.code LIKE '%REPORTING%'
ORDER BY d.code;

COMMIT;

SELECT '✅ DONE - Reporting activity types added for SO, HR, TEP' AS result;

# Database Index Standards for New Modules

**Version**: 1.0  
**Created**: October 10, 2025  
**Purpose**: Standardize performance index patterns across all modules

---

## 📋 Overview

This document defines the **standard index pattern** that MUST be applied to every new module in the Numbering System. Following this pattern ensures consistent performance across all modules.

---

## ✅ Standard 5-Index Pattern

Every new transactional module table MUST have these 5 composite indexes:

### 1. **User Ownership Index**
```php
$table->index(['user_id', 'status'], 'idx_{module}_user_status');
```
**Purpose**: Query user's records filtered by status  
**Example Queries**:
- "My active invoices"
- "My draft purchase requests"
- "My pending reimbursements"

### 2. **Business Unit Reports Index**
```php
$table->index(['business_unit_id', 'status', 'created_at'], 'idx_{module}_bu_status_date');
```
**Purpose**: Business unit reporting with date filtering  
**Example Queries**:
- "All WNS invoices in Q3 2025"
- "Approved PRs for UT this month"
- "Pending assets for MRP department"

### 3. **Department Reports Index**
```php
$table->index(['department_id', 'status', 'created_at'], 'idx_{module}_dept_status_date');
```
**Purpose**: Department-level reporting and tracking  
**Example Queries**:
- "IT department's invoices this quarter"
- "Finance department's approved PRs"
- "HR's reimbursement requests this month"

### 4. **Multi-BU User Context Index**
```php
$table->index(['user_id', 'business_unit_id', 'status'], 'idx_{module}_user_bu_status');
```
**Purpose**: User's records in specific business unit (for multi-BU users)  
**Example Queries**:
- "My PRs in WNS business unit"
- "My invoices for UT division"
- "My assets registered under MRP"

### 5. **Status Timeline Index**
```php
$table->index(['status', 'created_at'], 'idx_{module}_status_date');
```
**Purpose**: Status-based queries with chronological ordering  
**Example Queries**:
- "All approved records, newest first"
- "Recent rejections"
- "Voided transactions timeline"

---

## 📦 Complete Migration Template

Copy this template when creating a new module:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Replace {module_table} with actual table name (e.g., invoices, assets)
        // Replace {module} with module code (e.g., invoice, asset)
        
        Schema::table('{module_table}', function (Blueprint $table) {
            // 1. User ownership queries
            $table->index(['user_id', 'status'], 'idx_{module}_user_status');
            
            // 2. Business unit reports
            $table->index(['business_unit_id', 'status', 'created_at'], 'idx_{module}_bu_status_date');
            
            // 3. Department reports
            $table->index(['department_id', 'status', 'created_at'], 'idx_{module}_dept_status_date');
            
            // 4. Multi-BU user context
            $table->index(['user_id', 'business_unit_id', 'status'], 'idx_{module}_user_bu_status');
            
            // 5. Status timeline
            $table->index(['status', 'created_at'], 'idx_{module}_status_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('{module_table}', function (Blueprint $table) {
            $table->dropIndex('idx_{module}_user_status');
            $table->dropIndex('idx_{module}_bu_status_date');
            $table->dropIndex('idx_{module}_dept_status_date');
            $table->dropIndex('idx_{module}_user_bu_status');
            $table->dropIndex('idx_{module}_status_date');
        });
    }
};
```

---

## 📚 Real Examples

### Example 1: Invoice Module

```php
// Migration: add_performance_indexes_to_invoices_table.php
public function up(): void
{
    Schema::table('invoices', function (Blueprint $table) {
        $table->index(['user_id', 'status'], 'idx_invoice_user_status');
        $table->index(['business_unit_id', 'status', 'created_at'], 'idx_invoice_bu_status_date');
        $table->index(['department_id', 'status', 'created_at'], 'idx_invoice_dept_status_date');
        $table->index(['user_id', 'business_unit_id', 'status'], 'idx_invoice_user_bu_status');
        $table->index(['status', 'created_at'], 'idx_invoice_status_date');
    });
}
```

**Query Examples**:
```php
// Uses idx_invoice_user_status
Invoice::where('user_id', auth()->id())
    ->where('status', 'pending')
    ->get();

// Uses idx_invoice_bu_status_date
Invoice::where('business_unit_id', session('current_business_unit_id'))
    ->where('status', 'approved')
    ->whereBetween('created_at', [$startDate, $endDate])
    ->orderBy('created_at', 'desc')
    ->get();

// Uses idx_invoice_dept_status_date
Invoice::where('department_id', $deptId)
    ->whereIn('status', ['pending', 'approved'])
    ->latest('created_at')
    ->get();
```

### Example 2: Asset Management Module

```php
// Migration: add_performance_indexes_to_assets_table.php
public function up(): void
{
    Schema::table('assets', function (Blueprint $table) {
        $table->index(['user_id', 'status'], 'idx_asset_user_status');
        $table->index(['business_unit_id', 'status', 'created_at'], 'idx_asset_bu_status_date');
        $table->index(['department_id', 'status', 'created_at'], 'idx_asset_dept_status_date');
        $table->index(['user_id', 'business_unit_id', 'status'], 'idx_asset_user_bu_status');
        $table->index(['status', 'created_at'], 'idx_asset_status_date');
    });
}
```

### Example 3: Reimbursement Module

```php
// Migration: add_performance_indexes_to_reimbursements_table.php
public function up(): void
{
    Schema::table('reimbursements', function (Blueprint $table) {
        $table->index(['user_id', 'status'], 'idx_reimburse_user_status');
        $table->index(['business_unit_id', 'status', 'created_at'], 'idx_reimburse_bu_status_date');
        $table->index(['department_id', 'status', 'created_at'], 'idx_reimburse_dept_status_date');
        $table->index(['user_id', 'business_unit_id', 'status'], 'idx_reimburse_user_bu_status');
        $table->index(['status', 'created_at'], 'idx_reimburse_status_date');
    });
}
```

---

## 🔧 Additional Indexes for Specific Cases

### Approval Tables

If your module has an approval workflow table:

```php
Schema::table('{module}_approvals', function (Blueprint $table) {
    // Approval queue for approvers
    $table->index(['approver_id', 'status', 'assigned_at'], 'idx_{module}_approval_queue');
    
    // Workflow step lookup
    $table->index(['{module}_id', 'step_order'], 'idx_{module}_approval_workflow');
});
```

**Example**:
```php
// invoice_approvals table
$table->index(['approver_id', 'status', 'assigned_at'], 'idx_invoice_approval_queue');
$table->index(['invoice_id', 'step_order'], 'idx_invoice_approval_workflow');
```

### Item/Detail Tables

If your module has line items or detail tables:

```php
Schema::table('{module}_items', function (Blueprint $table) {
    // Parent record items
    $table->index(['{module}_id', 'id'], 'idx_{module}_items_order');
    
    // Department expense tracking (if applicable)
    $table->index(['expense_department_id', '{module}_id'], 'idx_{module}_items_dept_expense');
});
```

**Example**:
```php
// invoice_items table
$table->index(['invoice_id', 'id'], 'idx_invoice_items_order');
$table->index(['expense_department_id', 'invoice_id'], 'idx_invoice_items_dept_expense');
```

### Number Reservation Tables

If your module has number reservations:

```php
Schema::table('{module}_number_reservations', function (Blueprint $table) {
    $table->index(['user_id', 'status'], 'idx_{module}_num_user_status');
    $table->index(['business_unit_id', 'status', 'reserved_at'], 'idx_{module}_num_bu_status_date');
    $table->index(['department_id', 'status', 'reserved_at'], 'idx_{module}_num_dept_status_date');
    $table->index(['status', 'reserved_at'], 'idx_{module}_num_status_date');
});
```

---

## 📊 Performance Targets

With proper indexes, aim for these performance benchmarks:

| Query Type | Target Time | Max Queries | Page Load |
|------------|-------------|-------------|-----------|
| **Dashboard Stats** | <50ms | 3-5 queries | <100ms |
| **List View** | <30ms | 2-3 queries | <80ms |
| **Detail View** | <20ms | 2-4 queries | <60ms |
| **Reports** | <100ms | 5-8 queries | <200ms |

---

## ✅ Pre-Migration Checklist

Before running migration for new module:

- [ ] Replace `{module_table}` with actual table name
- [ ] Replace `{module}` with module code
- [ ] Verify all 5 standard indexes included
- [ ] Add approval indexes if module has workflow
- [ ] Add item indexes if module has detail table
- [ ] Test with `--pretend` flag first
- [ ] Verify indexes created with `SHOW INDEX`
- [ ] Test query performance with EXPLAIN

---

## 🧪 Testing Index Performance

After creating indexes, always verify with EXPLAIN:

```php
// Test in Tinker
DB::enableQueryLog();

// Run your typical queries
YourModule::where('user_id', 1)->where('status', 'active')->get();

// Check query time
$queries = DB::getQueryLog();
dd($queries);

// Verify index usage
$explain = DB::select("
    EXPLAIN SELECT * FROM {module_table} 
    WHERE user_id = 1 AND status = 'active'
");
dd($explain);
```

**Good indicators**:
- ✅ `key` field shows your index name
- ✅ `rows` scanned is low (< 100 for small tables)
- ✅ `Extra` shows "Using index" or "Using index condition"
- ✅ Query time < 10ms

**Bad indicators**:
- ❌ `key` is NULL (no index used)
- ❌ `rows` is high (table scan)
- ❌ `Extra` shows "Using filesort" or "Using temporary"
- ❌ Query time > 100ms

---

## 📝 Naming Conventions

Follow these strict naming conventions:

### Index Names
- Format: `idx_{module}_{purpose}`
- Examples:
  - `idx_invoice_user_status`
  - `idx_asset_bu_status_date`
  - `idx_reimburse_dept_status_date`

### Module Codes
Use short, consistent codes:
- `pr` - Purchase Request
- `invoice` - Invoice
- `asset` - Asset
- `reimburse` - Reimbursement
- `contract` - Contract
- `budget` - Budget

### Table Names
Always use plural form:
- `invoices`
- `assets`
- `reimbursements`
- `invoice_approvals`
- `asset_items`

---

## 🔍 Index Monitoring

Periodically check index usage with these queries:

```sql
-- Show all indexes on a table
SHOW INDEX FROM {table_name};

-- Find unused indexes (MySQL 8.0+)
SELECT * FROM sys.schema_unused_indexes
WHERE object_schema = 'your_database_name';

-- Index size
SELECT 
    table_name,
    index_name,
    ROUND(stat_value * @@innodb_page_size / 1024 / 1024, 2) AS size_mb
FROM mysql.innodb_index_stats
WHERE database_name = 'your_database_name'
ORDER BY stat_value DESC;
```

---

## ⚠️ Common Mistakes to Avoid

### 1. **Wrong Column Order**
```php
// ❌ WRONG: Less selective column first
$table->index(['status', 'user_id'], 'idx_wrong');

// ✅ CORRECT: More selective column first
$table->index(['user_id', 'status'], 'idx_correct');
```

### 2. **Missing created_at in Timeline Queries**
```php
// ❌ WRONG: Can't sort efficiently
$table->index(['business_unit_id', 'status'], 'idx_wrong');

// ✅ CORRECT: Supports ORDER BY created_at
$table->index(['business_unit_id', 'status', 'created_at'], 'idx_correct');
```

### 3. **Duplicate Indexes**
```php
// ❌ WRONG: Redundant (first index covers second)
$table->index(['user_id', 'status'], 'idx_user_status');
$table->index(['user_id'], 'idx_user'); // Redundant!

// ✅ CORRECT: Only create composite index
$table->index(['user_id', 'status'], 'idx_user_status');
```

### 4. **Too Many Indexes**
- ❌ Don't create index for every possible query
- ❌ Each index slows down INSERT/UPDATE operations
- ✅ Stick to standard 5-index pattern
- ✅ Add extras only if proven necessary by monitoring

---

## 📚 Reference Implementation

See these files for complete working examples:
- `database/migrations/2025_10_10_055356_add_performance_indexes_to_pr_tables.php`
- `database/migrations/2025_10_10_074237_add_supplementary_indexes_for_future_modules.php`

---

## 🆘 Troubleshooting

### Index Not Being Used?

1. **Check EXPLAIN output**:
   ```sql
   EXPLAIN SELECT * FROM {table} WHERE user_id = 1 AND status = 'active';
   ```

2. **Verify index exists**:
   ```sql
   SHOW INDEX FROM {table} WHERE Key_name = 'idx_{module}_user_status';
   ```

3. **Check statistics are up-to-date**:
   ```sql
   ANALYZE TABLE {table};
   ```

4. **Consider query rewrite**:
   ```php
   // ❌ Might not use index
   ->where('status', 'LIKE', '%active%')
   
   // ✅ Will use index
   ->where('status', '=', 'active')
   ```

### Slow Queries Despite Indexes?

1. **Check row count** - Maybe table is just large
2. **Verify data distribution** - Skewed data affects performance
3. **Consider covering index** - Include all SELECT columns
4. **Check for table locks** - Concurrent operations

---

## 📞 Support

If you encounter issues with index creation or performance:
1. Check existing migrations for patterns
2. Use `EXPLAIN` to debug query execution
3. Monitor with Laravel Telescope (if installed)
4. Document unusual patterns for future reference

---

**Last Updated**: October 10, 2025  
**Maintained By**: Development Team  
**Review Frequency**: Quarterly or when adding new modules

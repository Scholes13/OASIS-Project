# Known Issues - Admin Panel React Migration

## Issue 1: Select Component API Mismatch in User Create/Edit Pages

**Status:** Identified, needs refactoring  
**Priority:** Medium  
**Affected Files:**
- `resources/js/inertia/Pages/Admin/Users/Create.tsx`
- `resources/js/inertia/Pages/Admin/Users/Edit.tsx`

### Problem

The User Create and Edit pages are using a compound component API for Select that doesn't exist:

```typescript
// Current (incorrect) usage:
<Select value={value} onValueChange={onChange}>
  <SelectTrigger>
    <SelectValue placeholder="Select..." />
  </SelectTrigger>
  <SelectContent>
    <SelectItem value="1">Option 1</SelectItem>
  </SelectContent>
</Select>
```

Our Select component uses Headless UI's Listbox and expects an `options` array:

```typescript
// Correct usage:
<Select
  value={value}
  onChange={onChange}
  options={[
    { value: '1', label: 'Option 1' },
    { value: '2', label: 'Option 2' },
  ]}
  placeholder="Select..."
/>
```

### Impact

- Users Index page: **FIXED** ✅
- Users Create page: Needs refactoring (not blocking - page not yet accessible)
- Users Edit page: Needs refactoring (not blocking - page not yet accessible)

### Solution

Refactor the Create and Edit pages to use the correct Select API:

1. Convert all Select usages to use `options` prop
2. Update dynamic department/position loading to populate options arrays
3. Remove references to SelectTrigger, SelectValue, SelectContent, SelectItem

### Example Refactoring

**Before:**
```typescript
<Select
  value={businessUnit}
  onValueChange={(value) => loadDepartments(parseInt(value), index)}
>
  <SelectTrigger>
    <SelectValue placeholder="Select business unit" />
  </SelectTrigger>
  <SelectContent>
    {businessUnits.map((bu) => (
      <SelectItem key={bu.id} value={String(bu.id)}>
        {bu.name}
      </SelectItem>
    ))}
  </SelectContent>
</Select>
```

**After:**
```typescript
<Select
  value={businessUnit}
  onChange={(value) => loadDepartments(parseInt(String(value)), index)}
  options={businessUnits.map((bu) => ({
    value: bu.id,
    label: bu.name,
  }))}
  placeholder="Select business unit"
/>
```

### Workaround

Until these pages are refactored:
1. Users Index page works correctly (main admin entry point)
2. Create/Edit pages can be accessed but Select dropdowns won't work
3. Users can still be managed through the Index page actions

### Next Steps

1. Create a separate task to refactor User Create page
2. Create a separate task to refactor User Edit page
3. Test dynamic department/position loading after refactoring
4. Verify form validation still works correctly

### Related Files

- Select component: `resources/js/inertia/components/ui/select.tsx`
- Select usage example: `resources/js/inertia/Pages/Admin/Users/Index.tsx` (correct usage)

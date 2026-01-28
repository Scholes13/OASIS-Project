# Bug Fix: Business Unit Assignment Validation Error

**Date**: January 27, 2026  
**Status**: ✅ Completed  
**Related Issue**: "At least one business unit assignment is required" error even when assignment is filled

## Problem

Users encountered validation error "At least one business unit assignment is required" when creating or editing users, even though they had already filled in all required fields (Business Unit, Department, Position).

### Root Cause

The form validation was checking the `business_units` field in the form data, but this field was never being updated when users selected values from the dropdowns. The selected values were only stored in the local `assignments` state, and only transferred to the form data during submission.

**Flow of the bug:**
1. User selects Business Unit → stored in `assignments` state
2. User selects Department → stored in `assignments` state  
3. User selects Position → stored in `assignments` state
4. User clicks "Create User" → form validation runs
5. Validation checks `business_units` field → still empty array
6. Validation fails with error message

## Solution

Added real-time synchronization between the `assignments` state and the form's `business_units` field by:

1. Created `updateBusinessUnitsFormValue()` helper function that:
   - Filters out incomplete assignments (where any ID is 0)
   - Updates the form's `business_units` field using `setValue()`
   - Triggers validation with `shouldValidate: true`

2. Called this helper function whenever assignments change:
   - When Business Unit is selected (`loadDepartments`)
   - When Department is selected (`loadPositions`)
   - When Position is selected (`updatePositionId`)
   - When assignments are initialized from existing user data (Edit page only)

### Code Changes

#### Create.tsx

```typescript
const updateBusinessUnitsFormValue = (currentAssignments: typeof assignments) => {
  // Filter out incomplete assignments (where any ID is 0)
  const validAssignments = currentAssignments.filter(a => 
    a.business_unit_id > 0 && a.department_id > 0 && a.position_id > 0
  );
  
  setValue('business_units', validAssignments.map(a => ({
    business_unit_id: a.business_unit_id,
    department_id: a.department_id,
    position_id: a.position_id,
  })), { shouldValidate: true });
};

const loadDepartments = async (businessUnitId: number, index: number) => {
  // ... existing code ...
  setAssignments(newAssignments);
  
  // Update form value for validation
  updateBusinessUnitsFormValue(newAssignments);
};

const loadPositions = async (departmentId: number, index: number) => {
  // ... existing code ...
  setAssignments(newAssignments);
  
  // Update form value for validation
  updateBusinessUnitsFormValue(newAssignments);
};

const updatePositionId = (positionId: number, index: number) => {
  const newAssignments = [...assignments];
  newAssignments[index].position_id = positionId;
  setAssignments(newAssignments);
  
  // Update form value for validation
  updateBusinessUnitsFormValue(newAssignments);
};
```

#### Edit.tsx

Same changes as Create.tsx, plus:

```typescript
// Initialize assignments from user data
useEffect(() => {
  if (user.business_units && user.business_units.length > 0) {
    // ... existing code ...
    setAssignments(initialAssignments);
    
    // Update form value for validation
    updateBusinessUnitsFormValue(initialAssignments);
  }
}, [user, businessUnits]);
```

## Verification

### Build Status
✅ Build succeeded with no errors
```bash
npm run build
# ✓ built in 54.05s
```

### Files Modified
- `resources/js/inertia/Pages/Admin/Users/Create.tsx`
- `resources/js/inertia/Pages/Admin/Users/Edit.tsx`

### Testing Checklist

**Create User Page** (`/admin/users/create`):
- [ ] Select Business Unit → no error should appear
- [ ] Select Department → no error should appear
- [ ] Select Position → error message should disappear
- [ ] Click "Create User" → form should submit successfully
- [ ] Try adding multiple assignments → all should validate correctly

**Edit User Page** (`/admin/users/{id}/edit`):
- [ ] Page loads with existing assignments → no validation error
- [ ] Modify Business Unit → validation updates correctly
- [ ] Modify Department → validation updates correctly
- [ ] Modify Position → validation updates correctly
- [ ] Click "Update User" → form should submit successfully

## Impact

This fix ensures that:
1. ✅ Real-time validation feedback as users fill the form
2. ✅ Error message disappears immediately when all fields are filled
3. ✅ Form submission works correctly when all required fields are complete
4. ✅ Multiple business unit assignments validate correctly
5. ✅ Edit page loads without validation errors for existing users

## Related Documentation

- **Select Component Fix**: `.kiro/specs/admin-panel-react-migration/BUGFIX-SELECT-COMPONENT.md`
- **Known Issues**: `.kiro/specs/admin-panel-react-migration/KNOWN-ISSUES.md`
- **Task 18 Completion**: `.kiro/specs/admin-panel-react-migration/TASK-18-COMPLETION.md`

## Technical Notes

### Why Filter Incomplete Assignments?

The `updateBusinessUnitsFormValue` function filters out assignments where any ID is 0 because:
- When user first adds an assignment, all IDs start at 0
- When user changes Business Unit, Department and Position IDs reset to 0
- We only want to validate complete assignments (all 3 fields filled)
- This prevents false positives during the filling process

### Why Use `shouldValidate: true`?

Setting `shouldValidate: true` in `setValue()` ensures:
- Validation runs immediately after the value changes
- Error messages appear/disappear in real-time
- User gets instant feedback on form validity
- Better UX compared to validation only on submit

## Next Steps

User should test both Create and Edit pages to confirm:
1. No validation errors when filling the form correctly
2. Error messages appear/disappear appropriately
3. Form submission works as expected
4. Multiple assignments can be added and validated

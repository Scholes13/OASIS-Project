<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments
     */
    public function index(Request $request): View
    {
        $query = Department::with(['businessUnit', 'positions', 'users']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhereHas('businessUnit', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        // Business unit filter
        if ($request->filled('business_unit')) {
            $query->where('business_unit_id', $request->business_unit);
        }

        // Sorting
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        
        if ($sortField === 'business_unit') {
            $query->join('business_units', 'departments.business_unit_id', '=', 'business_units.id')
                  ->orderBy('business_units.name', $sortDirection)
                  ->select('departments.*');
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        $departments = $query->paginate(15)->appends($request->query());
        $businessUnits = BusinessUnit::active()->orderBy('name')->get();

        return view('admin.departments.index', compact('departments', 'businessUnits'));
    }

    /**
     * Show the form for creating a new department
     */
    public function create(): View
    {
        $businessUnits = BusinessUnit::active()->orderBy('name')->get();

        return view('admin.departments.create', compact('businessUnits'));
    }

    /**
     * Store a newly created department
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_unit_id' => 'required|exists:business_units,id',
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('departments')->where(function ($query) use ($request) {
                    return $query->where('business_unit_id', $request->business_unit_id);
                }),
            ],
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        Department::create($validated);

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    /**
     * Display the specified department
     */
    public function show(Department $department): View
    {
        $department->load(['businessUnit', 'positions.users', 'users', 'numberSequences']);

        return view('admin.departments.show', compact('department'));
    }

    /**
     * Show the form for editing the specified department
     */
    public function edit(Department $department): View
    {
        $businessUnits = BusinessUnit::active()->orderBy('name')->get();

        return view('admin.departments.edit', compact('department', 'businessUnits'));
    }

    /**
     * Update the specified department
     */
    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'business_unit_id' => 'required|exists:business_units,id',
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('departments')->where(function ($query) use ($request) {
                    return $query->where('business_unit_id', $request->business_unit_id);
                })->ignore($department->id),
            ],
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $department->update($validated);

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    /**
     * Show the purchasing admin configuration page
     */
    public function purchasingConfig(Department $department): View
    {
        $department->load(['businessUnit', 'defaultPurchasingAdmin']);

        return view('admin.departments.purchasing-config', compact('department'));
    }

    /**
     * Remove the specified department
     */
    public function destroy(Department $department): RedirectResponse
    {
        // Check if department has positions or users
        if ($department->positions()->count() > 0) {
            return redirect()
                ->route('admin.departments.index')
                ->with('error', 'Cannot delete department that has positions assigned.');
        }

        if ($department->users()->count() > 0) {
            return redirect()
                ->route('admin.departments.index')
                ->with('error', 'Cannot delete department that has users assigned.');
        }

        $department->delete();

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}

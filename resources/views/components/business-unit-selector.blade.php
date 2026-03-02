<div class="mb-4">
    <label for="business_unit_id" class="block text-sm font-medium text-gray-700 mb-2">
        Business Unit
    </label>
    <select 
        name="business_unit_id" 
        id="business_unit_id" 
        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"
        {{ $attributes }}
    >
        <option value="">Select Business Unit</option>
        @foreach($businessUnits as $businessUnit)
            <option 
                value="{{ $businessUnit->id }}" 
                {{ $selectedBusinessUnit == $businessUnit->id ? 'selected' : '' }}
            >
                {{ $businessUnit->name }} ({{ $businessUnit->code }})
                @if($businessUnit->parent)
                    - Child of {{ $businessUnit->parent->name }}
                @endif
            </option>
        @endforeach
    </select>
    
    @if($businessUnits->count() > 0)
        <p class="mt-1 text-xs text-gray-500">
            @if(auth()->user()->isSuperAdmin())
                You have access to {{ $businessUnits->count() }} business unit(s) as Super Admin
            @else
                You have access to {{ $businessUnits->count() }} business unit(s)
            @endif
        </p>
    @endif
</div>
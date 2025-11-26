<div 
    wire:init="loadData"
    wire:key="activity-index-{{ auth()->id() }}"
    class="h-full"
>
    <div class="h-full px-4 sm:px-6 lg:px-8 py-6">
        {{-- Header --}}
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    📅 Sales Activities
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Track and manage your daily sales activities
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4 gap-3">
                <a href="{{ route('sales-crm.activities.create') }}" 
                   wire:navigate
                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    New Activity
                </a>
            </div>
        </div>

        @if($readyToLoad)
            {{-- Stats Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                {{-- Total Activities --}}
                <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Activities</dt>
                                    <dd class="text-2xl font-semibold text-gray-900">{{ $this->stats['total'] }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Completed --}}
                <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                                    <dd class="text-2xl font-semibold text-green-600">{{ $this->stats['completed'] }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Planned --}}
                <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Planned</dt>
                                    <dd class="text-2xl font-semibold text-yellow-600">{{ $this->stats['planned'] }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Today --}}
                <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Today</dt>
                                    <dd class="text-2xl font-semibold text-indigo-600">{{ $this->stats['today'] }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="bg-white shadow rounded-lg border border-gray-200 p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    {{-- Search --}}
                    <div class="lg:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input 
                            type="text" 
                            id="search"
                            wire:model.live.debounce.300ms="filters.search"
                            wire:loading.attr="disabled"
                            wire:target="applyFilters"
                            placeholder="Search title, description, location..."
                            class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                        >
                    </div>

                    {{-- Activity Type --}}
                    <div>
                        <label for="activity_type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <select 
                            id="activity_type"
                            wire:model.live="filters.activity_type"
                            wire:loading.attr="disabled"
                            class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                        >
                            <option value="">All Types</option>
                            <option value="call">Phone Call</option>
                            <option value="visit">Site Visit</option>
                            <option value="meeting">Meeting</option>
                            <option value="blitz">Blitz</option>
                            <option value="follow_up">Follow Up</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select 
                            id="status"
                            wire:model.live="filters.status"
                            wire:loading.attr="disabled"
                            class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                        >
                            <option value="">All Status</option>
                            <option value="planned">Planned</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    {{-- Date From --}}
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input 
                            type="date" 
                            id="date_from"
                            wire:model.live="filters.date_from"
                            wire:loading.attr="disabled"
                            class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                        >
                    </div>

                    {{-- Date To --}}
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <input 
                            type="date" 
                            id="date_to"
                            wire:model.live="filters.date_to"
                            wire:loading.attr="disabled"
                            class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                        >
                    </div>

                    {{-- Reset Button --}}
                    <div class="flex items-end lg:col-span-2">
                        <button 
                            type="button"
                            wire:click="resetFilters"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <svg class="-ml-1 mr-2 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Reset Filters
                        </button>
                        @if($this->hasActiveFilters())
                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                {{ $this->getActiveFilterCount() }} active
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Loading Indicator --}}
                <div wire:loading wire:target="applyFilters" class="mt-4">
                    <div class="flex items-center text-sm text-gray-500">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Applying filters...
                    </div>
                </div>
            </div>

            {{-- Activities List --}}
            <div class="bg-white shadow rounded-lg border border-gray-200 overflow-hidden">
                @if($this->activities->count() > 0)
                    <ul role="list" class="divide-y divide-gray-200">
                        @foreach($this->activities as $activity)
                            <li wire:key="activity-{{ $activity->id }}" class="hover:bg-gray-50 transition-colors">
                                <a href="{{ route('sales-crm.activities.show', $activity) }}" wire:navigate class="block px-6 py-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-start space-x-4 flex-1">
                                            {{-- Activity Type Icon --}}
                                            <div class="flex-shrink-0">
                                                @switch($activity->activity_type)
                                                    @case('call')
                                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                            </svg>
                                                        </div>
                                                        @break
                                                    @case('visit')
                                                        <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                                            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                            </svg>
                                                        </div>
                                                        @break
                                                    @case('meeting')
                                                        <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                                                            <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                            </svg>
                                                        </div>
                                                        @break
                                                    @case('blitz')
                                                        <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                                            <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                            </svg>
                                                        </div>
                                                        @break
                                                    @default
                                                        <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                                            <svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                            </svg>
                                                        </div>
                                                @endswitch
                                            </div>

                                            {{-- Activity Info --}}
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between mb-1">
                                                    <p class="text-sm font-medium text-gray-900 truncate">
                                                        {{ $activity->title }}
                                                    </p>
                                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $activity->getResultBadgeColor() }}">
                                                        {{ ucfirst($activity->status) }}
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-500 truncate mb-2">
                                                    {{ Str::limit($activity->description ?? '-', 100) }}
                                                </p>
                                                <div class="flex items-center text-xs text-gray-400 space-x-4">
                                                    <span class="flex items-center">
                                                        <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                        {{ $activity->activity_date->format('d M Y') }}
                                                    </span>
                                                    @if($activity->location)
                                                        <span class="flex items-center">
                                                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            </svg>
                                                            {{ Str::limit($activity->location, 30) }}
                                                        </span>
                                                    @endif
                                                    @if($activity->contact)
                                                        <span class="flex items-center">
                                                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                            </svg>
                                                            {{ $activity->contact->name }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Chevron --}}
                                        <div class="ml-4 flex-shrink-0">
                                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    {{-- Pagination --}}
                    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        {{ $this->activities->links() }}
                    </div>
                @else
                    {{-- Empty State --}}
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No activities found</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            @if($this->hasActiveFilters())
                                Try adjusting your filters or reset them.
                            @else
                                Get started by creating a new activity.
                            @endif
                        </p>
                        <div class="mt-6">
                            @if($this->hasActiveFilters())
                                <button 
                                    type="button" 
                                    wire:click="resetFilters"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    Reset Filters
                                </button>
                            @else
                                <a 
                                    href="{{ route('sales-crm.activities.create') }}" 
                                    wire:navigate
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                    </svg>
                                    Create Activity
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @else
            {{-- Loading Skeleton --}}
            <x-loading-skeleton type="stats" class="mb-6" />
            <x-loading-skeleton type="table" :rows="10" />
        @endif
    </div>
</div>

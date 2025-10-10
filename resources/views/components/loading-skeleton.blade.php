{{-- Loading Skeleton Component --}}
{{-- Usage: <x-loading-skeleton :rows="5" type="table" /> --}}

@props([
    'rows' => 3,
    'type' => 'default', // default, table, card, stats
])

<div {{ $attributes->merge(['class' => 'animate-pulse']) }}>
    @if($type === 'table')
        {{-- Table Skeleton --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="h-4 bg-gray-200 rounded w-1/4"></div>
            </div>
            <div class="divide-y divide-gray-200">
                @for($i = 0; $i < $rows; $i++)
                    <div class="px-6 py-4 flex items-center space-x-4">
                        <div class="h-10 w-10 bg-gray-200 rounded-full"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                            <div class="h-3 bg-gray-100 rounded w-1/2"></div>
                        </div>
                        <div class="h-8 w-20 bg-gray-200 rounded"></div>
                    </div>
                @endfor
            </div>
        </div>
    
    @elseif($type === 'card')
        {{-- Card Grid Skeleton --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @for($i = 0; $i < $rows; $i++)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="h-6 bg-gray-200 rounded w-3/4 mb-4"></div>
                    <div class="space-y-3">
                        <div class="h-4 bg-gray-100 rounded"></div>
                        <div class="h-4 bg-gray-100 rounded w-5/6"></div>
                        <div class="h-4 bg-gray-100 rounded w-4/6"></div>
                    </div>
                    <div class="mt-4 flex justify-between items-center">
                        <div class="h-8 w-24 bg-gray-200 rounded"></div>
                        <div class="h-8 w-8 bg-gray-200 rounded-full"></div>
                    </div>
                </div>
            @endfor
        </div>
    
    @elseif($type === 'stats')
        {{-- Stats Grid Skeleton --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @for($i = 0; $i < 4; $i++)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                        <div class="h-10 w-10 bg-gray-200 rounded-full"></div>
                    </div>
                    <div class="mt-4 h-8 bg-gray-300 rounded w-1/3"></div>
                    <div class="mt-2 h-3 bg-gray-100 rounded w-1/4"></div>
                </div>
            @endfor
        </div>
    
    @else
        {{-- Default List Skeleton --}}
        <div class="space-y-4">
            @for($i = 0; $i < $rows; $i++)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-start space-x-4">
                        <div class="h-12 w-12 bg-gray-200 rounded-lg"></div>
                        <div class="flex-1 space-y-3">
                            <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                            <div class="h-3 bg-gray-100 rounded w-full"></div>
                            <div class="h-3 bg-gray-100 rounded w-5/6"></div>
                        </div>
                    </div>
                </div>
            @endfor
        </div>
    @endif
</div>

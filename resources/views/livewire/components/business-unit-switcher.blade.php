<div class="relative" x-data="{ open: false }">
    @if(count($availableBusinessUnits) > 1)
        <!-- Business Unit Switcher Button -->
        <button 
            type="button" 
            x-on:click="open = !open"
            x-on:click.away="open = false"
            class="relative flex items-center space-x-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200">
            
            <!-- Current Business Unit Info -->
            <div class="flex items-center space-x-2">
                <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-semibold text-indigo-600">
                        {{ substr($currentBusinessUnit['code'], 0, 2) }}
                    </span>
                </div>
                <div class="hidden sm:block">
                    <span class="text-sm font-medium text-gray-900">{{ $currentBusinessUnit['code'] }}</span>
                    <span class="text-xs text-gray-500 ml-1">{{ $currentBusinessUnit['name'] }}</span>
                </div>
            </div>
            
            <!-- Dropdown Arrow -->
            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" 
                 :class="open ? 'rotate-180' : ''" 
                 fill="none" 
                 stroke="currentColor" 
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <div x-show="open" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="absolute right-0 z-10 mt-2 w-80 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
             style="display: none;">
             
            <div class="px-4 py-3 border-b border-gray-200">
                <p class="text-sm font-medium text-gray-900">Switch Business Unit</p>
                <p class="text-xs text-gray-500">Select a business unit to switch context</p>
            </div>
            
            <div class="max-h-60 overflow-y-auto">
                @foreach($availableBusinessUnits as $businessUnit)
                    <button 
                        wire:click="switchBusinessUnit({{ $businessUnit['id'] }})"
                        x-on:click="open = false"
                        class="w-full text-left px-4 py-3 hover:bg-gray-50 transition-colors duration-200 border-l-4 {{ $businessUnit['id'] == $currentBusinessUnit['id'] ? 'border-indigo-500 bg-indigo-50' : 'border-transparent' }}">
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-lg flex items-center justify-center shadow-sm">
                                    <span class="text-xs font-bold text-white">
                                        {{ substr($businessUnit['code'], 0, 2) }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $businessUnit['code'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $businessUnit['name'] }}</p>
                                    <p class="text-xs text-indigo-600 font-medium">{{ ucfirst($businessUnit['role']) }}</p>
                                </div>
                            </div>
                            
                            @if($businessUnit['id'] == $currentBusinessUnit['id'])
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    @else
        <!-- Single Business Unit Display -->
        <div class="flex items-center space-x-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
            <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center">
                <span class="text-xs font-semibold text-indigo-600">
                    {{ substr($currentBusinessUnit['code'], 0, 2) }}
                </span>
            </div>
            <div class="hidden sm:block">
                <span class="text-sm font-medium text-gray-900">{{ $currentBusinessUnit['code'] }}</span>
                <span class="text-xs text-gray-500 ml-1">{{ $currentBusinessUnit['name'] }}</span>
            </div>
        </div>
    @endif
</div>
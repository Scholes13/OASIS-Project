<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200">
        <div class="px-6 py-6">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Docs & Help</h1>
                    <p class="text-sm text-gray-500">Panduan lengkap penggunaan sistem Oasis</p>
                </div>
            </div>
        </div>
    </div>

    <div class="px-6 py-6">
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Sidebar Navigation -->
            <div class="lg:w-56 flex-shrink-0">
                <nav class="bg-white rounded-xl border border-gray-200 overflow-hidden sticky top-4">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-900">Daftar Isi</h3>
                    </div>
                    <div class="p-2">
                        @foreach($sections as $key => $label)
                            <button 
                                wire:click="setActiveSection('{{ $key }}')"
                                class="w-full text-left px-3 py-2 rounded-lg text-sm transition-colors {{ $activeSection === $key ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="flex-1 min-w-0">
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="p-6">
                        @if($activeSection === 'getting-started')
                            @include('livewire.docs-help.getting-started')
                        @elseif($activeSection === 'purchase-request')
                            @include('livewire.docs-help.purchase-request')
                        @elseif($activeSection === 'stock-request')
                            @include('livewire.docs-help.stock-request')
                        @elseif($activeSection === 'approvals')
                            @include('livewire.docs-help.approvals')
                        @elseif($activeSection === 'dashboard')
                            @include('livewire.docs-help.dashboard')
                        @elseif($activeSection === 'faq')
                            @include('livewire.docs-help.faq')
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

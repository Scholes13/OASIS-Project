<div class="space-y-8">


    @if(!$generatedNumber)
        <!-- PR Number Request Form -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Request PR Number</h3>
                <p class="text-sm text-gray-600 mt-1">Generate a new Purchase Request number for {{ session('current_business_unit_name') }}</p>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- Auto-populated Information Display -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Informasi Otomatis</h4>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">No.</label>
                            <p class="text-sm text-gray-900">Auto-generated</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Tgl Pengajuan</label>
                            <p class="text-sm text-gray-900">{{ $submission_date }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Dept</label>
                            <p class="text-sm text-gray-900">{{ $department_code }} - {{ $department_name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">No. PR</label>
                            <p class="text-sm text-gray-500 font-mono">{{ $this->getNextNumberPreview() }}</p>
                            <p class="text-xs text-gray-400">Format: PR.DEPT/YYYY/MM/XXX</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Pembuat</label>
                            <p class="text-sm text-gray-900">{{ $user_name }}</p>
                        </div>
                    </div>
                </div>

                <!-- Required Input Fields -->
                <div class="grid grid-cols-1 gap-6">
                    <!-- Keperluan -->
                    <div>
                        <label for="purpose" class="block text-sm font-semibold text-gray-700 mb-2">
                            Keperluan <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            wire:model.lazy="purpose" 
                            id="purpose"
                            rows="3"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors duration-200"
                            placeholder="Contoh: Pembelian peralatan kantor untuk mendukung operasional harian..."
                            maxlength="500"
                        ></textarea>
                        @error('purpose') 
                            <p class="mt-1 text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                        <div class="mt-1 flex items-center justify-between">
                            <p class="text-xs {{ strlen(trim($purpose ?? '')) >= 3 ? 'text-green-600' : 'text-red-500' }}">
                                {{ strlen($purpose ?? '') }}/500 karakter 
                                @if(strlen(trim($purpose ?? '')) >= 3)
                                    <span class="inline-flex items-center">
                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Valid
                                    </span>
                                @else
                                    <span class="text-red-500">(minimal 3 karakter)</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Deskripsi -->
                    <div>
                        <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                            Deskripsi <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            wire:model.lazy="description" 
                            id="description"
                            rows="4"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors duration-200"
                            placeholder="Contoh: Laptop untuk karyawan baru, printer untuk departemen HR, alat tulis untuk kebutuhan sehari-hari, dll. Jelaskan secara detail item yang dibutuhkan..."
                            maxlength="1000"
                        ></textarea>
                        @error('description') 
                            <p class="mt-1 text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                        <div class="mt-1 flex items-center justify-between">
                            <p class="text-xs {{ strlen(trim($description ?? '')) >= 10 ? 'text-green-600' : 'text-red-500' }}">
                                {{ strlen($description ?? '') }}/1000 karakter 
                                @if(strlen(trim($description ?? '')) >= 10)
                                    <span class="inline-flex items-center">
                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Valid
                                    </span>
                                @else
                                    <span class="text-red-500">(minimal 10 karakter)</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Preview Section - Only show format info -->
                @if($this->isFormValid)
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h4 class="text-sm font-medium text-blue-900">Siap untuk Generate Nomor PR</h4>
                        </div>
                        <p class="text-sm text-blue-800">Format: {{ $this->getNextNumberPreview() }}</p>
                        <p class="text-xs text-blue-600 mt-1">Nomor PR akan di-generate secara unik saat Anda klik "Generate Nomor PR"</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Generate Button -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    @if(!$this->isFormValid)
                        <div class="flex items-start">
                            <svg class="w-4 h-4 text-amber-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            <div>
                                <p class="font-medium">Lengkapi persyaratan berikut:</p>
                                <ul class="mt-1 text-xs space-y-1">
                                    @if(strlen(trim($purpose ?? '')) < 3)
                                        <li class="flex items-center text-red-600">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                            Keperluan minimal 3 karakter (saat ini: {{ strlen(trim($purpose ?? '')) }})
                                        </li>
                                    @else
                                        <li class="flex items-center text-green-600">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Keperluan sudah valid
                                        </li>
                                    @endif
                                    @if(strlen(trim($description ?? '')) < 10)
                                        <li class="flex items-center text-red-600">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                            Deskripsi minimal 10 karakter (saat ini: {{ strlen(trim($description ?? '')) }})
                                        </li>
                                    @else
                                        <li class="flex items-center text-green-600">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Deskripsi sudah valid
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p>Semua persyaratan terpenuhi! Klik generate untuk mereservasi nomor PR Anda.</p>
                        </div>
                    @endif
                </div>
                
                <div class="flex items-center space-x-3">
                    <button 
                        wire:click="submitRequest" 
                        wire:loading.attr="disabled"
                        wire:target="submitRequest"
                        type="button"
                        @if(!$this->isFormValid) disabled @endif
                        onclick="console.log('Button clicked!'); console.log('Form valid:', {{ $this->isFormValid ? 'true' : 'false' }});"
                        class="inline-flex items-center px-6 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                        <span wire:loading.remove wire:target="submitRequest" class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h4a1 1 0 110 2h-1v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6H3a1 1 0 110-2h4zM6 6v12h12V6H6z"></path>
                            </svg>
                            Generate Nomor PR
                        </span>
                        <span wire:loading wire:target="submitRequest" class="flex items-center">
                            <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Generating...
                        </span>
                    </button>
                </div>
            </div>
        </div>

    @else
        <!-- Generated Number Display -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Nomor PR Berhasil Di-Generate</h3>
                        <p class="text-sm text-gray-600 mt-1">Nomor Purchase Request Anda siap digunakan</p>
                    </div>
                    <div class="text-green-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- Generated Number Display -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
                    <h4 class="text-lg font-semibold text-green-900 mb-2">Nomor PR yang Di-generate</h4>
                    <p class="text-3xl font-mono font-bold text-green-800 mb-3">{{ $generatedNumber }}</p>
                    <p class="text-sm text-green-700">Mohon simpan nomor ini - Anda akan membutuhkannya untuk langkah selanjutnya</p>
                </div>

                <!-- Data Table Format -->
                @if($numberDetails)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h5 class="text-sm font-medium text-gray-900 mb-3">Detail Request PR Number</h5>
                        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No.</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl Pengajuan</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dept</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. PR</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keperluan</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pembuat</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $numberDetails['sequence_number'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($numberDetails['submission_date'])->format('d/m/Y') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $numberDetails['department_code'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">{{ $numberDetails['formatted_number'] }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">{{ $numberDetails['purpose'] }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">{{ $numberDetails['description'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $numberDetails['requested_by'] }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Next Step Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    <p><strong>Langkah Selanjutnya:</strong> Buat form Purchase Request lengkap menggunakan nomor ini.</p>
                </div>
                
                <div class="flex items-center space-x-3">
                    <button 
                        wire:click="$set('generatedNumber', null)" 
                        type="button"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                        Generate Nomor Baru
                    </button>

                    <button 
                        wire:click="createPRForm" 
                        type="button"
                        class="inline-flex items-center px-6 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Buat Form PR
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
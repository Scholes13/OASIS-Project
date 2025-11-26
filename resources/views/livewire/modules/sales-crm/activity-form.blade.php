<div class="min-h-screen py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-6">
            <h2 class="text-2xl sm:text-3xl font-bold leading-tight text-gray-900 dark:text-white">
                {{ $isEditMode ? '✏️ Edit Activity' : '➕ New Activity' }}
            </h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ $isEditMode ? 'Update activity details' : 'Record your sales activity' }}
            </p>
        </div>

        {{-- Error Messages --}}
        @if ($errors->any())
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Please fix the following errors:</h3>
                        <ul class="mt-2 text-sm text-red-700 dark:text-red-300 list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Form --}}
        <form wire:submit="save" class="space-y-6">
            {{-- Activity Information Card --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">📅 Activity Information</h3>
                </div>

                <div class="p-4 sm:p-6 space-y-6">
                    {{-- Activity Type & Date --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                        {{-- Activity Type --}}
                        <div>
                            <label for="activity_type"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Activity Type <span class="text-red-500">*</span>
                            </label>
                            <select id="activity_type" wire:model.blur="activity_type"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('activity_type') border-red-300 dark:border-red-600 @enderror">
                                <option value="call">📞 Phone Call</option>
                                <option value="visit">🏢 Site Visit</option>
                                <option value="meeting">👥 Meeting</option>
                                <option value="blitz">⚡ Blitz</option>
                                <option value="follow_up">🔄 Follow Up</option>
                                <option value="other">📋 Other</option>
                            </select>
                            @error('activity_type')
                                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Activity Date --}}
                        <div>
                            <label for="activity_date"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Activity Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="activity_date" wire:model.blur="activity_date"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('activity_date') border-red-300 dark:border-red-600 @enderror">
                            @error('activity_date')
                                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Link to Contact (Optional) --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <div class="flex items-center mb-4">
                            <input type="checkbox" id="link_contact" wire:model.live="link_contact"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded transition-colors">
                            <label for="link_contact" class="ml-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                🔗 Link to Existing Contact <span class="text-gray-500">(Auto-fill company info)</span>
                            </label>
                        </div>

                        @if ($link_contact)
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                                <label for="existing_contact_id"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Select Contact
                                </label>
                                <select id="existing_contact_id" wire:model.live="existing_contact_id"
                                    class="w-full rounded-lg border-blue-300 dark:border-blue-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <option value="">-- Choose Contact --</option>
                                    @foreach ($availableContacts as $contact)
                                        <option value="{{ $contact['id'] }}">{{ $contact['label'] }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-2 text-xs text-blue-700 dark:text-blue-300 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Selecting a contact will auto-fill company, department, PIC, and address fields
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- Company Name --}}
                    <div>
                        <label for="company_name"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Company Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="company_name" wire:model.blur="company_name"
                            placeholder="e.g., PT ABC Indonesia"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('company_name') border-red-300 dark:border-red-600 @enderror">
                        @error('company_name')
                            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Department --}}
                    <div>
                        <label for="department"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Department <span class="text-gray-400">(Optional)</span>
                        </label>
                        <input type="text" id="department" wire:model.blur="department"
                            placeholder="e.g., Procurement, Marketing, Finance"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('department') border-red-300 dark:border-red-600 @enderror">
                        @error('department')
                            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- PIC Name & Phone --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                        {{-- PIC Name --}}
                        <div>
                            <label for="pic_name"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                PIC Name (Person In Charge)
                            </label>
                            <input type="text" id="pic_name" wire:model.blur="pic_name"
                                placeholder="e.g., John Doe"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('pic_name') border-red-300 dark:border-red-600 @enderror">
                            @error('pic_name')
                                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- PIC Phone --}}
                        <div>
                            <label for="pic_phone"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                PIC Phone Number
                            </label>
                            <input type="text" id="pic_phone" wire:model.blur="pic_phone"
                                placeholder="e.g., 0812-3456-7890"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('pic_phone') border-red-300 dark:border-red-600 @enderror">
                            @error('pic_phone')
                                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- PIC Email & Position --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                        {{-- PIC Email --}}
                        <div>
                            <label for="pic_email"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                PIC Email <span class="text-gray-400">(Optional)</span>
                            </label>
                            <input type="email" id="pic_email" wire:model.blur="pic_email"
                                placeholder="e.g., john.doe@company.com"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('pic_email') border-red-300 dark:border-red-600 @enderror">
                            @error('pic_email')
                                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- PIC Position --}}
                        <div>
                            <label for="pic_position"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                PIC Position <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="pic_position" wire:model.blur="pic_position"
                                placeholder="e.g., Procurement Manager, Director"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('pic_position') border-red-300 dark:border-red-600 @enderror">
                            @error('pic_position')
                                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Birth Date --}}
                    <div>
                        <label for="pic_birth_date"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Birth Date <span class="text-gray-400">(Optional - for relationship building)</span>
                        </label>
                        <input type="date" id="pic_birth_date" wire:model.blur="pic_birth_date"
                            max="{{ date('Y-m-d') }}"
                            class="w-full md:w-1/2 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('pic_birth_date') border-red-300 dark:border-red-600 @enderror">
                        @error('pic_birth_date')
                            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Social Media --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/>
                                <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"/>
                            </svg>
                            Social Media <span class="text-gray-400 font-normal ml-1">(All optional)</span>
                        </h3>

                        <div class="space-y-4">
                            {{-- LinkedIn --}}
                            <div>
                                <label for="linkedin"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 flex items-center">
                                    <svg class="w-4 h-4 mr-1.5 text-blue-700 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M6.29 18.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0020 3.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.073 4.073 0 01.8 7.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 010 16.407a11.616 11.616 0 006.29 1.84"/>
                                    </svg>
                                    LinkedIn Profile
                                </label>
                                <input type="url" id="linkedin" wire:model.blur="linkedin"
                                    placeholder="https://linkedin.com/in/username"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('linkedin') border-red-300 dark:border-red-600 @enderror">
                                @error('linkedin')
                                    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Instagram --}}
                            <div>
                                <label for="instagram"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 flex items-center">
                                    <svg class="w-4 h-4 mr-1.5 text-pink-600 dark:text-pink-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 0C7.284 0 6.944.012 5.877.06 4.813.109 4.086.278 3.45.525a4.902 4.902 0 00-1.772 1.153A4.902 4.902 0 00.525 3.45C.278 4.086.109 4.813.06 5.877.012 6.944 0 7.284 0 10s.012 3.056.06 4.123c.049 1.064.218 1.791.465 2.427a4.902 4.902 0 001.153 1.772 4.902 4.902 0 001.772 1.153c.636.247 1.363.416 2.427.465C6.944 19.988 7.284 20 10 20s3.056-.012 4.123-.06c1.064-.049 1.791-.218 2.427-.465a4.902 4.902 0 001.772-1.153 4.902 4.902 0 001.153-1.772c.247-.636.416-1.363.465-2.427.048-1.067.06-1.407.06-4.123s-.012-3.056-.06-4.123c-.049-1.064-.218-1.791-.465-2.427a4.902 4.902 0 00-1.153-1.772A4.902 4.902 0 0016.55.525C15.914.278 15.187.109 14.123.06 13.056.012 12.716 0 10 0zm0 1.802c2.67 0 2.986.01 4.04.058.975.045 1.504.207 1.857.344.466.181.8.398 1.15.748.35.35.567.684.748 1.15.137.353.3.882.344 1.857.048 1.054.058 1.37.058 4.04s-.01 2.986-.058 4.04c-.045.975-.207 1.504-.344 1.857-.181.466-.398.8-.748 1.15-.35.35-.684.567-1.15.748-.353.137-.882.3-1.857.344-1.054.048-1.37.058-4.04.058s-2.986-.01-4.04-.058c-.975-.045-1.504-.207-1.857-.344a3.097 3.097 0 01-1.15-.748 3.097 3.097 0 01-.748-1.15c-.137-.353-.3-.882-.344-1.857-.048-1.054-.058-1.37-.058-4.04s.01-2.986.058-4.04c.045-.975.207-1.504.344-1.857.181-.466.398-.8.748-1.15.35-.35.684-.567 1.15-.748.353-.137.882-.3 1.857-.344 1.054-.048 1.37-.058 4.04-.058z"/>
                                        <path d="M10 13.333a3.333 3.333 0 110-6.666 3.333 3.333 0 010 6.666zm0-8.468a5.135 5.135 0 100 10.27 5.135 5.135 0 000-10.27zm6.538-.203a1.2 1.2 0 11-2.4 0 1.2 1.2 0 012.4 0z"/>
                                    </svg>
                                    Instagram Username
                                </label>
                                <input type="text" id="instagram" wire:model.blur="instagram"
                                    placeholder="username or https://instagram.com/username"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('instagram') border-red-300 dark:border-red-600 @enderror">
                                @error('instagram')
                                    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Facebook --}}
                            <div>
                                <label for="facebook"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 flex items-center">
                                    <svg class="w-4 h-4 mr-1.5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M20 10c0-5.523-4.477-10-10-10S0 4.477 0 10c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V10h2.54V7.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V10h2.773l-.443 2.89h-2.33v6.988C16.343 19.128 20 14.991 20 10z"/>
                                    </svg>
                                    Facebook Profile
                                </label>
                                <input type="url" id="facebook" wire:model.blur="facebook"
                                    placeholder="https://facebook.com/username"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('facebook') border-red-300 dark:border-red-600 @enderror">
                                @error('facebook')
                                    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Office Address --}}
                    <div>
                        <label for="office_address"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Office Address
                        </label>
                        <textarea id="office_address" wire:model.blur="office_address" rows="2"
                            placeholder="e.g., Jl. Sudirman No. 123, Jakarta Pusat 10220"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-y @error('office_address') border-red-300 dark:border-red-600 @enderror"></textarea>
                        @error('office_address')
                            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label for="description"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Activity Description
                        </label>
                        <textarea id="description" wire:model.blur="description" rows="4"
                            placeholder="Describe what happened during this activity..."
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-y @error('description') border-red-300 dark:border-red-600 @enderror"></textarea>
                        @error('description')
                            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select id="status" wire:model.blur="status"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('status') border-red-300 dark:border-red-600 @enderror">
                            <option value="planned">📅 Planned</option>
                            <option value="completed">✅ Completed</option>
                            <option value="cancelled">❌ Cancelled</option>
                        </select>
                        @error('status')
                            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-col sm:flex-row items-center justify-end gap-3 sm:gap-4">
                <button type="button" wire:click="cancel"
                    class="w-full sm:w-auto order-2 sm:order-1 px-6 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors shadow-sm">
                    Cancel
                </button>
                <button type="submit" wire:loading.attr="disabled"
                    class="w-full sm:w-auto order-1 sm:order-2 px-6 py-2.5 border border-transparent rounded-lg text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm">
                    <span wire:loading.remove wire:target="save">
                        {{ $isEditMode ? '💾 Update Activity' : '➕ Save Activity' }}
                    </span>
                    <span wire:loading wire:target="save" class="flex items-center justify-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Saving...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

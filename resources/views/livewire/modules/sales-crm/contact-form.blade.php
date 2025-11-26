<div>
    {{-- Header --}}
    <div class="px-4 py-5 sm:px-6 lg:px-8 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ $isEditMode ? 'Edit Contact' : 'Create Contact' }}
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $isEditMode ? 'Update contact information' : 'Add a new contact to your CRM' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <div class="px-4 py-5 sm:px-6 lg:px-8">
        <form wire:submit="save" class="space-y-6">
            {{-- Personal Information Section --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Personal Information</h2>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    {{-- Name --}}
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" wire:model.blur="name"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm @error('name') border-red-300 @enderror">
                        @error('name')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Email
                        </label>
                        <input type="email" id="email" wire:model.blur="email"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm @error('email') border-red-300 @enderror">
                        @error('email')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Phone --}}
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Phone
                        </label>
                        <input type="text" id="phone" wire:model.blur="phone"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm @error('phone') border-red-300 @enderror">
                        @error('phone')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Mobile --}}
                    <div>
                        <label for="mobile" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Mobile <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="mobile" wire:model.blur="mobile"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm @error('mobile') border-red-300 @enderror">
                        @error('mobile')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Birth Date --}}
                    <div>
                        <label for="birth_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Birth Date
                        </label>
                        <input type="date" id="birth_date" wire:model.blur="birth_date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm @error('birth_date') border-red-300 @enderror">
                        @error('birth_date')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Professional Information Section --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Professional Information</h2>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    {{-- Company --}}
                    <div>
                        <label for="company" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Company
                        </label>
                        <input type="text" id="company" wire:model.blur="company"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm @error('company') border-red-300 @enderror">
                        @error('company')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Department --}}
                    <div>
                        <label for="department" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Department
                        </label>
                        <input type="text" id="department" wire:model.blur="department"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm @error('department') border-red-300 @enderror">
                        @error('department')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Position --}}
                    <div class="sm:col-span-2">
                        <label for="position" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Position
                        </label>
                        <input type="text" id="position" wire:model.blur="position"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm @error('position') border-red-300 @enderror">
                        @error('position')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Contact Classification Section --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Classification</h2>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    {{-- Status --}}
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select id="status" wire:model.blur="status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm @error('status') border-red-300 @enderror">
                            <option value="">Select Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        @error('status')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Category --}}
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select id="category" wire:model.blur="category"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm @error('category') border-red-300 @enderror">
                            <option value="">Select Category</option>
                            <option value="lead">Lead</option>
                            <option value="prospect">Prospect</option>
                            <option value="customer">Customer</option>
                            <option value="partner">Partner</option>
                        </select>
                        @error('category')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Social Media Section --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Social Media</h2>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    {{-- LinkedIn --}}
                    <div>
                        <label for="linkedin" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            LinkedIn
                        </label>
                        <input type="url" id="linkedin" wire:model.blur="linkedin"
                            placeholder="https://linkedin.com/in/..."
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm @error('linkedin') border-red-300 @enderror">
                        @error('linkedin')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Instagram --}}
                    <div>
                        <label for="instagram" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Instagram
                        </label>
                        <input type="url" id="instagram" wire:model.blur="instagram"
                            placeholder="https://instagram.com/..."
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm @error('instagram') border-red-300 @enderror">
                        @error('instagram')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Facebook --}}
                    <div>
                        <label for="facebook" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Facebook
                        </label>
                        <input type="url" id="facebook" wire:model.blur="facebook"
                            placeholder="https://facebook.com/..."
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm @error('facebook') border-red-300 @enderror">
                        @error('facebook')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Additional Information Section --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Additional Information</h2>
                <div class="grid grid-cols-1 gap-6">
                    {{-- Address --}}
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Address
                        </label>
                        <textarea id="address" wire:model.blur="address" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm @error('address') border-red-300 @enderror"></textarea>
                        @error('address')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Notes
                        </label>
                        <textarea id="notes" wire:model.blur="notes" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm @error('notes') border-red-300 @enderror"></textarea>
                        @error('notes')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center justify-end space-x-4">
                    <button type="button" wire:click="cancel"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="save">
                            {{ $isEditMode ? 'Update Contact' : 'Create Contact' }}
                        </span>
                        <span wire:loading wire:target="save">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
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
            </div>
        </form>
    </div>
</div>

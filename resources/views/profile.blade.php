<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="w-full space-y-6">
            <!-- User Information Display (Read-only) -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">
                            {{ __('Profile Information') }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ __('Your account information is managed by the administrator.') }}
                        </p>
                    </header>

                    <div class="mt-6 space-y-4">
                        <div>
                            <x-input-label for="display_name" :value="__('Name')" />
                            <div class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-gray-700">
                                {{ Auth::user()->name }}
                            </div>
                        </div>

                        <div>
                            <x-input-label for="display_email" :value="__('Email')" />
                            <div class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-gray-700">
                                {{ Auth::user()->email }}
                            </div>
                        </div>

                        @if(Auth::user()->role)
                        <div>
                            <x-input-label for="display_role" :value="__('Role')" />
                            <div class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-gray-700">
                                {{ ucfirst(Auth::user()->role) }}
                            </div>
                        </div>
                        @endif

                        @if(Auth::user()->department)
                        <div>
                            <x-input-label for="display_department" :value="__('Department')" />
                            <div class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-gray-700">
                                {{ Auth::user()->department->name }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Password Update Form -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

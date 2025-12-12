<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('SLA Settings - Purchasing Admin') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="w-full">
            <!-- Session flash messages are automatically displayed as toast by layouts/app.blade.php -->

            @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800 mb-2">Please fix the following errors:</h3>
                            <ul class="list-disc list-inside text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Info Box -->
            <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">About SLA Settings</h3>
                        <div class="mt-2 text-sm text-blue-700 space-y-1">
                            <p>• <strong>Follow-up SLA:</strong> Maximum time allowed from task entry to when admin starts working (Pending → In Progress)</p>
                            <p>• <strong>Completion SLA:</strong> Maximum time allowed from start to completion (In Progress → Done)</p>
                            <p>• <strong>Email Alerts:</strong> When enabled, system will send email notifications to assigned admin and department manager when tasks exceed SLA targets</p>
                            <p>• Configure SLA targets for each business unit independently</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLA Settings for Each Business Unit -->
            <div class="space-y-6">
                @foreach($businessUnits as $businessUnit)
                    @php
                        $settings = $slaSettings->get($businessUnit->id);
                        $hasSettings = $settings !== null;
                    @endphp

                    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-indigo-100 rounded-md p-2">
                                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $businessUnit->name }}</h3>
                                        <p class="text-sm text-gray-500">{{ $businessUnit->code }}</p>
                                    </div>
                                </div>
                                @if($hasSettings)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Configured
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        Not Configured
                                    </span>
                                @endif
                            </div>
                        </div>

                        <form action="{{ route('admin.sla-settings.update') }}" method="POST" class="p-6">
                            @csrf
                            <input type="hidden" name="business_unit_id" value="{{ $businessUnit->id }}">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Follow-up SLA Hours -->
                                <div>
                                    <label for="followup_sla_hours_{{ $businessUnit->id }}" class="block text-sm font-medium text-gray-700 mb-2">
                                        Follow-up SLA (Hours) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" 
                                           name="followup_sla_hours" 
                                           id="followup_sla_hours_{{ $businessUnit->id }}" 
                                           value="{{ old('followup_sla_hours', $settings?->followup_sla_hours ?? 24) }}"
                                           min="1"
                                           max="720"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('followup_sla_hours') border-red-500 @enderror"
                                           placeholder="24"
                                           required>
                                    <p class="mt-1 text-xs text-gray-500">Maximum time from task entry to start (1-720 hours)</p>
                                    @error('followup_sla_hours')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Completion SLA Hours -->
                                <div>
                                    <label for="completion_sla_hours_{{ $businessUnit->id }}" class="block text-sm font-medium text-gray-700 mb-2">
                                        Completion SLA (Hours) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" 
                                           name="completion_sla_hours" 
                                           id="completion_sla_hours_{{ $businessUnit->id }}" 
                                           value="{{ old('completion_sla_hours', $settings?->completion_sla_hours ?? 72) }}"
                                           min="1"
                                           max="720"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('completion_sla_hours') border-red-500 @enderror"
                                           placeholder="72"
                                           required>
                                    <p class="mt-1 text-xs text-gray-500">Maximum time from start to completion (1-720 hours)</p>
                                    @error('completion_sla_hours')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Email Alerts Toggle -->
                            <div class="mt-6">
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input type="checkbox" 
                                               name="email_alerts_enabled" 
                                               id="email_alerts_enabled_{{ $businessUnit->id }}" 
                                               value="1"
                                               {{ old('email_alerts_enabled', $settings?->email_alerts_enabled ?? true) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    </div>
                                    <div class="ml-3">
                                        <label for="email_alerts_enabled_{{ $businessUnit->id }}" class="font-medium text-gray-700">Enable Email Alerts</label>
                                        <p class="text-sm text-gray-500">Send email notifications when tasks exceed SLA targets</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Current Settings Display (if exists) -->
                            @if($hasSettings)
                                <div class="mt-6 pt-6 border-t border-gray-200">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3">Current Settings</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="bg-gray-50 rounded-lg p-3">
                                            <p class="text-xs text-gray-500">Follow-up SLA</p>
                                            <p class="text-lg font-semibold text-gray-900">{{ $settings->followup_sla_hours }} hours</p>
                                        </div>
                                        <div class="bg-gray-50 rounded-lg p-3">
                                            <p class="text-xs text-gray-500">Completion SLA</p>
                                            <p class="text-lg font-semibold text-gray-900">{{ $settings->completion_sla_hours }} hours</p>
                                        </div>
                                        <div class="bg-gray-50 rounded-lg p-3">
                                            <p class="text-xs text-gray-500">Email Alerts</p>
                                            <p class="text-lg font-semibold {{ $settings->email_alerts_enabled ? 'text-emerald-600' : 'text-gray-400' }}">
                                                {{ $settings->email_alerts_enabled ? 'Enabled' : 'Disabled' }}
                                            </p>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500">
                                        Last updated: {{ $settings->updated_at->format('M d, Y H:i') }}
                                    </p>
                                </div>
                            @endif

                            <!-- Action Button -->
                            <div class="mt-6 flex justify-end">
                                <button type="submit" 
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ $hasSettings ? 'Update Settings' : 'Save Settings' }}
                                </button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>

            <!-- Quick Reference Guide -->
            <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-gray-800">Recommended SLA Values</h3>
                        <div class="mt-2 text-sm text-gray-600 space-y-1">
                            <p>• <strong>Standard:</strong> Follow-up: 24 hours, Completion: 72 hours</p>
                            <p>• <strong>Urgent:</strong> Follow-up: 4 hours, Completion: 24 hours</p>
                            <p>• <strong>Relaxed:</strong> Follow-up: 48 hours, Completion: 168 hours (1 week)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

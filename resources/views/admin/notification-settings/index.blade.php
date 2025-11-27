<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Email Notification Settings') }}
            </h2>
            <a href="{{ route('admin.notification-settings.statistics') }}" 
               class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                <i class="fas fa-chart-bar mr-2"></i>View Statistics
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="w-full">
            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
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

            <!-- Statistics Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                                <i class="fas fa-envelope text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Sent</dt>
                                    <dd class="text-2xl font-semibold text-gray-900">{{ number_format($settings->total_sent) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-red-100 rounded-md p-3">
                                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Failed</dt>
                                    <dd class="text-2xl font-semibold text-gray-900">{{ number_format($settings->total_failed) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                                <i class="fas fa-percentage text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Success Rate</dt>
                                    <dd class="text-2xl font-semibold text-gray-900">
                                        @php
                                            $total = $settings->total_sent + $settings->total_failed;
                                            $rate = $total > 0 ? round(($settings->total_sent / $total) * 100, 1) : 0;
                                        @endphp
                                        {{ $rate }}%
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-indigo-100 rounded-md p-3">
                                <i class="fas fa-clock text-indigo-600 text-xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Last Sent</dt>
                                    <dd class="text-sm font-semibold text-gray-900">
                                        @if($settings->last_email_sent_at)
                                            {{ $settings->last_email_sent_at->diffForHumans() }}
                                        @else
                                            Never
                                        @endif
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuration Form -->
            <form action="{{ route('admin.notification-settings.update') }}" method="POST" class="space-y-6">
                @csrf

                <!-- SMTP Configuration -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 bg-purple-100 rounded-md p-2">
                                <i class="fas fa-server text-purple-600"></i>
                            </div>
                            <h3 class="ml-3 text-lg font-semibold text-gray-900">SMTP Server Configuration</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- SMTP Host -->
                            <div>
                                <label for="smtp_host" class="block text-sm font-medium text-gray-700 mb-2">
                                    SMTP Host <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       name="smtp_host" 
                                       id="smtp_host" 
                                       value="{{ old('smtp_host', $settings->smtp_host) }}"
                                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 @error('smtp_host') border-red-500 @enderror"
                                       placeholder="smtp.gmail.com"
                                       required>
                                @error('smtp_host')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- SMTP Port -->
                            <div>
                                <label for="smtp_port" class="block text-sm font-medium text-gray-700 mb-2">
                                    SMTP Port <span class="text-red-500">*</span>
                                </label>
                                <input type="number" 
                                       name="smtp_port" 
                                       id="smtp_port" 
                                       value="{{ old('smtp_port', $settings->smtp_port) }}"
                                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 @error('smtp_port') border-red-500 @enderror"
                                       placeholder="587"
                                       required>
                                @error('smtp_port')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- SMTP Username -->
                            <div>
                                <label for="smtp_username" class="block text-sm font-medium text-gray-700 mb-2">
                                    SMTP Username <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       name="smtp_username" 
                                       id="smtp_username" 
                                       value="{{ old('smtp_username', $settings->smtp_username) }}"
                                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 @error('smtp_username') border-red-500 @enderror"
                                       placeholder="your-email@werkudara.com"
                                       required>
                                @error('smtp_username')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- SMTP Password -->
                            <div>
                                <label for="smtp_password" class="block text-sm font-medium text-gray-700 mb-2">
                                    SMTP Password 
                                    @if($settings->smtp_password)
                                        <span class="text-gray-500 font-normal">(Optional - leave blank to keep current)</span>
                                    @else
                                        <span class="text-red-500">*</span>
                                    @endif
                                </label>
                                <div class="relative">
                                    <input type="password" 
                                           name="smtp_password" 
                                           id="smtp_password" 
                                           value="{{ old('smtp_password') }}"
                                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 @error('smtp_password') border-red-500 @enderror"
                                           placeholder="{{ $settings->smtp_password ? 'Enter new password to change' : 'Enter SMTP password' }}"
                                           {{ $settings->smtp_password ? '' : 'required' }}>
                                    @if($settings->smtp_password)
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                @if($settings->smtp_password)
                                    <p class="mt-1 text-xs text-green-600 flex items-center">
                                        <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Password is currently set and encrypted
                                    </p>
                                @else
                                    <p class="mt-1 text-xs text-gray-500">
                                        For Gmail: Use App Password (requires 2FA)
                                    </p>
                                @endif
                                @error('smtp_password')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- SMTP Encryption -->
                            <div class="md:col-span-2">
                                <label for="smtp_encryption" class="block text-sm font-medium text-gray-700 mb-2">
                                    Encryption Type <span class="text-red-500">*</span>
                                </label>
                                <select name="smtp_encryption" 
                                        id="smtp_encryption" 
                                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 @error('smtp_encryption') border-red-500 @enderror"
                                        required>
                                    <option value="tls" {{ old('smtp_encryption', $settings->smtp_encryption) === 'tls' ? 'selected' : '' }}>TLS (Recommended - Port 587)</option>
                                    <option value="ssl" {{ old('smtp_encryption', $settings->smtp_encryption) === 'ssl' ? 'selected' : '' }}>SSL (Port 465)</option>
                                    <option value="none" {{ old('smtp_encryption', $settings->smtp_encryption) === 'none' ? 'selected' : '' }}>⚠️ None - Unencrypted (Development Only)</option>
                                </select>
                                @error('smtp_encryption')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-2 text-sm text-amber-600">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Security Notice:</strong> Unencrypted SMTP sends credentials in plaintext. Only use for local development or trusted internal networks.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Settings -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 bg-blue-100 rounded-md p-2">
                                <i class="fas fa-at text-blue-600"></i>
                            </div>
                            <h3 class="ml-3 text-lg font-semibold text-gray-900">Email Sender Information</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- From Address -->
                            <div>
                                <label for="mail_from_address" class="block text-sm font-medium text-gray-700 mb-2">
                                    From Email Address <span class="text-red-500">*</span>
                                </label>
                                <input type="email" 
                                       name="mail_from_address" 
                                       id="mail_from_address" 
                                       value="{{ old('mail_from_address', $settings->mail_from_address) }}"
                                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 @error('mail_from_address') border-red-500 @enderror"
                                       placeholder="noreply@werkudara.com"
                                       required>
                                @error('mail_from_address')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- From Name -->
                            <div>
                                <label for="mail_from_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    From Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       name="mail_from_name" 
                                       id="mail_from_name" 
                                       value="{{ old('mail_from_name', $settings->mail_from_name) }}"
                                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 @error('mail_from_name') border-red-500 @enderror"
                                       placeholder="WNS Purchase Request System"
                                       required>
                                @error('mail_from_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notification Options -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 bg-green-100 rounded-md p-2">
                                <i class="fas fa-cog text-green-600"></i>
                            </div>
                            <h3 class="ml-3 text-lg font-semibold text-gray-900">Notification Options</h3>
                        </div>
                        
                        <div class="space-y-6">
                            <!-- Enable Email Notifications -->
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" 
                                           name="email_enabled" 
                                           id="email_enabled" 
                                           value="1"
                                           {{ old('email_enabled', $settings->email_enabled) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </div>
                                <div class="ml-3">
                                    <label for="email_enabled" class="font-medium text-gray-700">Enable Email Notifications</label>
                                    <p class="text-sm text-gray-500">Send email notifications to approvers and requestors</p>
                                </div>
                            </div>

                            <!-- Fallback to Database -->
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" 
                                           name="fallback_to_database" 
                                           id="fallback_to_database" 
                                           value="1"
                                           {{ old('fallback_to_database', $settings->fallback_to_database) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </div>
                                <div class="ml-3">
                                    <label for="fallback_to_database" class="font-medium text-gray-700">Enable Database Fallback</label>
                                    <p class="text-sm text-gray-500">Save notifications to database if email sending fails (Recommended)</p>
                                </div>
                            </div>

                            <!-- Link Expiry Days -->
                            <div>
                                <label for="link_expiry_days" class="block text-sm font-medium text-gray-700 mb-2">
                                    Public Link Expiry (Days) <span class="text-red-500">*</span>
                                </label>
                                <div class="flex items-center space-x-4">
                                    <input type="range" 
                                           name="link_expiry_days" 
                                           id="link_expiry_days" 
                                           min="1" 
                                           max="14" 
                                           value="{{ old('link_expiry_days', $settings->link_expiry_days) }}"
                                           class="flex-1"
                                           oninput="document.getElementById('days-value').textContent = this.value">
                                    <span class="text-lg font-semibold text-gray-900 w-12 text-center">
                                        <span id="days-value">{{ old('link_expiry_days', $settings->link_expiry_days) }}</span> days
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Public approval links will expire after this many days (1-14 days)</p>
                                @error('link_expiry_days')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-4">
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200">
                        <i class="fas fa-save mr-2"></i>Save Settings
                    </button>
                </div>
            </form>

            <!-- Test Email Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0 bg-yellow-100 rounded-md p-2">
                            <i class="fas fa-paper-plane text-yellow-600"></i>
                        </div>
                        <h3 class="ml-3 text-lg font-semibold text-gray-900">Send Test Email</h3>
                    </div>
                    
                    <p class="text-sm text-gray-600 mb-4">
                        Send a test email to verify your SMTP configuration is working correctly. Make sure to save your settings first.
                    </p>

                    <form action="{{ route('admin.notification-settings.test') }}" method="POST" class="flex items-end space-x-4">
                        @csrf
                        <div class="flex-1">
                            <label for="test_email" class="block text-sm font-medium text-gray-700 mb-2">
                                Test Email Address
                            </label>
                            <input type="email" 
                                   name="test_email" 
                                   id="test_email" 
                                   value="{{ old('test_email', auth()->user()->email) }}"
                                   class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="your-email@example.com"
                                   required>
                        </div>
                        <button type="submit" 
                                class="bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200">
                            <i class="fas fa-paper-plane mr-2"></i>Send Test
                        </button>
                    </form>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Important Notes</h3>
                        <div class="mt-2 text-sm text-blue-700 space-y-1">
                            <p>• For Gmail: Use App Password (not your regular password). Enable 2FA first, then generate App Password.</p>
                            <p>• TLS (Port 587) is recommended for most SMTP servers including Gmail, Outlook, and Office 365.</p>
                            <p>• Database fallback ensures notifications are always saved even if email sending fails.</p>
                            <p>• Test your configuration after making changes to ensure emails are delivered correctly.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

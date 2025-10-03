@php
    use Illuminate\Support\Facades\Auth;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-sm text-gray-600 mt-1">Welcome back, {{ Auth::user()->name }}</p>
            </div>
            <div class="text-sm text-gray-500">
                {{ now()->format('l, F j, Y') }}
            </div>
        </div>
    </x-slot>

    <!-- Dashboard Content -->
    <div class="space-y-6 max-w-none">
        <!-- Livewire User Dashboard Component -->
        <livewire:dashboard.user-dashboard />
    </div>
</x-app-layout>

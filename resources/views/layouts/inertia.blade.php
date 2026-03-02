<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Oasis') }}</title>
        <meta name="description" content="Enterprise Office Administration System">

        <!-- Fonts: Plus Jakarta Sans (primary) + Inter (fallback body) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="dns-prefetch" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700|inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        {{-- Ziggy Routes for React --}}
        @routes
        
        {{-- React/Inertia Scripts --}}
        @viteReactRefresh
        @vite(['resources/js/inertia/app.tsx'])
        @inertiaHead
        
        <!-- Prevent layout shift: Read sidebar state before render -->
        <script>
            (function() {
                try {
                    var stored = localStorage.getItem('layout-storage');
                    if (stored) {
                        var data = JSON.parse(stored);
                        if (data.state && data.state.sidebarMinimized) {
                            document.documentElement.classList.add('sidebar-minimized');
                        }
                    }
                } catch (e) {}
            })();
        </script>
        <style>
            /* Initial sidebar state to prevent layout shift */
            .sidebar-minimized [data-sidebar] { width: 4rem !important; }
            .sidebar-minimized .main-content { margin-left: 4rem !important; }
        </style>
    </head>
    <body class="h-full font-inter antialiased bg-gray-50">
        {{-- React/Inertia App - Layout is handled by React AppLayout component --}}
        @inertia
    </body>
</html>

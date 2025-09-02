<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'NumberSys') }} - Enterprise Document Management</title>
        <meta name="description" content="Secure enterprise document numbering and approval system">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="h-full font-inter antialiased">
        {{ $slot }}
        
        @livewireScripts
        
        <!-- Fix Alpine.js and Livewire compatibility -->
        <script>
            // Disable Livewire navigate for login to prevent Alpine.js conflicts
            document.addEventListener('DOMContentLoaded', function() {
                // Ensure Alpine is available and properly initialized
                if (typeof Alpine !== 'undefined') {
                    Alpine.start();
                }
            });
            
            // Handle form submissions without navigate
            document.addEventListener('livewire:init', function() {
                Livewire.hook('morph.updated', ({ el, component }) => {
                    // Re-initialize Alpine components after Livewire updates
                    if (typeof Alpine !== 'undefined' && Alpine.initTree) {
                        Alpine.initTree(el);
                    }
                });
            });
        </script>
    </body>
</html>

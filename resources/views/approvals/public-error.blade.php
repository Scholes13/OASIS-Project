<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Error' }} - Purchase Request Approval</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="antialiased">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full">
            <div class="bg-white rounded-2xl shadow-xl p-12 text-center">
                <!-- Icon -->
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-6
                    @if($color === 'red') bg-red-100
                    @elseif($color === 'green') bg-green-100
                    @elseif($color === 'yellow') bg-yellow-100
                    @elseif($color === 'blue') bg-blue-100
                    @else bg-gray-100
                    @endif">
                    <i class="fas {{ $icon ?? 'fa-exclamation-circle' }} text-4xl
                        @if($color === 'red') text-red-600
                        @elseif($color === 'green') text-green-600
                        @elseif($color === 'yellow') text-yellow-600
                        @elseif($color === 'blue') text-blue-600
                        @else text-gray-600
                        @endif"></i>
                </div>

                <!-- Title -->
                <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $title ?? 'Error' }}</h1>

                <!-- Message -->
                <p class="text-lg text-gray-600 mb-8">{{ $message ?? 'An error occurred.' }}</p>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row justify-center space-y-3 sm:space-y-0 sm:space-x-4">
                    <a href="{{ config('app.url') }}" 
                       class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                        <i class="fas fa-home mr-2"></i>
                        Go to Homepage
                    </a>
                    
                    <a href="javascript:history.back()" 
                       class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Go Back
                    </a>
                </div>

                <!-- Footer -->
                <div class="mt-8 pt-8 border-t border-gray-200">
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-question-circle mr-1"></i>
                        Need help? Contact your system administrator
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

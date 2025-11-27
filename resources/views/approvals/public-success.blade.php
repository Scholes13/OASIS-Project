<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Decision Submitted - Purchase Request Approval</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .confetti {
            animation: confetti-fall 3s ease-in-out;
        }
        @keyframes confetti-fall {
            0% { transform: translateY(-100vh) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }
    </style>
</head>
<body class="antialiased">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl w-full">
            <!-- Success Card -->
            <div class="bg-white rounded-2xl shadow-xl p-12 text-center">
                <!-- Icon -->
                @if($action === 'approved')
                    <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-gradient-to-r from-green-400 to-green-600 mb-6 animate-bounce">
                        <i class="fas fa-check text-white text-5xl"></i>
                    </div>
                @else
                    <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-gradient-to-r from-red-400 to-red-600 mb-6">
                        <i class="fas fa-times text-white text-5xl"></i>
                    </div>
                @endif

                <!-- Title -->
                <h1 class="text-4xl font-bold text-gray-900 mb-4">
                    @if($action === 'approved')
                        Request Approved! 🎉
                    @else
                        Request Rejected
                    @endif
                </h1>

                <!-- Message -->
                <p class="text-lg text-gray-600 mb-8">
                    @if($action === 'approved')
                        Thank you for approving this purchase request. The requestor and next approver (if any) have been notified.
                    @else
                        The purchase request has been rejected. The requestor has been notified with your feedback.
                    @endif
                </p>

                <!-- Details Card -->
                <div class="bg-gray-50 rounded-xl p-6 mb-8 text-left">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        Decision Details
                    </h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-start">
                            <span class="text-sm font-medium text-gray-500">Purchase Request:</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $approval->purchaseRequest->pr_number }}</span>
                        </div>
                        
                        <div class="flex justify-between items-start">
                            <span class="text-sm font-medium text-gray-500">Your Decision:</span>
                            <span class="text-sm font-semibold 
                                @if($action === 'approved') text-green-600
                                @else text-red-600
                                @endif">
                                {{ ucfirst($action) }}
                            </span>
                        </div>
                        
                        <div class="flex justify-between items-start">
                            <span class="text-sm font-medium text-gray-500">Decided At:</span>
                            <span class="text-sm font-semibold text-gray-900">{{ now()->format('M d, Y H:i') }}</span>
                        </div>
                        
                        <div class="flex justify-between items-start">
                            <span class="text-sm font-medium text-gray-500">Amount:</span>
                            <span class="text-sm font-semibold text-gray-900">Rp {{ number_format($approval->purchaseRequest->total_amount, 0, ',', '.') }}</span>
                        </div>

                        @if($notes)
                            <div class="pt-3 border-t border-gray-200">
                                <span class="text-sm font-medium text-gray-500 block mb-2">Your Notes:</span>
                                <p class="text-sm text-gray-700 bg-white rounded-lg p-3 border border-gray-200">{{ $notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Next Steps -->
                @if($action === 'approved')
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-8 text-left">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-500"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800 mb-1">What happens next?</h3>
                                <p class="text-sm text-blue-700">
                                    @if($approval->step_order < $approval->purchaseRequest->approvals()->count())
                                        The next approver will receive an email notification to continue the approval process.
                                    @else
                                        The requestor will be notified that all approvals are complete.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-8 text-left">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800 mb-1">What happens next?</h3>
                                <p class="text-sm text-yellow-700">
                                    The requestor will receive an email notification about the rejection and your feedback. They can edit and resubmit the request if needed.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row justify-center space-y-3 sm:space-y-0 sm:space-x-4">
                    <a href="{{ config('app.url') }}" 
                       class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 shadow-lg">
                        <i class="fas fa-home mr-2"></i>
                        Close This Page
                    </a>
                </div>

                <!-- Footer -->
                <div class="mt-8 pt-8 border-t border-gray-200">
                    <p class="text-sm text-gray-500 flex items-center justify-center">
                        <i class="fas fa-check-circle mr-2 text-green-500"></i>
                        Your decision has been recorded and cannot be changed
                    </p>
                </div>
            </div>

            <!-- Confirmation Message -->
            <div class="mt-6 text-center">
                <p class="text-white text-sm">
                    <i class="fas fa-envelope mr-2"></i>
                    An email confirmation has been sent to all relevant parties
                </p>
            </div>
        </div>
    </div>

    @if($action === 'approved')
        <!-- Confetti Animation (simple version) -->
        <script>
            // Create simple confetti effect
            for (let i = 0; i < 30; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.cssText = `
                        position: fixed;
                        width: 10px;
                        height: 10px;
                        background: ${['#ff6b6b', '#4ecdc4', '#45b7d1', '#f9ca24', '#6c5ce7'][Math.floor(Math.random() * 5)]};
                        left: ${Math.random() * 100}vw;
                        top: -10px;
                        border-radius: 50%;
                        pointer-events: none;
                        z-index: 9999;
                    `;
                    document.body.appendChild(confetti);
                    setTimeout(() => confetti.remove(), 3000);
                }, i * 100);
            }
        </script>
    @endif
</body>
</html>

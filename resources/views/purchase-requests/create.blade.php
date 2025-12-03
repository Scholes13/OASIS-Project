@php
    use Illuminate\Support\Facades\Auth;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Create Purchase Request</h1>
                <p class="text-sm text-gray-600 mt-1">Create a new purchase request for {{ session('current_business_unit_name') }}</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('purchase-requests.index') }}" 
                   wire:navigate
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to List
                </a>
            </div>
        </div>
    </x-slot>

    <x-slot name="breadcrumbs">
        <li class="flex">
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}" wire:navigate class="text-gray-400 hover:text-gray-500">
                    <svg class="flex-shrink-0 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2z"></path>
                    </svg>
                    <span class="sr-only">Dashboard</span>
                </a>
            </div>
        </li>
        <li class="flex">
            <div class="flex items-center">
                <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
                <a href="{{ route('purchase-requests.index') }}" wire:navigate class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">
                    Purchase Requests
                </a>
            </div>
        </li>
        <li class="flex">
            <div class="flex items-center">
                <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="ml-4 text-sm font-medium text-gray-500">Create New</span>
            </div>
        </li>
    </x-slot>

    <!-- Purchase Request Create Form -->
    <livewire:modules.purchase-request.create />
    
    {{-- INLINE SCRIPT: Global calculation functions for PR items --}}
    <script>
        console.log('🎯 PR CREATE PAGE SCRIPT LOADED');
        
        // Format number with Indonesian locale
        window.formatNumber = function(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        };
        
        // Calculate grand total from all row totals
        window.calculateGrandTotal = function() {
            let grandTotal = 0;
            
            document.querySelectorAll('span[id^="total-"]').forEach(function(span) {
                if (span.id === 'js-grand-total' || span.id === 'grand-total') return;
                const val = parseFloat(span.dataset.value) || 0;
                grandTotal += val;
            });
            
            // Try both possible IDs
            let grandTotalElement = document.getElementById('js-grand-total') || document.getElementById('grand-total');
            if (grandTotalElement) {
                grandTotalElement.dataset.value = grandTotal;
                grandTotalElement.textContent = window.formatNumber(grandTotal);
                grandTotalElement.style.color = '#4f46e5';
                setTimeout(() => { grandTotalElement.style.color = ''; }, 200);
            }
            
            console.log('💰 Grand total:', grandTotal);
            return grandTotal;
        };
        
        // Calculate row total - called from oninput with 'this' as element
        window.calculateRowTotal = function(index, element) {
            console.log('🔢 calculateRowTotal:', index, 'hasElement:', !!element);
            
            let row = null;
            
            // Method 1: Get row from the element that triggered the event
            if (element && typeof element.closest === 'function') {
                row = element.closest('tr');
            }
            
            // Method 2: If element is a string or number, find by total span id
            if (!row && (typeof index === 'number' || typeof index === 'string')) {
                const totalSpan = document.getElementById('total-' + index);
                if (totalSpan) {
                    row = totalSpan.closest('tr');
                }
            }
            
            if (!row) {
                console.log('❌ No row found');
                return;
            }
            
            // Find inputs by class within this row
            const qtyInput = row.querySelector('.item-quantity');
            const priceInput = row.querySelector('.item-price');
            const totalSpan = row.querySelector('span[id^="total-"]');
            
            console.log('  qtyInput:', !!qtyInput, qtyInput ? qtyInput.value : 'N/A');
            console.log('  priceInput:', !!priceInput, priceInput ? priceInput.value : 'N/A');
            console.log('  totalSpan:', !!totalSpan);
            
            if (qtyInput && priceInput && totalSpan) {
                const qty = parseFloat(qtyInput.value.replace(/[^0-9.]/g, '')) || 0;
                const price = parseFloat(priceInput.value.replace(/[^0-9.]/g, '')) || 0;
                const total = Math.round(qty * price);
                
                console.log('🧮 Calc:', qty, 'x', price, '=', total);
                
                totalSpan.dataset.value = total;
                totalSpan.textContent = window.formatNumber(total);
                totalSpan.style.color = '#4f46e5';
                setTimeout(() => { totalSpan.style.color = ''; }, 200);
                
                window.calculateGrandTotal();
            } else {
                console.log('❌ Missing elements in row');
            }
        };
        
        // Recalculate all totals - only updates if values exist
        window.recalculateAllTotals = function(forceUpdate = false) {
            console.log('🔄 recalculateAllTotals, force:', forceUpdate);
            
            // Find all item rows by looking for total spans
            const totalSpans = document.querySelectorAll('span[id^="total-"]:not(#js-grand-total):not(#grand-total)');
            console.log('  Found total spans:', totalSpans.length);
            
            let hasCalculations = false;
            
            totalSpans.forEach(function(span) {
                const row = span.closest('tr');
                if (!row) return;
                
                const qtyInput = row.querySelector('.item-quantity');
                const priceInput = row.querySelector('.item-price');
                
                if (qtyInput && priceInput) {
                    const qty = parseFloat(qtyInput.value.replace(/[^0-9.]/g, '')) || 0;
                    const price = parseFloat(priceInput.value.replace(/[^0-9.]/g, '')) || 0;
                    
                    // Only update if we have actual values or forcing update
                    if ((qty > 0 && price > 0) || forceUpdate) {
                        const total = Math.round(qty * price);
                        span.dataset.value = total;
                        span.textContent = window.formatNumber(total);
                        console.log('  Row:', span.id, qty, 'x', price, '=', total);
                        hasCalculations = true;
                    } else if (span.dataset.value && parseFloat(span.dataset.value) > 0) {
                        // Keep existing value if it's already calculated
                        hasCalculations = true;
                        console.log('  Row:', span.id, 'keeping existing value:', span.dataset.value);
                    }
                }
            });
            
            if (hasCalculations) {
                window.calculateGrandTotal();
            }
        };
        
        // Button state functions
        window.showSavingState = function(button) {
            if (button) { button.disabled = true; button.classList.add('opacity-75'); }
        };
        window.showSubmittingState = function(button) {
            if (button) { button.disabled = true; button.classList.add('opacity-75'); }
        };
        window.showResubmittingState = function(button) {
            if (button) { button.disabled = true; button.classList.add('opacity-75'); }
        };
        
        // Init on various events
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📌 DOMContentLoaded');
            setTimeout(window.recalculateAllTotals, 500);
        });
        
        window.addEventListener('load', function() {
            console.log('📌 Window load');
            setTimeout(window.recalculateAllTotals, 500);
        });
        
        document.addEventListener('livewire:initialized', function() {
            console.log('📌 Livewire initialized');
            setTimeout(window.recalculateAllTotals, 500);
        });
        
        document.addEventListener('livewire:navigated', function() {
            console.log('📌 Livewire navigated');
            setTimeout(window.recalculateAllTotals, 500);
        });
        
        console.log('✅ All PR calculation functions ready');
    </script>
</x-app-layout>
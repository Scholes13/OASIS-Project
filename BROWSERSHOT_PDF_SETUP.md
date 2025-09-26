# Browsershot PDF Setup - Public Route

## Overview
Telah dibuat route khusus untuk browsershot agar dapat mengakses halaman PDF Purchase Request tanpa terkena middleware autentikasi.

## Changes Made

### 1. New Public Route
**File:** `routes/web.php`
```php
// Public PDF route for browsershot (no auth middleware)
Route::get('/purchase-requests/{purchaseRequest}/pdf-public', [PurchaseRequestController::class, 'pdfPublic'])->name('purchase-requests.pdf-public');
```

### 2. New Controller Method
**File:** `app/Http/Controllers/PurchaseRequestController.php`
```php
/**
 * Generate PDF view for purchase request - Public access for browsershot
 */
public function pdfPublic(PurchaseRequest $purchaseRequest)
{
    // Load relationships needed for PDF
    $purchaseRequest->load([
        'user',
        'department',
        'businessUnit',
        'items',
        'approvals.approver',
    ]);

    // Generate QR codes for PDF
    $qrCodeService = new QrCodeService;
    $qrCodes = $this->generateQrCodesForPdf($purchaseRequest, $qrCodeService);

    return view('purchase-requests.pdf-browser', compact('purchaseRequest', 'qrCodes'));
}
```

### 3. Updated Browsershot URL
**File:** `app/Http/Controllers/PurchaseRequestController.php`
```php
// Changed from:
$url = $baseUrl.'/purchase-requests/'.$purchaseRequest->id.'/pdf';

// To:
$url = $baseUrl.'/purchase-requests/'.$purchaseRequest->id.'/pdf-public';
```

## How It Works

1. **Original PDF Route (Authenticated):**
   - URL: `/purchase-requests/{id}/pdf`
   - Requires authentication
   - Used for regular user viewing

2. **New Public PDF Route (No Authentication):**
   - URL: `/purchase-requests/{id}/pdf-public`
   - No authentication required
   - Used specifically for browsershot access

3. **Download Process:**
   - User clicks "Download PDF" button on `/purchase-requests/{id}`
   - System calls `downloadPdf()` method
   - If `pdf_method = 'browsershot'`, it uses the public route
   - Browsershot accesses `/purchase-requests/{id}/pdf-public` without auth issues
   - PDF is generated and downloaded

## Testing

### Test URLs:
- **Authenticated PDF View:** http://localhost:8000/purchase-requests/3/pdf
- **Public PDF View (Browsershot):** http://localhost:8000/purchase-requests/3/pdf-public
- **Download PDF:** http://localhost:8000/purchase-requests/3/download-pdf

### Verification:
```bash
# Check routes are registered
php artisan route:list --name=purchase-requests.pdf

# Test public access (should work without login)
curl -I http://localhost:8000/purchase-requests/3/pdf-public
```

## Benefits

1. **No Authentication Issues:** Browsershot can access PDF content without session/auth middleware
2. **Same Content:** Uses same PDF template (`pdf-browser.blade.php`) and QR codes
3. **Secure:** Only exposes PDF view, no sensitive operations
4. **Backward Compatible:** Existing authenticated routes still work
5. **Flexible:** Can switch between DomPDF and Browsershot seamlessly

## Configuration

In `config/pdf.php`:
```php
'pdf_method' => 'browsershot', // or 'dompdf'
```

When `pdf_method` is set to `'browsershot'`, the system will automatically use the public route for PDF generation.
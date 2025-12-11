# Design Document: Purchasing Module Refactor

## Overview

Refactoring ini bertujuan untuk mengkonsolidasikan struktur folder Purchasing Module agar konsisten di seluruh layer aplikasi. Saat ini `PurchaseRequest` dan `StockRequest` berada sebagai folder terpisah di level yang sama dengan `Purchasing`, padahal seharusnya keduanya merupakan sub-module dari `Purchasing`.

## Architecture

### Current Structure (Problematic)

```
app/
├── Models/Modules/
│   ├── PurchaseRequest/     ❌ Standalone
│   ├── StockRequest/        ❌ Standalone
│   ├── Purchasing/          ⚠️ Partial (empty subfolders)
│   └── SalesCrm/            ✅ OK
│
├── Livewire/Modules/
│   ├── PurchaseRequest/     ❌ Standalone
│   ├── StockRequest/        ❌ Standalone
│   ├── Purchasing/          ⚠️ Partial
│   └── SalesCrm/            ✅ OK
│
├── Http/Controllers/Modules/
│   ├── PurchaseRequest/     ❌ Standalone
│   ├── StockRequest/        ❌ Standalone
│   └── Purchasing/          ⚠️ Partial
│
├── Services/Modules/
│   ├── PurchaseRequest/     ❌ Standalone
│   ├── StockRequest/        ❌ Standalone
│   └── SalesCrm/            ✅ OK
│
└── Notifications/
    ├── PurchaseRequest/     ❌ Standalone
    └── StockRequest/        ❌ Standalone
```

### Target Structure

```
app/
├── Models/Modules/
│   ├── Purchasing/
│   │   ├── PurchaseRequest/
│   │   └── StockRequest/
│   └── SalesCrm/
│
├── Livewire/Modules/
│   ├── Purchasing/
│   │   ├── PurchaseRequest/
│   │   ├── StockRequest/
│   │   └── AllRequests.php
│   └── SalesCrm/
│
├── Http/Controllers/Modules/
│   └── Purchasing/
│       ├── PurchaseRequest/
│       ├── StockRequest/
│       └── PurchasingController.php
│
├── Services/Modules/
│   ├── Purchasing/
│   │   ├── PurchaseRequest/
│   │   └── StockRequest/
│   └── SalesCrm/
│
└── Notifications/
    └── Purchasing/
        ├── PurchaseRequest/
        └── StockRequest/
```

## Components and Interfaces

### Namespace Mapping

| Layer | Old Namespace | New Namespace |
|-------|---------------|---------------|
| Models | `App\Models\Modules\PurchaseRequest` | `App\Models\Modules\Purchasing\PurchaseRequest` |
| Models | `App\Models\Modules\StockRequest` | `App\Models\Modules\Purchasing\StockRequest` |
| Controllers | `App\Http\Controllers\Modules\PurchaseRequest` | `App\Http\Controllers\Modules\Purchasing\PurchaseRequest` |
| Controllers | `App\Http\Controllers\Modules\StockRequest` | `App\Http\Controllers\Modules\Purchasing\StockRequest` |
| Livewire | `App\Livewire\Modules\PurchaseRequest` | `App\Livewire\Modules\Purchasing\PurchaseRequest` |
| Livewire | `App\Livewire\Modules\StockRequest` | `App\Livewire\Modules\Purchasing\StockRequest` |
| Services | `App\Services\Modules\PurchaseRequest` | `App\Services\Modules\Purchasing\PurchaseRequest` |
| Services | `App\Services\Modules\StockRequest` | `App\Services\Modules\Purchasing\StockRequest` |
| Notifications | `App\Notifications\PurchaseRequest` | `App\Notifications\Purchasing\PurchaseRequest` |
| Notifications | `App\Notifications\StockRequest` | `App\Notifications\Purchasing\StockRequest` |

### View Path Mapping

| Old Path | New Path |
|----------|----------|
| `purchase-requests.*` | `purchasing.purchase-requests.*` |
| `stock-requests.*` | `purchasing.stock-requests.*` |
| `approvals.*` | `purchasing.approvals.purchase-request.*` |
| `stock-approvals.*` | `purchasing.approvals.stock-request.*` |
| `emails.purchase-request.*` | `emails.purchasing.purchase-request.*` |
| `emails.stock-request.*` | `emails.purchasing.stock-request.*` |
| `livewire.modules.purchase-request.*` | `livewire.modules.purchasing.purchase-request.*` |
| `livewire.modules.stock-request.*` | `livewire.modules.purchasing.stock-request.*` |

## Data Models

Tidak ada perubahan pada struktur data/database. Refactoring ini hanya mengubah lokasi file dan namespace PHP.

### Files to Move

#### Models (5 + 4 = 9 files)
- PurchaseRequest: `PrApproval.php`, `PrCategory.php`, `PrItem.php`, `PrNumberReservation.php`, `PurchaseRequest.php`
- StockRequest: `StockApproval.php`, `StockItem.php`, `StockNumberReservation.php`, `StockRequest.php`

#### Controllers (4 + 2 = 6 files)
- PurchaseRequest: `ApprovalController.php`, `PurchaseRequestController.php`, `Api/ApprovalController.php`, `Api/PurchaseRequestController.php`
- StockRequest: `StockApprovalController.php`, `StockRequestController.php`

#### Livewire (4 + 2 = 6 files)
- PurchaseRequest: `AllRequests.php`, `ApprovalsIndex.php`, `Create.php`, `MyPurchaseRequests.php`
- StockRequest: `Create.php`, `MyStockRequests.php`

#### Services (3 + 1 = 4 files)
- PurchaseRequest: `ApprovalWorkflowService.php`, `PurchaseRequestService.php`, `UniversalPRNumberingService.php`
- StockRequest: `UniversalStockNumberingService.php`

#### Notifications (3 + 1 = 4 files)
- PurchaseRequest: `ApprovalCompleted.php`, `ApprovalRejected.php`, `ApprovalRequested.php`
- StockRequest: `ApprovalRequested.php`

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

Karena refactoring ini adalah perubahan struktural (bukan fungsional), correctness properties difokuskan pada verifikasi bahwa:
1. Semua file dipindahkan ke lokasi yang benar
2. Semua referensi diupdate dengan benar
3. Aplikasi tetap berfungsi seperti sebelumnya

### Property 1: Application Boot Success
*For any* Laravel application state after refactoring, booting the application (running `php artisan`) SHALL complete without class not found errors.
**Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5**

### Property 2: Route Resolution
*For any* defined route in web.php that references Purchasing module controllers, the route SHALL resolve to the correct controller action without errors.
**Validates: Requirements 2.3**

### Property 3: Test Suite Pass
*For any* existing test in the test suite, running `php artisan test` SHALL produce the same pass/fail result as before refactoring.
**Validates: Requirements 5.1, 5.2, 5.3, 5.4**

## Error Handling

### Potential Errors During Refactoring

1. **Class Not Found**: Jika ada referensi yang terlewat diupdate
   - Mitigation: Gunakan grep untuk mencari semua referensi sebelum menghapus folder lama
   
2. **View Not Found**: Jika ada view path yang terlewat diupdate
   - Mitigation: Gunakan grep untuk mencari semua `view()` calls
   
3. **Route Not Found**: Jika controller namespace tidak diupdate di routes
   - Mitigation: Update routes/web.php secara eksplisit

### Rollback Strategy

- Commit semua perubahan sebelum memulai refactoring
- Jika terjadi error, dapat rollback dengan `git checkout .`

## Testing Strategy

### Dual Testing Approach

#### Unit Tests
- Tidak diperlukan unit test baru karena ini adalah refactoring struktural
- Existing unit tests harus tetap pass setelah refactoring

#### Integration/Feature Tests
- Run existing feature tests untuk memastikan fungsionalitas tidak berubah
- Manual testing pada halaman-halaman utama:
  - Purchase Request list, create, show, edit
  - Stock Request list, create, show, edit
  - Approval workflows
  - Email notifications

### Verification Steps

1. **Pre-refactoring**: Run `php artisan test` dan catat hasilnya
2. **Post-refactoring**: Run `php artisan test` dan bandingkan hasilnya
3. **Cache Clear**: Run semua cache clear commands
4. **IDE Helper**: Regenerate IDE helper files

### Property-Based Testing

Untuk refactoring ini, property-based testing tidak diperlukan karena:
- Tidak ada logic baru yang ditambahkan
- Perubahan hanya pada lokasi file dan namespace
- Verifikasi cukup dengan menjalankan existing test suite

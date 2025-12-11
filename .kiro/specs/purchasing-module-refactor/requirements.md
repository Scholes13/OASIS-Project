# Requirements Document

## Introduction

Refactoring struktur folder Purchasing Module untuk menyelaraskan organisasi kode. Saat ini `PurchaseRequest` dan `StockRequest` berada sebagai folder terpisah di level yang sama dengan `Purchasing`, padahal seharusnya keduanya merupakan sub-module dari `Purchasing`. Refactoring ini akan mengkonsolidasikan semua komponen terkait purchasing ke dalam satu struktur hierarki yang konsisten.

## Glossary

- **Purchasing Module**: Modul utama yang menangani semua proses pengadaan termasuk Purchase Request dan Stock Request
- **Purchase Request (PR)**: Sub-module untuk permintaan pembelian barang/jasa
- **Stock Request (ST)**: Sub-module untuk permintaan stok/inventaris
- **Namespace**: PHP namespace yang menentukan lokasi logis dari class
- **View Path**: Lokasi file Blade template di folder resources/views

## Requirements

### Requirement 1

**User Story:** As a developer, I want consistent folder structure across all layers, so that I can easily navigate and maintain the codebase.

#### Acceptance Criteria

1. WHEN a developer looks at the Models folder THEN the System SHALL organize PurchaseRequest and StockRequest models under `Modules/Purchasing/` namespace
2. WHEN a developer looks at the Livewire folder THEN the System SHALL organize PurchaseRequest and StockRequest components under `Modules/Purchasing/` namespace
3. WHEN a developer looks at the Controllers folder THEN the System SHALL organize PurchaseRequest and StockRequest controllers under `Modules/Purchasing/` namespace
4. WHEN a developer looks at the Services folder THEN the System SHALL organize PurchaseRequest and StockRequest services under `Modules/Purchasing/` namespace
5. WHEN a developer looks at the Views folder THEN the System SHALL organize purchase-request and stock-request views under `livewire/modules/purchasing/` path

### Requirement 2

**User Story:** As a developer, I want all namespace references updated correctly, so that the application continues to work after refactoring.

#### Acceptance Criteria

1. WHEN models are moved to new namespace THEN the System SHALL update all `use` statements referencing the old namespace
2. WHEN Livewire components are moved THEN the System SHALL update all route references and view paths
3. WHEN controllers are moved THEN the System SHALL update all route definitions in web.php
4. WHEN services are moved THEN the System SHALL update all dependency injection references
5. WHEN views are moved THEN the System SHALL update all `@livewire` and `view()` references

### Requirement 3

**User Story:** As a developer, I want the old folder structure removed, so that there is no confusion about which files to use.

#### Acceptance Criteria

1. WHEN refactoring is complete THEN the System SHALL remove the standalone `PurchaseRequest` folder from Models/Modules
2. WHEN refactoring is complete THEN the System SHALL remove the standalone `StockRequest` folder from Models/Modules
3. WHEN refactoring is complete THEN the System SHALL remove the standalone `PurchaseRequest` folder from Livewire/Modules
4. WHEN refactoring is complete THEN the System SHALL remove the standalone `StockRequest` folder from Livewire/Modules
5. WHEN refactoring is complete THEN the System SHALL remove the standalone `PurchaseRequest` folder from Services/Modules
6. WHEN refactoring is complete THEN the System SHALL remove the standalone `StockRequest` folder from Services/Modules

### Requirement 4

**User Story:** As a developer, I want the steering documentation updated, so that future development follows the correct structure.

#### Acceptance Criteria

1. WHEN refactoring is complete THEN the System SHALL update `.kiro/steering/structure.md` to reflect the new folder organization
2. WHEN refactoring is complete THEN the System SHALL update `.kiro/steering/product.md` if module structure is documented there

### Requirement 5

**User Story:** As a user, I want the application to work exactly as before, so that my workflow is not disrupted.

#### Acceptance Criteria

1. WHEN accessing Purchase Request pages THEN the System SHALL display the same functionality as before refactoring
2. WHEN accessing Stock Request pages THEN the System SHALL display the same functionality as before refactoring
3. WHEN accessing Purchasing dashboard THEN the System SHALL display combined data from both sub-modules
4. WHEN approval workflows are triggered THEN the System SHALL process them correctly with new namespaces

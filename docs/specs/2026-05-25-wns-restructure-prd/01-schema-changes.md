# 01 - Schema Changes

Status: draft
Depends on: 00-overview

## Migration: `parent_department_id`

File baru:
```
database/migrations/2026_05_25_100000_add_parent_department_id_to_departments_table.php
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('parent_department_id')
                ->nullable()
                ->after('business_unit_id')
                ->constrained('departments')
                ->nullOnDelete();

            $table->index('parent_department_id');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['parent_department_id']);
            $table->dropIndex(['parent_department_id']);
            $table->dropColumn('parent_department_id');
        });
    }
};
```

## Constraint Rules

Hard constraints (enforced at model + form request level, bukan via DB check):

1. **Same BU constraint** — `parent.business_unit_id` harus sama dengan `child.business_unit_id`. Division tidak boleh lintas BU.
2. **Max 1 level nesting** — sub-dept tidak boleh punya sub-sub-dept. `parent.parent_department_id` harus `null`.
3. **No self-reference** — `id !== parent_department_id`.
4. **Soft cycle prevention** — karena max 1 level, cycle tidak mungkin secara struktural. Tetap validasi di Form Request.

Implementasi di `Department` model (`booted` hook):

```php
static::saving(function (self $dept) {
    if ($dept->parent_department_id === $dept->id) {
        throw new \DomainException('Department cannot be its own parent.');
    }

    if ($dept->parent_department_id) {
        $parent = Department::find($dept->parent_department_id);
        if (! $parent) {
            throw new \DomainException('Parent department not found.');
        }
        if ($parent->business_unit_id !== $dept->business_unit_id) {
            throw new \DomainException('Parent must be in the same business unit.');
        }
        if ($parent->parent_department_id !== null) {
            throw new \DomainException('Sub-department cannot have a sub-department.');
        }
    }
});
```

## Model Updates: `app/Models/Core/Department.php`

### Tambah ke `$fillable`

```php
protected $fillable = [
    'business_unit_id',
    'parent_department_id',  // NEW
    'code',
    'name',
    'is_active',
    'is_purchasing_department',
    'default_purchasing_admin_id',
];
```

### Relasi baru

```php
public function parent(): BelongsTo
{
    return $this->belongsTo(self::class, 'parent_department_id');
}

public function children(): HasMany
{
    return $this->hasMany(self::class, 'parent_department_id');
}

public function activeChildren(): HasMany
{
    return $this->children()->where('is_active', true);
}
```

### Helper methods

```php
public function isRootDepartment(): bool
{
    return $this->parent_department_id === null;
}

public function isSubDepartment(): bool
{
    return $this->parent_department_id !== null;
}

/**
 * Returns IDs of self + active direct children.
 * Used for scoping queries (Activity tasks, Cashflow line items).
 */
public function descendantIds(): array
{
    return [
        $this->id,
        ...$this->activeChildren()->pluck('id')->all(),
    ];
}
```

### Scope baru

```php
public function scopeRootOnly($query)
{
    return $query->whereNull('parent_department_id');
}

public function scopeSubOnly($query)
{
    return $query->whereNotNull('parent_department_id');
}
```

### Update `getFullNameAttribute`

```php
public function getFullNameAttribute(): string
{
    if (! $this->businessUnit) {
        return $this->name;
    }

    $base = $this->businessUnit->name . ' - ' . $this->name;

    if ($this->parent_department_id && $this->parent) {
        return $this->businessUnit->name . ' - ' . $this->parent->name . ' / ' . $this->name;
    }

    return $base;
}
```

Contoh hasil:
- Root: `"Werkudara Nirwana Sakti - Sales & Marketing"`
- Sub: `"Werkudara Nirwana Sakti - Sales & Marketing / Business Solutions"`

### Update `ensureDefaultPositions`

Saat ini auto-create 4 position (EXEC/HOD/LEAD/STAFF) untuk SETIAP department baru. Perubahan:

- **Root department** (parent dari sub-dept) — biasanya tidak butuh STAFF, hanya GM/HOD-equivalent. Default-nya tetap 4 position untuk backward compat, tapi bisa di-skip via parameter:

```php
protected static function booted(): void
{
    static::created(function (self $department) {
        if (! $department->parent_department_id) {
            // Root dept: tetap auto-create default positions
            $department->ensureDefaultPositions();
        }
        // Sub-dept: position di-define manual via seeder atau admin UI
    });
}
```

Sub-department dapat custom position lewat seeder atau UI. Tidak auto-generate karena role di sub-dept (Manager, Specialist, Engineer) tidak fit di template default.

## Tabel `positions` — tidak ada schema change

Position table sudah punya `department_id` sebagai FK ke `departments`. Sub-dept langsung bisa punya position-nya sendiri tanpa migrasi tambahan.

## Backward Compatibility

- Semua dept lama otomatis dapat `parent_department_id = NULL` → tetap dianggap root department.
- Query `Department::where('business_unit_id', $buId)` masih bekerja, tapi akan return root + sub-dept campur. Untuk filter root saja: `->rootOnly()`.
- Seeder lama yang panggil `Department::firstOrCreate([...])` tidak terpengaruh karena `parent_department_id` opsional.

## Risk

| Risk | Mitigation |
|------|------------|
| Query lama yang loop semua dept WNS akan termasuk sub-dept (mungkin double-count). | Audit semua `Department::forBusinessUnit($id)` callsite. Tambah `->rootOnly()` di tempat yang perlu. Lihat Section 06. |
| Department switcher di navbar nampilin 14 dept WNS (11 lama + S&M + 3 sub) → membingungkan. | Frontend perlu group by parent. Lihat Section 06. |
| `descendantIds()` dipanggil per-request → query ekstra. | Cache di `availableDepartments` Inertia props (di `HandleInertiaRequests`). |

## Verification

```bash
php artisan migrate --pretend  # cek SQL yang akan dijalankan
php artisan migrate
php artisan test tests/Feature/Core/DepartmentDeleteTest.php
```

Sanity check via tinker:
```php
$sm = Department::create([
    'business_unit_id' => $wns->id,
    'code' => 'SM',
    'name' => 'Sales & Marketing',
]);
$bsd = Department::create([
    'business_unit_id' => $wns->id,
    'parent_department_id' => $sm->id,
    'code' => 'BSD',
    'name' => 'Business Solutions',
]);

$bsd->parent->name;  // "Sales & Marketing"
$sm->descendantIds();  // [SM_id, BSD_id]

// Validation tests
Department::create([
    'business_unit_id' => $mrp->id,
    'parent_department_id' => $sm->id,  // beda BU
    'code' => 'X',
    'name' => 'X',
]);  // expect: DomainException

Department::create([
    'business_unit_id' => $wns->id,
    'parent_department_id' => $bsd->id,  // sub of sub
    'code' => 'Y',
    'name' => 'Y',
]);  // expect: DomainException
```

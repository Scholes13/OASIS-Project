<?php

namespace App\Services\Modules\SalesCrm;

use App\Models\Core\BusinessUnit;
use App\Models\Core\User;
use App\Models\Modules\SalesCrm\Contact;
use App\Models\Modules\SalesCrm\ContactSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContactService
{
    /**
     * Create a manual contact (without activity)
     */
    public function createManualContact(array $data): Contact
    {
        DB::beginTransaction();

        try {
            $businessUnitId = $data['business_unit_id'] ?? session('current_business_unit_id');

            // Generate contact code
            $code = $this->generateContactCode($businessUnitId);

            // Create contact
            $contact = Contact::create([
                'business_unit_id' => $businessUnitId,
                'code' => $code,
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'mobile' => $data['mobile'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'company' => $data['company'] ?? null,
                'department' => $data['department'] ?? null,
                'position' => $data['position'] ?? null,
                'social_media' => $data['social_media'] ?? null,
                'status' => $data['status'] ?? 'active',
                'category' => $data['category'] ?? 'lead',
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
                'assigned_to' => $data['assigned_to'] ?? Auth::id(),
            ]);

            // Create source record for manual entry
            ContactSource::create([
                'contact_id' => $contact->id,
                'source_type' => 'manual',
                'source_user_id' => Auth::id(),
                'source_notes' => $data['source_notes'] ?? 'Direct manual input',
                'source_date' => now()->toDateString(),
            ]);

            DB::commit();

            return $contact->load('source');

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing contact
     */
    public function updateContact(Contact $contact, array $data): Contact
    {
        DB::beginTransaction();

        try {
            $contact->update($data);

            DB::commit();

            return $contact->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a contact (soft delete)
     */
    public function deleteContact(Contact $contact): bool
    {
        return $contact->delete();
    }

    /**
     * Generate unique contact code
     * Format: CONT-{BU_CODE}-{YY}-{NNNNN}
     * Example: CONT-WNS-25-00001
     */
    public function generateContactCode(int $businessUnitId): string
    {
        $businessUnit = BusinessUnit::find($businessUnitId);
        $year = date('y'); // 2-digit year

        // Get last contact number for this BU in current year
        $lastContact = Contact::where('business_unit_id', $businessUnitId)
            ->whereYear('created_at', date('Y'))
            ->latest('id')
            ->first();

        // Extract number from last code or start from 1
        $nextNumber = 1;
        if ($lastContact && $lastContact->code) {
            // Extract last 5 digits from code (CONT-WNS-25-00001 -> 00001)
            $lastNumber = (int) substr($lastContact->code, -5);
            $nextNumber = $lastNumber + 1;
        }

        return sprintf('CONT-%s-%s-%05d', $businessUnit->code, $year, $nextNumber);
    }

    /**
     * Assign contact to a different user
     */
    public function assignContact(Contact $contact, User $user): Contact
    {
        $contact->update(['assigned_to' => $user->id]);

        return $contact->fresh();
    }

    /**
     * Get contacts for a specific user with filters
     */
    public function getContactsForUser(User $user, array $filters = []): Collection
    {
        $query = Contact::query()
            ->with(['source', 'assignedTo:id,name', 'createdBy:id,name'])
            ->where('assigned_to', $user->id)
            ->where('business_unit_id', session('current_business_unit_id'));

        // Apply filters
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['company'])) {
            $query->where('company', 'like', "%{$filters['company']}%");
        }

        return $query->latest('created_at')->get();
    }

    /**
     * Get all contacts for business unit (admin view)
     */
    public function getAllContacts(array $filters = []): Collection
    {
        $query = Contact::query()
            ->with(['source', 'assignedTo:id,name', 'createdBy:id,name'])
            ->where('business_unit_id', session('current_business_unit_id'));

        // Apply filters (same as getContactsForUser)
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query->latest('created_at')->get();
    }

    /**
     * Get contact statistics
     */
    public function getContactStats(?User $user = null): array
    {
        $businessUnitId = session('current_business_unit_id');

        $query = Contact::query()->where('business_unit_id', $businessUnitId);

        if ($user) {
            $query->where('assigned_to', $user->id);
        }

        return [
            'total_contacts' => $query->count(),
            'active' => (clone $query)->where('status', 'active')->count(),
            'leads' => (clone $query)->where('category', 'lead')->count(),
            'prospects' => (clone $query)->where('category', 'prospect')->count(),
            'customers' => (clone $query)->where('category', 'customer')->count(),
            'partners' => (clone $query)->where('category', 'partner')->count(),
            'from_activity' => Contact::whereHas('source', function ($q) {
                $q->where('source_type', 'activity');
            })->where('business_unit_id', $businessUnitId)->count(),
            'manual_entry' => Contact::whereHas('source', function ($q) {
                $q->where('source_type', 'manual');
            })->where('business_unit_id', $businessUnitId)->count(),
        ];
    }

    /**
     * Bulk import contacts
     * (Future feature - placeholder)
     */
    public function importContacts(array $rows): array
    {
        DB::beginTransaction();

        try {
            $imported = [];
            $businessUnitId = session('current_business_unit_id');

            foreach ($rows as $row) {
                // Generate code
                $code = $this->generateContactCode($businessUnitId);

                // Create contact
                $contact = Contact::create([
                    'business_unit_id' => $businessUnitId,
                    'code' => $code,
                    'name' => $row['name'],
                    'email' => $row['email'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'mobile' => $row['mobile'] ?? null,
                    'company' => $row['company'] ?? null,
                    'department' => $row['department'] ?? null,
                    'position' => $row['position'] ?? null,
                    'status' => $row['status'] ?? 'active',
                    'category' => $row['category'] ?? 'lead',
                    'created_by' => Auth::id(),
                    'assigned_to' => $row['assigned_to'] ?? Auth::id(),
                ]);

                // Create source record
                ContactSource::create([
                    'contact_id' => $contact->id,
                    'source_type' => 'import',
                    'source_user_id' => Auth::id(),
                    'source_notes' => 'Bulk import - '.($row['filename'] ?? 'Unknown'),
                    'source_date' => now()->toDateString(),
                ]);

                $imported[] = $contact;
            }

            DB::commit();

            return $imported;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get recent contacts for dashboard
     */
    public function getRecentContacts(?User $user = null, int $limit = 10): Collection
    {
        $query = Contact::query()
            ->with(['source', 'assignedTo:id,name'])
            ->where('business_unit_id', session('current_business_unit_id'));

        if ($user) {
            $query->where('assigned_to', $user->id);
        }

        return $query->latest('created_at')->limit($limit)->get();
    }
}

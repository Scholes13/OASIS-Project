<?php

namespace App\Livewire\Modules\SalesCrm;

use App\Models\Modules\SalesCrm\Activity;
use App\Models\Modules\SalesCrm\Contact;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ActivityForm extends Component
{
    // Activity properties
    public ?Activity $activity = null;

    public $activityId = null;

    public $isEditMode = false;

    // Form data
    public $activity_type = 'call';

    public $activity_date;

    public $company_name = '';

    public $department = '';

    public $pic_name = '';

    public $pic_email = '';

    public $pic_phone = '';

    public $pic_position = '';

    public $pic_birth_date = '';

    public $office_address = '';

    public $description = '';

    public $status = 'completed';

    // Social media
    public $linkedin = '';

    public $instagram = '';

    public $facebook = '';

    // Contact linking
    public $link_contact = false;

    public $existing_contact_id = null;

    public $create_new_contact = false;

    // New contact data
    public $contact_name = '';

    public $contact_phone = '';

    public $contact_email = '';

    public $contact_company = '';

    public $contact_department = '';

    public $contact_position = '';

    // Session context
    public $businessUnitId;

    // Available contacts for dropdown
    public $availableContacts = [];

    protected $listeners = [
        'business-unit-switched' => 'handleBusinessUnitSwitch',
    ];

    public function mount($activityId = null): void
    {
        $this->businessUnitId = session('current_business_unit_id');

        // Set default date to today
        $this->activity_date = now()->format('Y-m-d');

        if ($activityId) {
            $this->isEditMode = true;
            $this->activityId = $activityId;
            $this->loadActivity();
        }

        $this->loadAvailableContacts();
    }

    public function hydrate(): void
    {
        $sessionBuId = session('current_business_unit_id');
        if ($this->businessUnitId != $sessionBuId) {
            $this->businessUnitId = $sessionBuId;
            $this->loadAvailableContacts();
        }
    }

    public function handleBusinessUnitSwitch($businessUnitId): void
    {
        session(['current_business_unit_id' => $businessUnitId]);
        $this->businessUnitId = $businessUnitId;
        $this->loadAvailableContacts();
    }

    protected function loadActivity(): void
    {
        $this->activity = Activity::with('contact')
            ->where('id', $this->activityId)
            ->where('business_unit_id', session('current_business_unit_id'))
            ->firstOrFail();

        // Populate form
        $this->activity_type = $this->activity->activity_type;
        $this->activity_date = $this->activity->activity_date->format('Y-m-d');
        $this->company_name = $this->activity->title; // Map old 'title' to 'company_name'
        $this->department = $this->activity->department ?? '';
        $this->pic_name = $this->activity->pic_name ?? '';
        $this->pic_phone = $this->activity->pic_phone ?? '';
        $this->office_address = $this->activity->office_address ?? '';
        $this->description = $this->activity->description ?? '';
        $this->status = $this->activity->status;

        // Load comprehensive contact fields from linked contact if available
        if ($this->activity->contact_id && $this->activity->contact) {
            $contact = $this->activity->contact;
            $this->link_contact = true;
            $this->existing_contact_id = $this->activity->contact_id;

            // Load all contact fields (matching updatedExistingContactId logic)
            $this->pic_email = $contact->email ?? '';
            $this->pic_position = $contact->position ?? '';
            $this->pic_birth_date = $contact->birth_date ? $contact->birth_date->format('Y-m-d') : '';

            $socialMedia = $contact->social_media ?? [];
            $this->linkedin = $socialMedia['linkedin'] ?? '';
            $this->instagram = $socialMedia['instagram'] ?? '';
            $this->facebook = $socialMedia['facebook'] ?? '';
        }
    }

    protected function loadAvailableContacts(): void
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id') ?? $this->businessUnitId;

        $this->availableContacts = Contact::where('business_unit_id', $buId)
            ->when(! $user->hasAnyRole(['super_admin', 'admin']), fn ($q) => $q->where('assigned_to', $user->id)
            )
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn ($contact) => [
                'id' => $contact->id,
                'label' => "{$contact->name}".($contact->company ? " ({$contact->company})" : ''),
            ]);
    }

    public function updatedLinkContact($value): void
    {
        if (! $value) {
            $this->create_new_contact = false;
            $this->existing_contact_id = null;
            $this->resetContactForm();
        }
    }

    public function updatedCreateNewContact($value): void
    {
        if ($value) {
            $this->existing_contact_id = null;
        } else {
            $this->resetContactForm();
        }
    }

    public function updatedExistingContactId($contactId): void
    {
        if ($contactId) {
            $contact = Contact::find($contactId);
            if ($contact) {
                // Auto-fill activity form from contact data
                $this->company_name = $contact->company ?? '';
                $this->department = $contact->department ?? '';
                $this->pic_name = $contact->name ?? '';
                $this->pic_email = $contact->email ?? '';
                $this->pic_phone = $contact->mobile ?? $contact->phone ?? '';
                $this->pic_position = $contact->position ?? '';
                $this->pic_birth_date = $contact->birth_date ? $contact->birth_date->format('Y-m-d') : '';
                $this->office_address = $contact->address ?? '';

                // Social media
                $socialMedia = $contact->social_media ?? [];
                $this->linkedin = $socialMedia['linkedin'] ?? '';
                $this->instagram = $socialMedia['instagram'] ?? '';
                $this->facebook = $socialMedia['facebook'] ?? '';
            }
        }
    }

    protected function resetContactForm(): void
    {
        $this->contact_name = '';
        $this->contact_phone = '';
        $this->contact_email = '';
        $this->contact_company = '';
        $this->contact_department = '';
        $this->contact_position = '';
    }

    protected function rules(): array
    {
        $rules = [
            'activity_type' => 'required|in:call,visit,meeting,blitz,follow_up,other',
            'activity_date' => 'required|date',
            'company_name' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'pic_name' => 'nullable|string|max:255',
            'pic_email' => 'nullable|email|max:255',
            'pic_phone' => 'nullable|string|max:50',
            'pic_position' => 'required|string|max:255',
            'pic_birth_date' => 'nullable|date|before:today',
            'office_address' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'status' => 'required|in:planned,completed,cancelled',
            'linkedin' => 'nullable|url|max:255',
            'instagram' => 'nullable|string|max:255',
            'facebook' => 'nullable|url|max:255',
            'link_contact' => 'boolean',
            'existing_contact_id' => 'nullable|exists:contacts,id',
            'create_new_contact' => 'boolean',
        ];

        // Contact validation rules (only if creating new contact)
        if ($this->link_contact && $this->create_new_contact) {
            $rules['contact_name'] = 'required|string|max:255';
            $rules['contact_phone'] = 'required|string|max:20';
            $rules['contact_email'] = 'nullable|email|max:255';
            $rules['contact_company'] = 'nullable|string|max:255';
            $rules['contact_department'] = 'nullable|string|max:255';
            $rules['contact_position'] = 'nullable|string|max:255';
        }

        return $rules;
    }

    public function save(): void
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $contact = null;
            $contactCreated = false;

            // 1. Only create/link contact if activity is COMPLETED
            if ($this->status === 'completed') {
                $contact = $this->findOrCreateContact();
                $contactCreated = $contact->wasRecentlyCreated;
            }

            // 2. Create/Update activity
            $activityData = [
                'business_unit_id' => session('current_business_unit_id'),
                'user_id' => Auth::id(),
                'contact_id' => $contact?->id,  // Only link if completed
                'activity_type' => $this->activity_type,
                'activity_date' => $this->activity_date,
                'title' => $this->company_name,
                'department' => $this->department,
                'pic_name' => $this->pic_name,
                'pic_phone' => $this->pic_phone,
                'office_address' => $this->office_address,
                'description' => $this->description,
                'status' => $this->status,
            ];

            if ($this->isEditMode) {
                // Check if status changed from planned/cancelled to completed
                $wasNotCompleted = $this->activity->status !== 'completed';
                $nowCompleted = $this->status === 'completed';

                if ($wasNotCompleted && $nowCompleted && ! $this->activity->contact_id) {
                    // Status changed to completed, create contact now
                    $contact = $this->findOrCreateContact();
                    $activityData['contact_id'] = $contact->id;
                    $contactCreated = $contact->wasRecentlyCreated;

                    // Create contact source for this existing activity
                    $this->createContactSource($contact, $this->activity);
                }

                $this->activity->update($activityData);
                $activity = $this->activity;

                if ($contactCreated) {
                    $message = 'Activity completed! Contact created: '.$contact->company;
                } else {
                    $message = 'Activity updated successfully!';
                }
            } else {
                $activity = Activity::create($activityData);

                // 3. Create ContactSource record (only if contact was created)
                if ($contact && $contactCreated) {
                    $this->createContactSource($contact, $activity);
                }

                if ($this->status === 'completed') {
                    $message = $contactCreated
                        ? 'Activity completed! Contact created: '.$contact->company
                        : 'Activity completed! Linked to: '.$contact->company;
                } else {
                    $message = 'Activity saved as '.$this->status.'. Contact will be created when completed.';
                }
            }

            DB::commit();

            // Clear caches for affected modules
            $this->clearModuleCaches($contact);

            // Dispatch events
            $this->dispatch('notify', [
                'message' => $message,
                'type' => 'success',
            ]);

            if ($this->isEditMode) {
                $this->dispatch('activity-updated');
            } else {
                $this->dispatch('activity-created');
            }

            // Redirect to activity show page
            redirect()->route('sales-crm.activities.show', $activity);

        } catch (\Exception $e) {
            DB::rollBack();

            $this->dispatch('notify', [
                'message' => 'Error: '.$e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    /**
     * Find existing contact by company or create new one
     * Only called when status = 'completed'
     */
    protected function findOrCreateContact(): Contact
    {
        // If linking to existing contact via checkbox, use that
        if ($this->link_contact && $this->existing_contact_id) {
            return Contact::findOrFail($this->existing_contact_id);
        }

        // Try to find existing contact by company name (case-insensitive, same BU)
        $existingContact = Contact::where('business_unit_id', session('current_business_unit_id'))
            ->whereRaw('LOWER(company) = ?', [strtolower($this->company_name)])
            ->first();

        if ($existingContact) {
            // Update contact if PIC info is more complete than existing
            $updates = [];

            if ($this->pic_name && ! $existingContact->name) {
                $updates['name'] = $this->pic_name;
            }
            if ($this->pic_email && ! $existingContact->email) {
                $updates['email'] = $this->pic_email;
            }
            if ($this->pic_phone && ! $existingContact->mobile) {
                $updates['mobile'] = $this->pic_phone;
                $updates['phone'] = $this->pic_phone;
            }
            if ($this->pic_position && ! $existingContact->position) {
                $updates['position'] = $this->pic_position;
            }
            if ($this->pic_birth_date && ! $existingContact->birth_date) {
                $updates['birth_date'] = $this->pic_birth_date;
            }
            if ($this->office_address && ! $existingContact->address) {
                $updates['address'] = $this->office_address;
            }
            if ($this->department && ! $existingContact->department) {
                $updates['department'] = $this->department;
            }

            // Update social media if any new data
            if ($this->linkedin || $this->instagram || $this->facebook) {
                $existingSocialMedia = $existingContact->social_media ?? [];
                $updates['social_media'] = array_merge($existingSocialMedia, array_filter([
                    'linkedin' => $this->linkedin ?: ($existingSocialMedia['linkedin'] ?? null),
                    'instagram' => $this->instagram ?: ($existingSocialMedia['instagram'] ?? null),
                    'facebook' => $this->facebook ?: ($existingSocialMedia['facebook'] ?? null),
                ]));
            }

            if (count($updates) > 0) {
                $existingContact->update($updates);
            }

            return $existingContact;
        }

        // Create new contact with unique code
        $contactCode = $this->generateUniqueContactCode();

        return Contact::create([
            'business_unit_id' => session('current_business_unit_id'),
            'created_by' => Auth::id(),
            'assigned_to' => Auth::id(),
            'code' => $contactCode,
            'name' => $this->pic_name ?: 'Unknown PIC',
            'email' => $this->pic_email,
            'phone' => $this->pic_phone,
            'mobile' => $this->pic_phone,
            'birth_date' => $this->pic_birth_date ?: null,
            'company' => $this->company_name,
            'department' => $this->department,
            'position' => $this->pic_position,
            'social_media' => array_filter([
                'linkedin' => $this->linkedin,
                'instagram' => $this->instagram,
                'facebook' => $this->facebook,
            ]),
            'address' => $this->office_address,
            'status' => 'active',
            'category' => 'lead',  // New contacts are leads by default
            'notes' => 'Auto-created from '.$this->activity_type.' activity on '.$this->activity_date,
        ]);
    }

    /**
     * Generate unique contact code
     */
    protected function generateUniqueContactCode(): string
    {
        $prefix = 'CNT-'.strtoupper(substr($this->company_name, 0, 3));
        $suffix = now()->format('ymd').rand(100, 999);
        $code = $prefix.'-'.$suffix;

        // Ensure uniqueness
        $attempt = 0;
        while (Contact::where('code', $code)->exists() && $attempt < 10) {
            $suffix = now()->format('ymd').rand(100, 999);
            $code = $prefix.'-'.$suffix;
            $attempt++;
        }

        return $code;
    }

    /**
     * Create ContactSource record to track where contact came from
     */
    protected function createContactSource(Contact $contact, Activity $activity): void
    {
        // Check if source already exists for this contact+activity
        $existingSource = DB::table('contact_sources')
            ->where('contact_id', $contact->id)
            ->where('source_activity_id', $activity->id)
            ->exists();

        if (! $existingSource) {
            DB::table('contact_sources')->insert([
                'contact_id' => $contact->id,
                'source_type' => 'activity',
                'source_activity_id' => $activity->id,
                'activity_type' => $this->activity_type, // Denormalized: call, visit, meeting, etc
                'source_user_id' => Auth::id(),
                'source_notes' => ucfirst($this->activity_type).' activity at '.$this->company_name,
                'source_date' => $this->activity_date,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Clear caches for affected modules (Activity & Contact stats)
     */
    protected function clearModuleCaches(?Contact $contact): void
    {
        $buId = session('current_business_unit_id');
        $userId = Auth::id();

        // Clear activity stats cache
        Cache::forget("activity_stats_{$buId}_{$userId}");

        // Clear contact stats cache if contact was created/updated
        if ($contact) {
            Cache::forget("contact_stats_{$buId}_{$userId}");

            // Also clear assigned user's cache if different
            if ($contact->assigned_to && $contact->assigned_to != $userId) {
                Cache::forget("contact_stats_{$buId}_{$contact->assigned_to}");
            }
        }
    }

    public function cancel(): void
    {
        if ($this->isEditMode) {
            redirect()->route('sales-crm.activities.show', $this->activity);
        } else {
            redirect()->route('sales-crm.activities.index');
        }
    }

    public function render()
    {
        return view('livewire.modules.sales-crm.activity-form')
            ->layout('layouts.app');
    }
}

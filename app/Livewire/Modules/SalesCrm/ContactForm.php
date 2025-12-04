<?php

namespace App\Livewire\Modules\SalesCrm;

use App\Models\Modules\SalesCrm\Contact;
use App\Services\Modules\SalesCrm\ContactService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ContactForm extends Component
{
    // Contact properties
    public ?Contact $contact = null;

    public $contactId = null;

    public $isEditMode = false;

    // Form data
    public $name = '';

    public $email = '';

    public $phone = '';

    public $mobile = '';

    public $birth_date = '';

    public $company = '';

    public $department = '';

    public $position = '';

    public $status = 'active';

    public $category = 'lead';

    public $address = '';

    public $notes = '';

    // Social media (JSON)
    public $linkedin = '';

    public $instagram = '';

    public $facebook = '';

    // Session context
    public $businessUnitId;

    protected $listeners = [
        'business-unit-switched' => 'handleBusinessUnitSwitch',
    ];

    public function mount($contactId = null): void
    {
        $this->businessUnitId = session('current_business_unit_id');

        if ($contactId) {
            $this->isEditMode = true;
            $this->contactId = $contactId;
            $this->loadContact();
        }
    }

    public function hydrate(): void
    {
        $sessionBuId = session('current_business_unit_id');
        if ($this->businessUnitId != $sessionBuId) {
            $this->businessUnitId = $sessionBuId;
        }
    }

    public function handleBusinessUnitSwitch($businessUnitId): void
    {
        session(['current_business_unit_id' => $businessUnitId]);
        $this->businessUnitId = $businessUnitId;

        // ✅ ORCHESTRATOR: Acknowledge completion (form pages use same component name)
        $this->dispatch('bu-switch-acknowledge', component: 'contacts');

        $buName = session('current_business_unit_name', 'new business unit');
        $this->dispatch('notify', message: "Switched to {$buName}", type: 'success');
    }

    protected function loadContact(): void
    {
        $user = Auth::user();

        $this->contact = Contact::where('id', $this->contactId)
            ->where('business_unit_id', session('current_business_unit_id'))
            ->when(! $user->hasAnyRole(['super_admin', 'admin']), fn ($q) => $q->where('assigned_to', $user->id) // Only assigned contacts
            )
            ->firstOrFail();

        // Populate form
        $this->name = $this->contact->name;
        $this->email = $this->contact->email ?? '';
        $this->phone = $this->contact->phone ?? '';
        $this->mobile = $this->contact->mobile ?? '';
        $this->birth_date = $this->contact->birth_date ? $this->contact->birth_date->format('Y-m-d') : '';
        $this->company = $this->contact->company ?? '';
        $this->department = $this->contact->department ?? '';
        $this->position = $this->contact->position ?? '';
        $this->status = $this->contact->status;
        $this->category = $this->contact->category;
        $this->address = $this->contact->address ?? '';
        $this->notes = $this->contact->notes ?? '';

        // Social media
        $socialMedia = $this->contact->social_media ?? [];
        $this->linkedin = $socialMedia['linkedin'] ?? '';
        $this->instagram = $socialMedia['instagram'] ?? '';
        $this->facebook = $socialMedia['facebook'] ?? '';
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'company' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,archived',
            'category' => 'required|in:lead,prospect,customer,partner',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'linkedin' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
        ];
    }

    public function save(): void
    {
        $this->validate();

        try {
            $contactService = app(ContactService::class);

            $contactData = [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'mobile' => $this->mobile,
                'birth_date' => $this->birth_date ?: null,
                'company' => $this->company,
                'department' => $this->department,
                'position' => $this->position,
                'status' => $this->status,
                'category' => $this->category,
                'address' => $this->address,
                'notes' => $this->notes,
                'social_media' => [
                    'linkedin' => $this->linkedin,
                    'instagram' => $this->instagram,
                    'facebook' => $this->facebook,
                ],
            ];

            if ($this->isEditMode) {
                // Update existing contact
                $contact = $contactService->updateContact($this->contact, $contactData);
                $message = 'Contact updated successfully!';

                $this->dispatch('contact-updated');
            } else {
                // Create new contact (manual entry)
                $contactData['business_unit_id'] = session('current_business_unit_id');
                $contactData['created_by'] = Auth::id();
                $contactData['assigned_to'] = Auth::id(); // Self-assign by default

                $contact = $contactService->createManualContact($contactData);
                $message = 'Contact created successfully!';

                $this->dispatch('contact-created');
            }

            // Dispatch success notification
            $this->dispatch('notify', [
                'message' => $message,
                'type' => 'success',
            ]);

            // Redirect to contact show page
            redirect()->route('sales-crm.contacts.show', $contact);

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'message' => 'Error: '.$e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    public function cancel(): void
    {
        if ($this->isEditMode) {
            redirect()->route('sales-crm.contacts.show', $this->contact);
        } else {
            redirect()->route('sales-crm.contacts.index');
        }
    }

    public function render()
    {
        return view('livewire.modules.sales-crm.contact-form')
            ->layout('layouts.app');
    }
}

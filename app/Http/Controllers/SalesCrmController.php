<?php

namespace App\Http\Controllers;

use App\Models\Modules\SalesCrm\Activity;
use App\Models\Modules\SalesCrm\Contact;
use App\Services\Modules\SalesCrm\ContactService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SalesCrmController extends Controller
{
    protected ContactService $contactService;

    public function __construct(ContactService $contactService)
    {
        $this->contactService = $contactService;
    }

    // ============================================================================
    // ACTIVITIES
    // ============================================================================

    /**
     * Display a listing of activities
     */
    public function activitiesIndex(Request $request)
    {
        $buId = session('current_business_unit_id');
        $user = Auth::user();

        $query = Activity::query()
            ->where('business_unit_id', $buId)
            ->when(! $user->hasAnyRole(['super_admin', 'admin']), fn ($q) => $q->where('user_id', $user->id))
            ->when($request->activity_type, fn ($q, $type) => $q->where('activity_type', $type))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('activity_date', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('activity_date', '<=', $date));

        // Search
        if ($search = $request->search) {
            $sanitizedSearch = str_replace(['%', '_'], ['\\%', '\\_'], $search);

            if (config('database.default') === 'mysql') {
                $fulltextSearch = preg_replace('/[+\-*<>()~"]/', ' ', $search);
                $fulltextSearch = trim($fulltextSearch);

                if ($fulltextSearch) {
                    $query->where(function ($q) use ($fulltextSearch, $sanitizedSearch) {
                        $q->whereRaw('MATCH(title, description) AGAINST(? IN BOOLEAN MODE)', [$fulltextSearch.'*'])
                            ->orWhere('location', 'like', "%{$sanitizedSearch}%");
                    });
                } else {
                    $query->where(function ($q) use ($sanitizedSearch) {
                        $q->where('title', 'like', "%{$sanitizedSearch}%")
                            ->orWhere('description', 'like', "%{$sanitizedSearch}%")
                            ->orWhere('location', 'like', "%{$sanitizedSearch}%");
                    });
                }
            } else {
                $query->where(function ($q) use ($sanitizedSearch) {
                    $q->where('title', 'like', "%{$sanitizedSearch}%")
                        ->orWhere('description', 'like', "%{$sanitizedSearch}%")
                        ->orWhere('location', 'like', "%{$sanitizedSearch}%");
                });
            }
        }

        $activities = $query
            ->with(['user:id,name,email', 'contact:id,code,name,company,email,phone'])
            ->latest('activity_date')
            ->latest('created_at')
            ->paginate(20);

        // Get stats
        $stats = $this->getActivityStats($buId, $user);

        return Inertia::render('SalesCrm/Activities/Index', [
            'activities' => $activities,
            'stats' => $stats,
            'filters' => [
                'search' => $request->search ?? '',
                'activity_type' => $request->activity_type ?? '',
                'status' => $request->status ?? '',
                'date_from' => $request->date_from ?? '',
                'date_to' => $request->date_to ?? '',
            ],
        ]);
    }

    /**
     * Show the form for creating a new activity
     */
    public function activitiesCreate()
    {
        $availableContacts = $this->getAvailableContacts();

        return Inertia::render('SalesCrm/Activities/Form', [
            'availableContacts' => $availableContacts,
        ]);
    }

    /**
     * Store a newly created activity
     */
    public function activitiesStore(Request $request)
    {
        $validated = $request->validate([
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
        ]);

        try {
            DB::beginTransaction();

            $contact = null;
            $contactCreated = false;

            // Only create/link contact if activity is COMPLETED
            if ($validated['status'] === 'completed') {
                $contact = $this->findOrCreateContact($validated);
                $contactCreated = $contact->wasRecentlyCreated;
            }

            // Create activity
            $activity = Activity::create([
                'business_unit_id' => session('current_business_unit_id'),
                'user_id' => Auth::id(),
                'contact_id' => $contact?->id,
                'activity_type' => $validated['activity_type'],
                'activity_date' => $validated['activity_date'],
                'title' => $validated['company_name'],
                'department' => $validated['department'],
                'pic_name' => $validated['pic_name'],
                'pic_phone' => $validated['pic_phone'],
                'office_address' => $validated['office_address'],
                'description' => $validated['description'],
                'status' => $validated['status'],
            ]);

            // Create ContactSource if contact was created
            if ($contact && $contactCreated) {
                $this->createContactSource($contact, $activity, $validated);
            }

            DB::commit();

            // Clear caches
            $this->clearModuleCaches($contact);

            $message = $validated['status'] === 'completed'
                ? ($contactCreated ? 'Activity completed! Contact created: '.$contact->company : 'Activity completed! Linked to: '.$contact->company)
                : 'Activity saved as '.$validated['status'].'. Contact will be created when completed.';

            return redirect()->route('sales-crm.activities.show', $activity)->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error: '.$e->getMessage());
        }
    }

    /**
     * Display the specified activity
     */
    public function activitiesShow(Activity $activity)
    {
        $activity->load(['user', 'contact', 'businessUnit']);

        return Inertia::render('SalesCrm/Activities/Show', [
            'activity' => $activity,
        ]);
    }

    /**
     * Show the form for editing the specified activity
     */
    public function activitiesEdit(Activity $activity)
    {
        $availableContacts = $this->getAvailableContacts();

        return Inertia::render('SalesCrm/Activities/Form', [
            'activity' => $activity,
            'availableContacts' => $availableContacts,
        ]);
    }

    /**
     * Update the specified activity
     */
    public function activitiesUpdate(Request $request, Activity $activity)
    {
        $validated = $request->validate([
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
        ]);

        try {
            DB::beginTransaction();

            $contact = null;
            $contactCreated = false;

            // Check if status changed to completed
            $wasNotCompleted = $activity->status !== 'completed';
            $nowCompleted = $validated['status'] === 'completed';

            if ($wasNotCompleted && $nowCompleted && ! $activity->contact_id) {
                $contact = $this->findOrCreateContact($validated);
                $contactCreated = $contact->wasRecentlyCreated;

                if ($contactCreated) {
                    $this->createContactSource($contact, $activity, $validated);
                }
            }

            // Update activity
            $activity->update([
                'contact_id' => $contact?->id ?? $activity->contact_id,
                'activity_type' => $validated['activity_type'],
                'activity_date' => $validated['activity_date'],
                'title' => $validated['company_name'],
                'department' => $validated['department'],
                'pic_name' => $validated['pic_name'],
                'pic_phone' => $validated['pic_phone'],
                'office_address' => $validated['office_address'],
                'description' => $validated['description'],
                'status' => $validated['status'],
            ]);

            DB::commit();

            // Clear caches
            $this->clearModuleCaches($contact);

            $message = $contactCreated ? 'Activity completed! Contact created: '.$contact->company : 'Activity updated successfully!';

            return redirect()->route('sales-crm.activities.show', $activity)->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified activity
     */
    public function activitiesDestroy(Activity $activity)
    {
        $activity->delete();

        return redirect()->route('sales-crm.activities.index')->with('success', 'Activity deleted successfully!');
    }

    // ============================================================================
    // CONTACTS
    // ============================================================================

    /**
     * Display a listing of contacts
     */
    public function contactsIndex(Request $request)
    {
        $buId = session('current_business_unit_id');
        $user = Auth::user();

        $query = Contact::query()
            ->where('business_unit_id', $buId)
            ->when(! $user->hasAnyRole(['super_admin', 'admin']), fn ($q) => $q->where('assigned_to', $user->id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->category, fn ($q, $category) => $q->where('category', $category))
            ->when($request->company, fn ($q, $company) => $q->where('company', 'like', "%{$company}%"));

        // Search
        if ($search = $request->search) {
            if (config('database.default') === 'mysql') {
                $query->whereRaw('MATCH(name, company, email, phone, mobile, position) AGAINST(? IN BOOLEAN MODE)', [$search.'*']);
            } else {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%");
                });
            }
        }

        $contacts = $query
            ->with(['assignedTo:id,name,email'])
            ->select([
                'id', 'business_unit_id', 'code', 'name', 'email', 'phone', 'mobile',
                'company', 'department', 'position', 'status', 'category',
                'assigned_to', 'created_at', 'updated_at',
            ])
            ->latest('created_at')
            ->paginate(20);

        // Get stats
        $stats = $this->getContactStats($buId, $user);

        return Inertia::render('SalesCrm/Contacts/Index', [
            'contacts' => $contacts,
            'stats' => $stats,
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? '',
                'category' => $request->category ?? '',
                'company' => $request->company ?? '',
            ],
        ]);
    }

    /**
     * Show the form for creating a new contact
     */
    public function contactsCreate()
    {
        return Inertia::render('SalesCrm/Contacts/Form');
    }

    /**
     * Store a newly created contact
     */
    public function contactsStore(Request $request)
    {
        $validated = $request->validate([
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
        ]);

        try {
            $contactData = [
                'business_unit_id' => session('current_business_unit_id'),
                'created_by' => Auth::id(),
                'assigned_to' => Auth::id(),
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'mobile' => $validated['mobile'],
                'birth_date' => $validated['birth_date'] ?: null,
                'company' => $validated['company'],
                'department' => $validated['department'],
                'position' => $validated['position'],
                'status' => $validated['status'],
                'category' => $validated['category'],
                'address' => $validated['address'],
                'notes' => $validated['notes'],
                'social_media' => [
                    'linkedin' => $validated['linkedin'],
                    'instagram' => $validated['instagram'],
                    'facebook' => $validated['facebook'],
                ],
            ];

            $contact = $this->contactService->createManualContact($contactData);

            return redirect()->route('sales-crm.contacts.show', $contact)->with('success', 'Contact created successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: '.$e->getMessage());
        }
    }

    /**
     * Display the specified contact
     */
    public function contactsShow(Contact $contact)
    {
        $contact->load(['assignedTo', 'createdBy', 'activities', 'source']);

        return Inertia::render('SalesCrm/Contacts/Show', [
            'contact' => $contact,
        ]);
    }

    /**
     * Show the form for editing the specified contact
     */
    public function contactsEdit(Contact $contact)
    {
        return Inertia::render('SalesCrm/Contacts/Form', [
            'contact' => $contact,
        ]);
    }

    /**
     * Update the specified contact
     */
    public function contactsUpdate(Request $request, Contact $contact)
    {
        $validated = $request->validate([
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
        ]);

        try {
            $contactData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'mobile' => $validated['mobile'],
                'birth_date' => $validated['birth_date'] ?: null,
                'company' => $validated['company'],
                'department' => $validated['department'],
                'position' => $validated['position'],
                'status' => $validated['status'],
                'category' => $validated['category'],
                'address' => $validated['address'],
                'notes' => $validated['notes'],
                'social_media' => [
                    'linkedin' => $validated['linkedin'],
                    'instagram' => $validated['instagram'],
                    'facebook' => $validated['facebook'],
                ],
            ];

            $this->contactService->updateContact($contact, $contactData);

            return redirect()->route('sales-crm.contacts.show', $contact)->with('success', 'Contact updated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified contact
     */
    public function contactsDestroy(Contact $contact)
    {
        $contact->delete();

        return redirect()->route('sales-crm.contacts.index')->with('success', 'Contact deleted successfully!');
    }

    // ============================================================================
    // HELPER METHODS
    // ============================================================================

    /**
     * Get activity statistics
     */
    protected function getActivityStats($buId, $user)
    {
        $cacheKey = "activity_stats_{$buId}_{$user->id}";

        return Cache::remember($cacheKey, 300, function () use ($buId, $user) {
            $baseQuery = Activity::where('business_unit_id', $buId);

            if (! $user->hasAnyRole(['super_admin', 'admin'])) {
                $baseQuery->where('user_id', $user->id);
            }

            $today = now()->toDateString();
            $stats = $baseQuery
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = "planned" THEN 1 ELSE 0 END) as planned,
                    SUM(CASE WHEN DATE(activity_date) = ? THEN 1 ELSE 0 END) as today
                ', [$today])
                ->first();

            return [
                'total' => (int) $stats->total,
                'completed' => (int) $stats->completed,
                'planned' => (int) $stats->planned,
                'today' => (int) $stats->today,
            ];
        });
    }

    /**
     * Get contact statistics
     */
    protected function getContactStats($buId, $user)
    {
        $cacheKey = "contact_stats_{$buId}_{$user->id}";

        return Cache::remember($cacheKey, 300, function () use ($buId, $user) {
            $baseQuery = Contact::where('business_unit_id', $buId);

            if (! $user->hasAnyRole(['super_admin', 'admin'])) {
                $baseQuery->where('assigned_to', $user->id);
            }

            $stats = $baseQuery
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN category = "lead" THEN 1 ELSE 0 END) as leads,
                    SUM(CASE WHEN category = "customer" THEN 1 ELSE 0 END) as customers
                ')
                ->first();

            return [
                'total' => (int) $stats->total,
                'active' => (int) $stats->active,
                'leads' => (int) $stats->leads,
                'customers' => (int) $stats->customers,
            ];
        });
    }

    /**
     * Get available contacts for dropdown
     */
    protected function getAvailableContacts()
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');

        return Contact::where('business_unit_id', $buId)
            ->when(! $user->hasAnyRole(['super_admin', 'admin']), fn ($q) => $q->where('assigned_to', $user->id))
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn ($contact) => [
                'id' => $contact->id,
                'label' => $contact->name.($contact->company ? " ({$contact->company})" : ''),
            ]);
    }

    /**
     * Find existing contact or create new one
     */
    protected function findOrCreateContact($validated)
    {
        // If linking to existing contact, use that
        if (isset($validated['link_contact']) && $validated['link_contact'] && isset($validated['existing_contact_id'])) {
            return Contact::findOrFail($validated['existing_contact_id']);
        }

        // Try to find existing contact by company name
        $existingContact = Contact::where('business_unit_id', session('current_business_unit_id'))
            ->whereRaw('LOWER(company) = ?', [strtolower($validated['company_name'])])
            ->first();

        if ($existingContact) {
            // Update contact if PIC info is more complete
            $updates = [];

            if (isset($validated['pic_name']) && $validated['pic_name'] && ! $existingContact->name) {
                $updates['name'] = $validated['pic_name'];
            }
            if (isset($validated['pic_email']) && $validated['pic_email'] && ! $existingContact->email) {
                $updates['email'] = $validated['pic_email'];
            }
            if (isset($validated['pic_phone']) && $validated['pic_phone'] && ! $existingContact->mobile) {
                $updates['mobile'] = $validated['pic_phone'];
                $updates['phone'] = $validated['pic_phone'];
            }
            if (isset($validated['pic_position']) && $validated['pic_position'] && ! $existingContact->position) {
                $updates['position'] = $validated['pic_position'];
            }
            if (isset($validated['pic_birth_date']) && $validated['pic_birth_date'] && ! $existingContact->birth_date) {
                $updates['birth_date'] = $validated['pic_birth_date'];
            }
            if (isset($validated['office_address']) && $validated['office_address'] && ! $existingContact->address) {
                $updates['address'] = $validated['office_address'];
            }
            if (isset($validated['department']) && $validated['department'] && ! $existingContact->department) {
                $updates['department'] = $validated['department'];
            }

            // Update social media
            if (isset($validated['linkedin']) || isset($validated['instagram']) || isset($validated['facebook'])) {
                $existingSocialMedia = $existingContact->social_media ?? [];
                $updates['social_media'] = array_merge($existingSocialMedia, array_filter([
                    'linkedin' => $validated['linkedin'] ?? ($existingSocialMedia['linkedin'] ?? null),
                    'instagram' => $validated['instagram'] ?? ($existingSocialMedia['instagram'] ?? null),
                    'facebook' => $validated['facebook'] ?? ($existingSocialMedia['facebook'] ?? null),
                ]));
            }

            if (count($updates) > 0) {
                $existingContact->update($updates);
            }

            return $existingContact;
        }

        // Create new contact
        $contactCode = $this->generateUniqueContactCode($validated['company_name']);

        return Contact::create([
            'business_unit_id' => session('current_business_unit_id'),
            'created_by' => Auth::id(),
            'assigned_to' => Auth::id(),
            'code' => $contactCode,
            'name' => $validated['pic_name'] ?? 'Unknown PIC',
            'email' => $validated['pic_email'] ?? null,
            'phone' => $validated['pic_phone'] ?? null,
            'mobile' => $validated['pic_phone'] ?? null,
            'birth_date' => $validated['pic_birth_date'] ?? null,
            'company' => $validated['company_name'],
            'department' => $validated['department'] ?? null,
            'position' => $validated['pic_position'] ?? null,
            'social_media' => array_filter([
                'linkedin' => $validated['linkedin'] ?? null,
                'instagram' => $validated['instagram'] ?? null,
                'facebook' => $validated['facebook'] ?? null,
            ]),
            'address' => $validated['office_address'] ?? null,
            'status' => 'active',
            'category' => 'lead',
            'notes' => 'Auto-created from '.$validated['activity_type'].' activity on '.$validated['activity_date'],
        ]);
    }

    /**
     * Generate unique contact code
     */
    protected function generateUniqueContactCode($companyName)
    {
        $prefix = 'CNT-'.strtoupper(substr($companyName, 0, 3));
        $suffix = now()->format('ymd').rand(100, 999);
        $code = $prefix.'-'.$suffix;

        $attempt = 0;
        while (Contact::where('code', $code)->exists() && $attempt < 10) {
            $suffix = now()->format('ymd').rand(100, 999);
            $code = $prefix.'-'.$suffix;
            $attempt++;
        }

        return $code;
    }

    /**
     * Create ContactSource record
     */
    protected function createContactSource($contact, $activity, $validated)
    {
        $existingSource = DB::table('contact_sources')
            ->where('contact_id', $contact->id)
            ->where('source_activity_id', $activity->id)
            ->exists();

        if (! $existingSource) {
            DB::table('contact_sources')->insert([
                'contact_id' => $contact->id,
                'source_type' => 'activity',
                'source_activity_id' => $activity->id,
                'activity_type' => $validated['activity_type'],
                'source_user_id' => Auth::id(),
                'source_notes' => ucfirst($validated['activity_type']).' activity at '.$validated['company_name'],
                'source_date' => $validated['activity_date'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Clear module caches
     */
    protected function clearModuleCaches($contact = null)
    {
        $buId = session('current_business_unit_id');
        $userId = Auth::id();

        Cache::forget("activity_stats_{$buId}_{$userId}");

        if ($contact) {
            Cache::forget("contact_stats_{$buId}_{$userId}");

            if ($contact->assigned_to && $contact->assigned_to != $userId) {
                Cache::forget("contact_stats_{$buId}_{$contact->assigned_to}");
            }
        }
    }
}

<?php

namespace App\Services\Modules\SalesCrm;

use App\Models\Core\User;
use App\Models\Modules\SalesCrm\Activity;
use App\Models\Modules\SalesCrm\CompanyVisitHistory;
use App\Models\Modules\SalesCrm\Contact;
use App\Models\Modules\SalesCrm\ContactSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ActivityService
{
    /**
     * Create a new activity
     */
    public function createActivity(array $data): Activity
    {
        DB::beginTransaction();

        try {
            // Calculate duration if times provided
            if (! empty($data['start_time']) && ! empty($data['end_time'])) {
                $start = \Carbon\Carbon::parse($data['start_time']);
                $end = \Carbon\Carbon::parse($data['end_time']);
                $data['duration_minutes'] = $end->diffInMinutes($start);
            }

            // Create activity
            $activity = Activity::create([
                'business_unit_id' => $data['business_unit_id'] ?? session('current_business_unit_id'),
                'user_id' => $data['user_id'] ?? Auth::id(),
                'contact_id' => $data['contact_id'] ?? null,
                'activity_date' => $data['activity_date'],
                'activity_type' => $data['activity_type'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'location' => $data['location'] ?? null,
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'result' => $data['result'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $data['status'] ?? 'completed',
            ]);

            DB::commit();

            return $activity;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create activity with new or existing contact
     */
    public function createActivityWithContact(array $activityData, array $contactData): Activity
    {
        DB::beginTransaction();

        try {
            // 1. Create or update contact
            $contact = Contact::updateOrCreate(
                [
                    'phone' => $contactData['phone'],
                    'business_unit_id' => $activityData['business_unit_id'] ?? session('current_business_unit_id'),
                ],
                [
                    'name' => $contactData['name'],
                    'email' => $contactData['email'] ?? null,
                    'mobile' => $contactData['mobile'] ?? null,
                    'birth_date' => $contactData['birth_date'] ?? null,
                    'company' => $contactData['company'] ?? null,
                    'department' => $contactData['department'] ?? null,
                    'position' => $contactData['position'] ?? null,
                    'social_media' => $contactData['social_media'] ?? null,
                    'status' => $contactData['status'] ?? 'active',
                    'category' => $contactData['category'] ?? 'lead',
                    'address' => $contactData['address'] ?? null,
                    'notes' => $contactData['notes'] ?? null,
                    'created_by' => Auth::id(),
                    'assigned_to' => $contactData['assigned_to'] ?? Auth::id(),
                ]
            );

            // 2. If contact is newly created, create source record
            if ($contact->wasRecentlyCreated) {
                // Code will be generated in ContactService, but we need to set it here
                $contactService = new ContactService;
                $contact->code = $contactService->generateContactCode(
                    $activityData['business_unit_id'] ?? session('current_business_unit_id')
                );
                $contact->save();
            }

            // 3. Create activity with contact link
            $activityData['contact_id'] = $contact->id;
            $activity = $this->createActivity($activityData);

            // 4. If contact was just created, link it to this activity as source
            if ($contact->wasRecentlyCreated && ! $contact->source) {
                ContactSource::create([
                    'contact_id' => $contact->id,
                    'source_type' => 'activity',
                    'source_activity_id' => $activity->id,
                    'activity_type' => $activity->activity_type,
                    'source_date' => $activity->activity_date,
                ]);
            }

            // 5. Update company visit history
            if ($contact->company) {
                $this->updateCompanyVisitHistory($contact, $activity);
            }

            DB::commit();

            return $activity->load('contact');

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing activity
     */
    public function updateActivity(Activity $activity, array $data): Activity
    {
        DB::beginTransaction();

        try {
            // Calculate duration if times provided
            if (! empty($data['start_time']) && ! empty($data['end_time'])) {
                $start = \Carbon\Carbon::parse($data['start_time']);
                $end = \Carbon\Carbon::parse($data['end_time']);
                $data['duration_minutes'] = $end->diffInMinutes($start);
            }

            $activity->update($data);

            // Update company visit history if contact/company changed
            if ($activity->contact && $activity->contact->company) {
                $this->updateCompanyVisitHistory($activity->contact, $activity);
            }

            DB::commit();

            return $activity->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete an activity (soft delete)
     */
    public function deleteActivity(Activity $activity): bool
    {
        return $activity->delete();
    }

    /**
     * Get activities for a specific user with filters
     */
    public function getActivitiesForUser(User $user, array $filters = []): Collection
    {
        $query = Activity::query()
            ->with(['contact:id,name,company', 'user:id,name'])
            ->where('user_id', $user->id)
            ->where('business_unit_id', session('current_business_unit_id'));

        // Apply filters
        if (! empty($filters['activity_type'])) {
            $query->where('activity_type', $filters['activity_type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('activity_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('activity_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['contact_id'])) {
            $query->where('contact_id', $filters['contact_id']);
        }

        return $query->latest('activity_date')->get();
    }

    /**
     * Update or create company visit history
     */
    protected function updateCompanyVisitHistory(Contact $contact, Activity $activity): void
    {
        if (! $contact->company) {
            return;
        }

        CompanyVisitHistory::updateOrCreate(
            [
                'business_unit_id' => $contact->business_unit_id,
                'company_name' => $contact->company,
                'department' => $contact->department,
            ],
            [
                'activity_id' => $activity->id,
                'contact_id' => $contact->id,
                'user_id' => $activity->user_id,
                'last_visit_at' => $activity->activity_date,
            ]
        )->increment('total_visits');
    }

    /**
     * Get activity statistics for user
     */
    public function getActivityStats(User $user, string $period = 'this_month'): array
    {
        $businessUnitId = session('current_business_unit_id');

        $query = Activity::query()
            ->where('user_id', $user->id)
            ->where('business_unit_id', $businessUnitId);

        // Apply period filter
        match ($period) {
            'today' => $query->whereDate('activity_date', today()),
            'this_week' => $query->whereBetween('activity_date', [now()->startOfWeek(), now()->endOfWeek()]),
            'this_month' => $query->whereMonth('activity_date', now()->month)
                ->whereYear('activity_date', now()->year),
            'this_year' => $query->whereYear('activity_date', now()->year),
            default => null,
        };

        return [
            'total_activities' => $query->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'planned' => (clone $query)->where('status', 'planned')->count(),
            'calls' => (clone $query)->where('activity_type', 'call')->count(),
            'visits' => (clone $query)->where('activity_type', 'visit')->count(),
            'meetings' => (clone $query)->where('activity_type', 'meeting')->count(),
            'blitz' => (clone $query)->where('activity_type', 'blitz')->count(),
        ];
    }

    /**
     * Get recent activities for dashboard
     */
    public function getRecentActivities(User $user, int $limit = 10): Collection
    {
        return Activity::query()
            ->with(['contact:id,name,company', 'user:id,name'])
            ->where('user_id', $user->id)
            ->where('business_unit_id', session('current_business_unit_id'))
            ->latest('activity_date')
            ->limit($limit)
            ->get();
    }
}

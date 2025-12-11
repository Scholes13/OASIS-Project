<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models\Core{
/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $business_unit_id
 * @property string $module_type
 * @property array<array-key, mixed> $approval_steps
 * @property bool $is_sequential
 * @property bool $is_default
 * @property bool $is_active
 * @property array<array-key, mixed>|null $conditions
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\BusinessUnit $businessUnit
 * @property-read int $steps_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow default()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow forBusinessUnit($businessUnitId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow forModule($moduleType)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereApprovalSteps($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereBusinessUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereConditions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereIsSequential($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereModuleType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class ApprovalWorkflow extends \Eloquent {}
}

namespace App\Models\Core{
/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property array<array-key, mixed>|null $numbering_config
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $parent_id
 * @property string|null $description
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property int|null $manager_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Department> $activeDepartments
 * @property-read int|null $active_departments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, NumberingModule> $activeNumberingModules
 * @property-read int|null $active_numbering_modules_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BusinessUnit> $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Department> $departments
 * @property-read int|null $departments_count
 * @property-read User|null $manager
 * @property-read \Illuminate\Database\Eloquent\Collection<int, NumberSequence> $numberSequences
 * @property-read int|null $number_sequences_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, NumberingModule> $numberingModules
 * @property-read int|null $numbering_modules_count
 * @property-read BusinessUnit|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Modules\PurchaseRequest\PurchaseRequest> $purchaseRequests
 * @property-read int|null $purchase_requests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserBusinessUnit> $userBusinessUnits
 * @property-read int|null $user_business_units_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit active()
 * @method static \Database\Factories\BusinessUnitFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereNumberingConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string|null $logo Logo path for business unit
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessUnit whereLogo($value)
 */
	class BusinessUnit extends \Eloquent {}
}

namespace App\Models\Core{
/**
 * @property int $id
 * @property int $business_unit_id
 * @property string $code
 * @property string $name
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Position> $activePositions
 * @property-read int|null $active_positions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $activeUsers
 * @property-read int|null $active_users_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read BusinessUnit $businessUnit
 * @property-read string $full_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, NumberSequence> $numberSequences
 * @property-read int|null $number_sequences_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Position> $positions
 * @property-read int|null $positions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserBusinessUnit> $userBusinessUnits
 * @property-read int|null $user_business_units_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department active()
 * @method static \Database\Factories\DepartmentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department forBusinessUnit($businessUnitId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereBusinessUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class Department extends \Eloquent {}
}

namespace App\Models\Core{
/**
 * @property int $id
 * @property string $smtp_host
 * @property int $smtp_port
 * @property string|null $smtp_username
 * @property string|null $smtp_password Encrypted password
 * @property string $smtp_encryption
 * @property string $mail_from_address
 * @property string $mail_from_name
 * @property bool $email_enabled Enable/disable email notifications
 * @property bool $fallback_to_database Always save to database as fallback
 * @property int $link_expiry_days Public approval link expiry in days
 * @property bool $retry_failed_emails Auto-retry failed emails
 * @property int $total_sent Total emails sent successfully
 * @property int $total_failed Total emails failed
 * @property \Illuminate\Support\Carbon|null $last_email_sent_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationSetting whereEmailEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationSetting whereFallbackToDatabase($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationSetting whereLastEmailSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationSetting whereLinkExpiryDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationSetting whereMailFromAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationSetting whereMailFromName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationSetting whereRetryFailedEmails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationSetting whereSmtpEncryption($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationSetting whereSmtpHost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationSetting whereSmtpPassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationSetting whereSmtpPort($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationSetting whereSmtpUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationSetting whereTotalFailed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationSetting whereTotalSent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationSetting whereUpdatedAt($value)
 */
	class NotificationSetting extends \Eloquent {}
}

namespace App\Models\Core{
/**
 * @property int $id
 * @property int $business_unit_id
 * @property int $numbering_module_id
 * @property int|null $department_id
 * @property int $year
 * @property int $month
 * @property int $current_number
 * @property int $max_number
 * @property array<array-key, mixed>|null $void_numbers
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\BusinessUnit $businessUnit
 * @property-read \App\Models\Department|null $department
 * @property-read \App\Models\NumberingModule $numberingModule
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PurchaseRequest> $purchaseRequests
 * @property-read int|null $purchase_requests_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence forPeriod($businessUnitId, $moduleId, $departmentId, $year, $month)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereBusinessUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereCurrentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereMaxNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereNumberingModuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereVoidNumbers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereYear($value)
 * @mixin \Eloquent
 */
	class NumberSequence extends \Eloquent {}
}

namespace App\Models\Core{
/**
 * @property int $id
 * @property int $business_unit_id
 * @property string $module_code
 * @property string $module_name
 * @property string $format_pattern
 * @property array<array-key, mixed>|null $config
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Core\BusinessUnit $businessUnit
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Core\NumberSequence> $numberSequences
 * @property-read int|null $number_sequences_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule byCode($moduleCode)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule forBusinessUnit($businessUnitId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule whereBusinessUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule whereConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule whereFormatPattern($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule whereModuleCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule whereModuleName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class NumberingModule extends \Eloquent {}
}

namespace App\Models\Core{
/**
 * @property int $id
 * @property int $department_id
 * @property string $name
 * @property string $code
 * @property string $level
 * @property string $access_level
 * @property int $hierarchy_level
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $activeUsers
 * @property-read int|null $active_users_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Department $department
 * @property-read string $full_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserBusinessUnit> $userBusinessUnits
 * @property-read int|null $user_business_units_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position byLevel($level)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position forDepartment($departmentId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position hod()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position leader()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position staff()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereHierarchyLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereAccessLevel($value)
 */
	class Position extends \Eloquent {}
}

namespace App\Models\Core{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $phone_number
 * @property int|null $primary_department_id
 * @property int|null $primary_position_id
 * @property int|null $supervisor_id
 * @property string $global_role
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserBusinessUnit> $activeBusinessUnits
 * @property-read int|null $active_business_units_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $activeSubordinates
 * @property-read int|null $active_subordinates_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserBusinessUnit> $businessUnits
 * @property-read int|null $business_units_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PrApproval> $pendingApprovals
 * @property-read int|null $pending_approvals_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PrApproval> $prApprovals
 * @property-read int|null $pr_approvals_count
 * @property-read Department|null $primaryDepartment
 * @property-read Position|null $primaryPosition
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PurchaseRequest> $purchaseRequests
 * @property-read int|null $purchase_requests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $subordinates
 * @property-read int|null $subordinates_count
 * @property-read User|null $supervisor
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User active()
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User inDepartment($departmentId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereGlobalRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePrimaryDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePrimaryPositionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSupervisorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withGlobalRole($role)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Modules\Purchasing\PurchaseRequest\PrApproval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Modules\SalesCrm\Contact> $assignedContacts
 * @property-read int|null $assigned_contacts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Modules\SalesCrm\Contact> $createdContacts
 * @property-read int|null $created_contacts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Modules\SalesCrm\Activity> $salesActivities
 * @property-read int|null $sales_activities_count
 */
	class User extends \Eloquent {}
}

namespace App\Models\Core{
/**
 * @property int $id
 * @property int $user_id
 * @property int $business_unit_id
 * @property int $department_id
 * @property int $position_id
 * @property bool $is_primary
 * @property bool $is_active
 * @property array<array-key, mixed>|null $permissions
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read BusinessUnit $businessUnit
 * @property-read Department|null $department
 * @property-read Position|null $position
 * @property-read User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit forBusinessUnit($businessUnitId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit primary()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit whereBusinessUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit whereIsPrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit wherePermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit wherePositionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBusinessUnit whereUserId($value)
 * @mixin \Eloquent
 */
	class UserBusinessUnit extends \Eloquent {}
}

namespace App\Models\Modules\Purchasing\PurchaseRequest{
/**
 * @property int $id
 * @property int $purchase_request_id
 * @property int $approver_id
 * @property int $step_order
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $assigned_at
 * @property \Illuminate\Support\Carbon|null $responded_at
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property bool $email_sent
 * @property \Illuminate\Support\Carbon|null $email_sent_at
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $approval_type
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read User $approver
 * @property-read string|null $formatted_due_date
 * @property-read string $status_color
 * @property-read \App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest $purchaseRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval approved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval dueSoon()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval forApprover($approverId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval overdue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval rejected()
 * @mixin \Eloquent
 * @property string|null $qr_code_path
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereApprovalType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereApproverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereAssignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereEmailSent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereEmailSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval wherePurchaseRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereQrCodePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereRespondedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereStepOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrApproval whereUpdatedAt($value)
 */
	class PrApproval extends \Eloquent {}
}

namespace App\Models\Modules\Purchasing\PurchaseRequest{
/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string $color
 * @property bool $is_active
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest> $purchaseRequests
 * @property-read int|null $purchase_requests_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrCategory active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrCategory ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrCategory whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrCategory whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrCategory whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrCategory whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrCategory whereUpdatedAt($value)
 */
	class PrCategory extends \Eloquent {}
}

namespace App\Models\Modules\Purchasing\PurchaseRequest{
/**
 * @property int $id
 * @property int $purchase_request_id
 * @property int $item_order
 * @property string $item_name
 * @property string|null $brand_name
 * @property int $expense_department_id
 * @property string|null $item_description
 * @property string|null $supplier_name
 * @property numeric $quantity
 * @property string $unit
 * @property numeric $unit_price
 * @property string $currency
 * @property numeric $total_price
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Department $expenseDepartment
 * @property-read string $formatted_quantity
 * @property-read string $formatted_total_price
 * @property-read string $formatted_unit_price
 * @property-read \App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest $purchaseRequest
 * @mixin \Eloquent
 * @property string|null $image_path Path to uploaded item image
 * @property-read string|null $image_url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem forPurchaseRequest($purchaseRequestId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereBrandName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereExpenseDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereItemDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereItemName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereItemOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem wherePurchaseRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereSupplierName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereTotalPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereUpdatedAt($value)
 */
	class PrItem extends \Eloquent {}
}

namespace App\Models\Modules\Purchasing\PurchaseRequest{
/**
 * @property int $id
 * @property string $pr_number
 * @property int $business_unit_id
 * @property int $department_id
 * @property int $user_id
 * @property int $sequence_id
 * @property string $purpose
 * @property string $description
 * @property string $status
 * @property \Illuminate\Support\Carbon $reserved_at
 * @property \Illuminate\Support\Carbon|null $used_at
 * @property \Illuminate\Support\Carbon|null $voided_at
 * @property string|null $void_reason
 * @property int|null $voided_by
 * @property int|null $purchase_request_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Core\BusinessUnit $businessUnit
 * @property-read \App\Models\Core\Department $department
 * @property-read \App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest|null $purchaseRequest
 * @property-read \App\Models\Core\NumberSequence $sequence
 * @property-read \App\Models\Core\User $user
 * @property-read \App\Models\Core\User|null $voidedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation byUser($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation reserved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation used()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation voided()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation whereBusinessUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation wherePrNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation wherePurchaseRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation wherePurpose($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation whereReservedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation whereSequenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation whereUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation whereVoidReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation whereVoidedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrNumberReservation whereVoidedBy($value)
 */
	class PrNumberReservation extends \Eloquent {}
}

namespace App\Models\Modules\Purchasing\PurchaseRequest{
/**
 * @property int $id
 * @property string $pr_number
 * @property int $business_unit_id
 * @property int $department_id
 * @property int $user_id
 * @property int $sequence_id
 * @property string $used_for
 * @property \Illuminate\Support\Carbon $date_of_request
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $rejected_at
 * @property \Illuminate\Support\Carbon|null $voided_at
 * @property array<array-key, mixed>|null $approval_workflow
 * @property bool $is_sequential_approval
 * @property numeric $total_amount
 * @property string $currency
 * @property array<array-key, mixed>|null $edit_history
 * @property int|null $last_modified_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $keperluan
 * @property \Illuminate\Support\Carbon|null $expected_date
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Modules\Purchasing\PurchaseRequest\PrApproval> $approvals
 * @property-read int|null $approvals_count
 * @property-read BusinessUnit $businessUnit
 * @property-read Department $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Modules\Purchasing\PurchaseRequest\PrItem> $items
 * @property-read int|null $items_count
 * @property-read User|null $lastModifiedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Modules\Purchasing\PurchaseRequest\PrApproval> $pendingApprovals
 * @property-read int|null $pending_approvals_count
 * @property-read NumberSequence $sequence
 * @property-read User $user
 * @mixin \Eloquent
 * @property int|null $category_id
 * @property \Illuminate\Support\Carbon|null $designated_date
 * @property \Illuminate\Support\Carbon|null $offline_approved_at
 * @property int|null $offline_approved_by
 * @property string|null $offline_approval_notes
 * @property-read \App\Models\Modules\Purchasing\PurchaseRequest\PrCategory|null $category
 * @property-read \App\Models\Core\User|null $offlineApprovedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest approved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest byDepartment($departmentId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest byUser($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest dateRange($startDate, $endDate)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest draft()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest rejected()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest submitted()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest voided()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereApprovalWorkflow($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereBusinessUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereDateOfRequest($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereDesignatedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereEditHistory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereExpectedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereIsSequentialApproval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereLastModifiedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereOfflineApprovalNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereOfflineApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereOfflineApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest wherePrNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereRejectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereSequenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereUsedFor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest whereVoidedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequest withStatus($status)
 */
	class PurchaseRequest extends \Eloquent {}
}

namespace App\Models\Modules\Purchasing\StockRequest{
/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Core\User|null $approver
 * @property-read string|null $formatted_due_date
 * @property-read string $status_color
 * @property-read \App\Models\Modules\Purchasing\StockRequest\StockRequest|null $stockRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockApproval approved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockApproval dueSoon()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockApproval forApprover($approverId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockApproval newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockApproval newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockApproval overdue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockApproval pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockApproval query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockApproval rejected()
 */
	class StockApproval extends \Eloquent {}
}

namespace App\Models\Modules\Purchasing\StockRequest{
/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Modules\Purchasing\StockRequest\StockRequest|null $stockRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockItem forStockRequest($stockRequestId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockItem ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockItem query()
 */
	class StockItem extends \Eloquent {}
}

namespace App\Models\Modules\Purchasing\StockRequest{
/**
 * @property-read \App\Models\Core\BusinessUnit|null $businessUnit
 * @property-read \App\Models\Core\Department|null $department
 * @property-read \App\Models\Core\NumberSequence|null $sequence
 * @property-read \App\Models\Modules\Purchasing\StockRequest\StockRequest|null $stockRequest
 * @property-read \App\Models\Core\User|null $user
 * @property-read \App\Models\Core\User|null $voidedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockNumberReservation byUser($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockNumberReservation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockNumberReservation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockNumberReservation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockNumberReservation reserved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockNumberReservation used()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockNumberReservation voided()
 */
	class StockNumberReservation extends \Eloquent {}
}

namespace App\Models\Modules\Purchasing\StockRequest{
/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Modules\Purchasing\StockRequest\StockApproval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \App\Models\Core\BusinessUnit|null $businessUnit
 * @property-read \App\Models\Core\Department|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Modules\Purchasing\StockRequest\StockItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\Core\User|null $lastModifiedBy
 * @property-read \App\Models\Core\User|null $offlineApprovedBy
 * @property-read \App\Models\Core\NumberSequence|null $sequence
 * @property-read \App\Models\Core\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockRequest approved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockRequest byDepartment($departmentId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockRequest byUser($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockRequest dateRange($startDate, $endDate)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockRequest draft()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockRequest rejected()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockRequest submitted()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockRequest voided()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockRequest withStatus($status)
 */
	class StockRequest extends \Eloquent {}
}

namespace App\Models\Modules\SalesCrm{
/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Core\BusinessUnit|null $businessUnit
 * @property-read \App\Models\Modules\SalesCrm\CompanyVisitHistory|null $companyVisitHistory
 * @property-read \App\Models\Modules\SalesCrm\Contact|null $contact
 * @property-read \App\Models\Core\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity betweenDates($startDate, $endDate)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity byType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity forBusinessUnit(int $businessUnitId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity forContact(int $contactId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity withoutTrashed()
 */
	class Activity extends \Eloquent {}
}

namespace App\Models\Modules\SalesCrm{
/**
 * @property-read \App\Models\Modules\SalesCrm\Activity|null $activity
 * @property-read \App\Models\Core\BusinessUnit|null $businessUnit
 * @property-read \App\Models\Modules\SalesCrm\Contact|null $contact
 * @property-read \App\Models\Core\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyVisitHistory forBusinessUnit(int $businessUnitId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyVisitHistory mostVisited(int $limit = 10)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyVisitHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyVisitHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyVisitHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyVisitHistory recentlyVisited(int $days = 30)
 */
	class CompanyVisitHistory extends \Eloquent {}
}

namespace App\Models\Modules\SalesCrm{
/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Modules\SalesCrm\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Core\User|null $assignedTo
 * @property-read \App\Models\Core\BusinessUnit|null $businessUnit
 * @property-read \App\Models\Modules\SalesCrm\CompanyVisitHistory|null $companyVisitHistory
 * @property-read \App\Models\Core\User|null $createdBy
 * @property-read string $full_name
 * @property-read int $total_activities
 * @property-read \App\Models\Modules\SalesCrm\Activity|null $lastActivity
 * @property-read \App\Models\Modules\SalesCrm\ContactSource|null $source
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact assignedTo(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact byCategory(string $category)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact forBusinessUnit(int $businessUnitId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact search(string $search)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact withoutTrashed()
 */
	class Contact extends \Eloquent {}
}

namespace App\Models\Modules\SalesCrm{
/**
 * @property-read \App\Models\Modules\SalesCrm\Contact|null $contact
 * @property-read \App\Models\Modules\SalesCrm\Activity|null $sourceActivity
 * @property-read \App\Models\Core\User|null $sourceUser
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactSource newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactSource newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactSource query()
 */
	class ContactSource extends \Eloquent {}
}


<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SalesCrmPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates Sales CRM permissions and assigns them to roles:
     * - Super Admin: All permissions
     * - Admin: All CRM permissions + manage
     * - Sales: View + Create + Edit (own activities/contacts)
     * - User: No CRM access by default
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ============================================================
        // ACTIVITY PERMISSIONS
        // ============================================================
        $activityPermissions = [
            'view_activities',      // View activities (own or managed)
            'create_activities',    // Create new activities
            'edit_activities',      // Edit activities (own or managed)
            'delete_activities',    // Delete activities (own or managed)
            'view_all_activities',  // View all activities (admin only)
        ];

        foreach ($activityPermissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // ============================================================
        // CONTACT PERMISSIONS
        // ============================================================
        $contactPermissions = [
            'view_contacts',        // View contacts (assigned or managed)
            'create_contacts',      // Create new contacts
            'edit_contacts',        // Edit contacts (assigned or managed)
            'delete_contacts',      // Delete contacts (assigned or managed)
            'view_all_contacts',    // View all contacts (admin only)
            'assign_contacts',      // Assign contacts to other users
        ];

        foreach ($contactPermissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // ============================================================
        // SALES CRM MANAGEMENT PERMISSION (Admin only)
        // ============================================================
        Permission::firstOrCreate(['name' => 'manage_sales_crm', 'guard_name' => 'web']);

        // ============================================================
        // ASSIGN PERMISSIONS TO ROLES
        // ============================================================

        // Super Admin - All permissions
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo([
                'view_activities',
                'create_activities',
                'edit_activities',
                'delete_activities',
                'view_all_activities',
                'view_contacts',
                'create_contacts',
                'edit_contacts',
                'delete_contacts',
                'view_all_contacts',
                'assign_contacts',
                'manage_sales_crm',
            ]);
        }

        // Admin - All CRM permissions + manage
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo([
                'view_activities',
                'create_activities',
                'edit_activities',
                'delete_activities',
                'view_all_activities',
                'view_contacts',
                'create_contacts',
                'edit_contacts',
                'delete_contacts',
                'view_all_contacts',
                'assign_contacts',
                'manage_sales_crm',
            ]);
        }

        // Sales Role - Create if doesn't exist
        $salesRole = Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);

        // Sales - View, Create, Edit (own activities/contacts)
        $salesRole->givePermissionTo([
            'view_activities',
            'create_activities',
            'edit_activities',
            'view_contacts',
            'create_contacts',
            'edit_contacts',
        ]);

        $this->command->info('✅ Sales CRM permissions created successfully!');
        $this->command->info('✅ Permissions assigned to roles: super_admin, admin, sales');
        $this->command->newLine();
        $this->command->info('📝 Activity Permissions: '.count($activityPermissions));
        $this->command->info('📝 Contact Permissions: '.count($contactPermissions));
        $this->command->info('📝 Management Permissions: 1');
        $this->command->newLine();
        $this->command->warn('⚠️  NOTE: Sales role created with CRM + PR access');
        $this->command->warn('⚠️  Run: php artisan cache:clear after seeding');
    }
}

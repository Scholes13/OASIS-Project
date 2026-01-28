import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { User } from '@/types/admin';
import { ArrowLeft, Edit, UserX, Mail, Phone, Building2, Briefcase, Users } from 'lucide-react';
import { toast } from 'sonner';

interface ShowProps {
  user: User;
}

export default function Show({ user }: ShowProps) {
  const handleDeactivate = () => {
    if (confirm(`Are you sure you want to deactivate user "${user.name}"?`)) {
      router.delete(route('admin.users.destroy', { user: user.id }), {
        onSuccess: () => {
          toast.success('User deactivated successfully');
          router.visit(route('admin.users.index'));
        },
        onError: () => {
          toast.error('Failed to deactivate user');
        },
      });
    }
  };

  return (
    <>
      <Head title={`User - ${user.name}`} />

      <div className="p-6 space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">{user.name}</h1>
            <p className="mt-1 text-sm text-gray-600">
              User details and business unit assignments
            </p>
          </div>
          <div className="flex items-center gap-3">
            <Link href={route('admin.users.index')}>
              <Button variant="outline">
                <ArrowLeft className="w-4 h-4 mr-2" />
                Back to Users
              </Button>
            </Link>
            <Link href={route('admin.users.edit', { user: user.id })}>
              <Button>
                <Edit className="w-4 h-4 mr-2" />
                Edit User
              </Button>
            </Link>
            {user.is_active && !user.is_super_admin && (
              <Button variant="destructive" onClick={handleDeactivate}>
                <UserX className="w-4 h-4 mr-2" />
                Deactivate
              </Button>
            )}
          </div>
        </div>

        {/* Basic Information */}
        <Card className="p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">
            Basic Information
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <div className="flex items-center text-sm text-gray-500 mb-1">
                <Mail className="w-4 h-4 mr-2" />
                Email
              </div>
              <div className="text-gray-900">{user.email}</div>
            </div>

            {user.phone_number && (
              <div>
                <div className="flex items-center text-sm text-gray-500 mb-1">
                  <Phone className="w-4 h-4 mr-2" />
                  Phone Number
                </div>
                <div className="text-gray-900">{user.phone_number}</div>
              </div>
            )}

            <div>
              <div className="text-sm text-gray-500 mb-1">Role</div>
              <Badge variant={user.is_super_admin ? 'info' : 'default'}>
                {user.is_super_admin ? 'Super Admin' : 'User'}
              </Badge>
            </div>

            <div>
              <div className="text-sm text-gray-500 mb-1">Status</div>
              <Badge variant={user.is_active ? 'success' : 'default'}>
                {user.is_active ? 'Active' : 'Inactive'}
              </Badge>
            </div>

            {user.supervisor && (
              <div>
                <div className="flex items-center text-sm text-gray-500 mb-1">
                  <Users className="w-4 h-4 mr-2" />
                  Supervisor
                </div>
                <div className="text-gray-900">
                  {user.supervisor.name}
                  <span className="text-sm text-gray-500 ml-2">
                    ({user.supervisor.email})
                  </span>
                </div>
              </div>
            )}

            <div>
              <div className="text-sm text-gray-500 mb-1">Created At</div>
              <div className="text-gray-900">
                {new Date(user.created_at).toLocaleDateString('en-US', {
                  year: 'numeric',
                  month: 'long',
                  day: 'numeric',
                })}
              </div>
            </div>
          </div>
        </Card>

        {/* Business Unit Assignments */}
        <Card className="p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">
            Business Unit Assignments
          </h2>
          {user.business_units && user.business_units.length > 0 ? (
            <div className="space-y-4">
              {user.business_units.map((assignment, index) => (
                <div
                  key={index}
                  className="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors"
                >
                  <div className="flex items-start justify-between mb-3">
                    <div className="flex items-center gap-2">
                      <Building2 className="w-5 h-5 text-gray-400" />
                      <div>
                        <div className="font-medium text-gray-900">
                          {assignment.business_unit.name}
                        </div>
                        <div className="text-sm text-gray-500">
                          {assignment.business_unit.code}
                        </div>
                      </div>
                    </div>
                    {assignment.is_primary && (
                      <Badge variant="default">Primary</Badge>
                    )}
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3 pt-3 border-t border-gray-100">
                    <div>
                      <div className="text-xs text-gray-500 mb-1">Department</div>
                      <div className="text-sm text-gray-900">
                        {assignment.department.name}
                      </div>
                    </div>
                    <div>
                      <div className="text-xs text-gray-500 mb-1">Position</div>
                      <div className="text-sm text-gray-900 flex items-center">
                        <Briefcase className="w-4 h-4 mr-1 text-gray-400" />
                        {assignment.position.name}
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-8 text-gray-500">
              No business unit assignments found
            </div>
          )}
        </Card>

        {/* Subordinates (if any) */}
        {user.subordinates && user.subordinates.length > 0 && (
          <Card className="p-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">
              Subordinates ({user.subordinates.length})
            </h2>
            <div className="space-y-2">
              {user.subordinates.map((subordinate) => (
                <Link
                  key={subordinate.id}
                  href={route('admin.users.show', { user: subordinate.id })}
                  className="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors"
                >
                  <div>
                    <div className="font-medium text-gray-900">
                      {subordinate.name}
                    </div>
                    <div className="text-sm text-gray-500">
                      {subordinate.email}
                    </div>
                  </div>
                  <Badge variant={subordinate.is_active ? 'success' : 'default'}>
                    {subordinate.is_active ? 'Active' : 'Inactive'}
                  </Badge>
                </Link>
              ))}
            </div>
          </Card>
        )}
      </div>
    </>
  );
}



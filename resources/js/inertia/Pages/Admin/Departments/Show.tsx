import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { ArrowLeft, Edit, Users, Building2, UserCircle, Shield } from 'lucide-react';
import type { DepartmentWithStats, Position } from '@/types/admin';

interface ShowProps {
  department: DepartmentWithStats & {
    positions?: Position[];
    user_assignments?: Array<{
      id: number;
      name: string;
      email: string;
      position: {
        id: number;
        name: string;
        code: string;
      };
    }>;
  };
}

function Show({ department }: ShowProps) {
  return (
    <>
      <Head title={department.name} />

      <div className="p-6 space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <div className="flex items-center gap-3">
              <h1 className="text-2xl font-bold text-gray-900">{department.name}</h1>
              <Badge variant={department.is_active ? 'success' : 'default'}>
                {department.is_active ? 'Active' : 'Inactive'}
              </Badge>
              {department.is_purchasing_enabled && (
                <Badge variant="info">Purchasing Enabled</Badge>
              )}
            </div>
            <p className="mt-1 text-sm text-gray-500">
              Department Code: {department.code}
            </p>
          </div>
          <div className="flex items-center gap-3">
            <Button
              variant="outline"
              onClick={() => router.visit(route('admin.departments.index'))}
              className="flex items-center gap-2"
            >
              <ArrowLeft className="w-4 h-4" />
              Back
            </Button>
            <Button
              onClick={() => router.visit(route('admin.departments.edit', { department: department.id }))}
              className="flex items-center gap-2"
            >
              <Edit className="w-4 h-4" />
              Edit Department
            </Button>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Main Information */}
          <div className="lg:col-span-2 space-y-6">
            {/* Basic Details */}
            <Card>
              <div className="px-6 py-4 border-b border-gray-200">
                <h2 className="text-lg font-semibold text-gray-900">Department Details</h2>
              </div>
              <div className="p-6 space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-500 mb-1">
                      Department Code
                    </label>
                    <p className="text-base text-gray-900">{department.code}</p>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-500 mb-1">
                      Department Name
                    </label>
                    <p className="text-base text-gray-900">{department.name}</p>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-500 mb-1">
                      Business Unit
                    </label>
                    <div className="flex items-center gap-2">
                      <Building2 className="w-4 h-4 text-gray-400" />
                      <p className="text-base text-gray-900">
                        {department.business_unit?.name || 'N/A'}
                      </p>
                    </div>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-500 mb-1">
                      Department Head
                    </label>
                    <div className="flex items-center gap-2">
                      <UserCircle className="w-4 h-4 text-gray-400" />
                      <p className="text-base text-gray-900">
                        {department.head?.name || 'No Head Assigned'}
                      </p>
                    </div>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-500 mb-1">
                      Status
                    </label>
                    <Badge variant={department.is_active ? 'success' : 'default'}>
                      {department.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                  </div>

                  {department.is_purchasing_enabled && (
                    <div>
                      <label className="block text-sm font-medium text-gray-500 mb-1">
                        Purchasing Admin
                      </label>
                      <div className="flex items-center gap-2">
                        <Shield className="w-4 h-4 text-gray-400" />
                        <p className="text-base text-gray-900">
                          {department.purchasing_admin?.name || 'No Admin Assigned'}
                        </p>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            </Card>

            {/* Positions */}
            <Card>
              <div className="px-6 py-4 border-b border-gray-200">
                <h2 className="text-lg font-semibold text-gray-900">Positions</h2>
              </div>
              <div className="p-6">
                {department.positions && department.positions.length > 0 ? (
                  <div className="space-y-3">
                    {department.positions.map((position: Position) => (
                      <div
                        key={position.id}
                        className="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
                      >
                        <div>
                          <p className="font-medium text-gray-900">{position.name}</p>
                          <p className="text-sm text-gray-500">Code: {position.code}</p>
                        </div>
                        <Badge variant="default">
                          {position.access_level || 'staff'}
                        </Badge>
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="text-center py-8 text-gray-500">
                    No positions defined for this department
                  </div>
                )}
              </div>
            </Card>

            {/* User Assignments */}
            <Card>
              <div className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                  <h2 className="text-lg font-semibold text-gray-900">User Assignments</h2>
                  <p className="text-sm text-gray-500 mt-1">
                    Users assigned to this department
                  </p>
                </div>
                <div className="flex items-center gap-2 text-sm text-gray-500">
                  <Users className="w-4 h-4" />
                  <span>
                    {department.user_assignments?.length || 0} user(s)
                  </span>
                </div>
              </div>
              <div className="p-6">
                {department.user_assignments && department.user_assignments.length > 0 ? (
                  <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                      <thead className="bg-gray-50">
                        <tr>
                          <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Name
                          </th>
                          <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Email
                          </th>
                          <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Position
                          </th>
                        </tr>
                      </thead>
                      <tbody className="bg-white divide-y divide-gray-100">
                        {department.user_assignments.map((user) => (
                          <tr key={user.id} className="hover:bg-gray-50 transition-colors">
                            <td className="px-4 py-3 whitespace-nowrap">
                              <div className="flex items-center gap-2">
                                <UserCircle className="w-5 h-5 text-gray-400" />
                                <span className="text-sm font-medium text-gray-900">
                                  {user.name}
                                </span>
                              </div>
                            </td>
                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                              {user.email}
                            </td>
                            <td className="px-4 py-3 whitespace-nowrap">
                              <Badge variant="default">
                                {user.position?.name || 'N/A'}
                              </Badge>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                ) : (
                  <div className="text-center py-8 text-gray-500">
                    No users assigned to this department yet
                  </div>
                )}
              </div>
            </Card>
          </div>

          {/* Sidebar - Statistics */}
          <div className="space-y-6">
            <Card>
              <div className="px-6 py-4 border-b border-gray-200">
                <h2 className="text-lg font-semibold text-gray-900">Statistics</h2>
              </div>
              <div className="p-6 space-y-4">
                <div className="flex items-center justify-between p-4 bg-indigo-50 rounded-lg">
                  <div className="flex items-center gap-3">
                    <div className="p-2 bg-indigo-100 rounded-lg">
                      <Users className="w-5 h-5 text-indigo-600" />
                    </div>
                    <div>
                      <p className="text-sm text-gray-600">Total Users</p>
                      <p className="text-2xl font-bold text-gray-900">
                        {department.user_assignments?.length || 0}
                      </p>
                    </div>
                  </div>
                </div>

                <div className="flex items-center justify-between p-4 bg-emerald-50 rounded-lg">
                  <div className="flex items-center gap-3">
                    <div className="p-2 bg-emerald-100 rounded-lg">
                      <Shield className="w-5 h-5 text-emerald-600" />
                    </div>
                    <div>
                      <p className="text-sm text-gray-600">Positions</p>
                      <p className="text-2xl font-bold text-gray-900">
                        {department.positions?.length || 0}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </Card>

            {/* Quick Actions */}
            <Card>
              <div className="px-6 py-4 border-b border-gray-200">
                <h2 className="text-lg font-semibold text-gray-900">Quick Actions</h2>
              </div>
              <div className="p-6 space-y-2">
                <Button
                  variant="outline"
                  className="w-full justify-start"
                  onClick={() => router.visit(route('admin.departments.edit', { department: department.id }))}
                >
                  <Edit className="w-4 h-4 mr-2" />
                  Edit Department
                </Button>
                <Button
                  variant="outline"
                  className="w-full justify-start"
                  onClick={() => router.visit(route('admin.users.index', { department_id: department.id }))}
                >
                  <Users className="w-4 h-4 mr-2" />
                  View Users
                </Button>
              </div>
            </Card>
          </div>
        </div>
      </div>
    </>
  );
}

export default Show;

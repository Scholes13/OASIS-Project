import { Head } from '@inertiajs/react';
import { StatCard } from '@/components/admin/StatCard';
import { ChartCard } from '@/components/admin/ChartCard';
import { LazyLineChart } from '@/components/admin/LazyChart';
import { AdminDashboardProps } from '@/types/admin';
import { Users, Building2, Briefcase, FileText, TrendingUp, ArrowRight } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';

export default function Dashboard({ stats, recentUsers, businessUnitStats, monthlyPRs }: AdminDashboardProps) {
  // Transform monthlyPRs object to array for Recharts
  const chartData = Object.entries(monthlyPRs).map(([month, count]) => ({
    month,
    count,
  }));

  return (
    <>
      <Head title="Admin Dashboard" />
      <AnimatePresence mode="wait">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          exit={{ opacity: 0, y: -20 }}
          transition={{ duration: 0.3 }}
          className="p-6"
        >
          {/* Statistics Grid */}
          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <motion.div
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ delay: 0.1, type: 'spring', stiffness: 300, damping: 30 }}
            >
              <StatCard
                title="Total Users"
                value={stats.total_users}
                icon={Users}
                color="indigo"
              />
            </motion.div>

            <motion.div
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ delay: 0.2, type: 'spring', stiffness: 300, damping: 30 }}
            >
              <StatCard
                title="Business Units"
                value={stats.total_business_units}
                icon={Building2}
                color="emerald"
              />
            </motion.div>

            <motion.div
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ delay: 0.3, type: 'spring', stiffness: 300, damping: 30 }}
            >
              <StatCard
                title="Departments"
                value={stats.total_departments}
                icon={Briefcase}
                color="amber"
              />
            </motion.div>

            <motion.div
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ delay: 0.4, type: 'spring', stiffness: 300, damping: 30 }}
            >
              <StatCard
                title="Purchase Requests"
                value={stats.total_purchase_requests}
                icon={FileText}
                color="blue"
              />
            </motion.div>
          </div>

          {/* Main Content Grid */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {/* Recent Users Table */}
            <motion.div
              initial={{ opacity: 0, x: -20 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ delay: 0.5, type: 'spring', stiffness: 300, damping: 30 }}
              className="bg-white rounded-xl border border-gray-100 overflow-hidden"
            >
              <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                  <h3 className="text-lg font-semibold text-gray-900">Recent Users</h3>
                  <p className="text-sm text-gray-600 mt-1">Latest registered users</p>
                </div>
                <Link
                  href="/admin/users"
                  className="text-sm text-primary hover:text-primary font-medium flex items-center gap-1 transition-colors"
                  aria-label="View all users"
                >
                  View All
                  <ArrowRight className="w-4 h-4" />
                </Link>
              </div>
              <div className="divide-y divide-gray-100">
                {recentUsers.length > 0 ? (
                  recentUsers.map((user, index) => (
                    <motion.div
                      key={user.id}
                      initial={{ opacity: 0, x: -10 }}
                      animate={{ opacity: 1, x: 0 }}
                      transition={{ delay: 0.6 + index * 0.05 }}
                      className="px-6 py-4 hover:bg-gray-50 transition-colors"
                    >
                      <div className="flex items-center justify-between">
                        <div className="flex-1 min-w-0">
                          <p className="text-sm font-medium text-gray-900 truncate">
                            {user.name}
                          </p>
                          <p className="text-sm text-gray-600 truncate">{user.email}</p>
                        </div>
                        <div className="ml-4">
                          <span
                            className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                              user.is_active
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-gray-100 text-gray-700'
                            }`}
                          >
                            {user.is_active ? 'Active' : 'Inactive'}
                          </span>
                        </div>
                      </div>
                    </motion.div>
                  ))
                ) : (
                  <div className="px-6 py-12 text-center">
                    <Users className="w-12 h-12 text-gray-400 mx-auto mb-3" />
                    <p className="text-sm text-gray-600">No users found</p>
                  </div>
                )}
              </div>
            </motion.div>

            {/* Business Unit Breakdown */}
            <motion.div
              initial={{ opacity: 0, x: 20 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ delay: 0.5, type: 'spring', stiffness: 300, damping: 30 }}
              className="bg-white rounded-xl border border-gray-100 overflow-hidden"
            >
              <div className="px-6 py-4 border-b border-gray-100">
                <h3 className="text-lg font-semibold text-gray-900">Business Unit Breakdown</h3>
                <p className="text-sm text-gray-600 mt-1">User distribution by business unit</p>
              </div>
              <div className="p-6 space-y-4">
                {businessUnitStats.length > 0 ? (
                  businessUnitStats.map((bu, index) => (
                    <motion.div
                      key={bu.id}
                      initial={{ opacity: 0, y: 10 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{ delay: 0.6 + index * 0.05 }}
                      whileHover={{ scale: 1.02 }}
                      className="p-4 rounded-lg border border-gray-100 hover:border-primary hover:bg-blue-600/50 transition-all cursor-pointer"
                    >
                      <div className="flex items-center justify-between mb-2">
                        <div className="flex items-center gap-3">
                          <div className="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                            <Building2 className="w-5 h-5 text-primary" />
                          </div>
                          <div>
                            <p className="text-sm font-semibold text-gray-900">{bu.name}</p>
                            <p className="text-xs text-gray-600">{bu.code}</p>
                          </div>
                        </div>
                        <div className="text-right">
                          <p className="text-lg font-bold text-gray-900">{bu.users_count || 0}</p>
                          <p className="text-xs text-gray-600">users</p>
                        </div>
                      </div>
                    </motion.div>
                  ))
                ) : (
                  <div className="py-12 text-center">
                    <Building2 className="w-12 h-12 text-gray-400 mx-auto mb-3" />
                    <p className="text-sm text-gray-600">No business units found</p>
                  </div>
                )}
              </div>
            </motion.div>
          </div>

          {/* Charts Row */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {/* Monthly PR Trends Chart */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.7, type: 'spring', stiffness: 300, damping: 30 }}
            >
              <ChartCard
                title="Monthly PR Trends"
                description="Purchase requests created this year"
              >
                {chartData.length > 0 ? (
                  <LazyLineChart
                    data={chartData}
                    dataKey="count"
                    xAxisKey="month"
                    height={300}
                    color="#6366f1"
                  />
                ) : (
                  <div className="h-[300px] flex items-center justify-center">
                    <div className="text-center">
                      <TrendingUp className="w-12 h-12 text-gray-400 mx-auto mb-3" />
                      <p className="text-sm text-gray-600">No data available</p>
                    </div>
                  </div>
                )}
              </ChartCard>
            </motion.div>

            {/* Quick Actions */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.8, type: 'spring', stiffness: 300, damping: 30 }}
              className="bg-white rounded-xl border border-gray-100 p-6"
            >
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
              <div className="grid grid-cols-2 gap-4">
                <Link
                  href="/admin/users/create"
                  className="p-4 rounded-lg border border-gray-200 hover:border-primary hover:bg-blue-600 transition-all group"
                  aria-label="Create new user"
                >
                  <Users className="w-8 h-8 text-primary mb-2 group-hover:scale-110 transition-transform" />
                  <p className="text-sm font-medium text-gray-900">Add User</p>
                  <p className="text-xs text-gray-600 mt-1">Create new user</p>
                </Link>

                <Link
                  href="/admin/business-units/create"
                  className="p-4 rounded-lg border border-gray-200 hover:border-emerald-300 hover:bg-emerald-50 transition-all group"
                  aria-label="Create new business unit"
                >
                  <Building2 className="w-8 h-8 text-emerald-600 mb-2 group-hover:scale-110 transition-transform" />
                  <p className="text-sm font-medium text-gray-900">Add Business Unit</p>
                  <p className="text-xs text-gray-600 mt-1">Create new unit</p>
                </Link>

                <Link
                  href="/admin/departments/create"
                  className="p-4 rounded-lg border border-gray-200 hover:border-amber-300 hover:bg-amber-50 transition-all group"
                  aria-label="Create new department"
                >
                  <Briefcase className="w-8 h-8 text-amber-600 mb-2 group-hover:scale-110 transition-transform" />
                  <p className="text-sm font-medium text-gray-900">Add Department</p>
                  <p className="text-xs text-gray-600 mt-1">Create new dept</p>
                </Link>

                <Link
                  href="/admin/pr-categories"
                  className="p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all group"
                  aria-label="Manage PR categories"
                >
                  <FileText className="w-8 h-8 text-blue-600 mb-2 group-hover:scale-110 transition-transform" />
                  <p className="text-sm font-medium text-gray-900">Manage Categories</p>
                  <p className="text-xs text-gray-600 mt-1">PR categories</p>
                </Link>
              </div>
            </motion.div>
          </div>
        </motion.div>
      </AnimatePresence>
    </>
  );
}



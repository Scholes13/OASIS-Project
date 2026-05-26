import { motion } from 'framer-motion';
import { formatDate } from '@/lib/formatters';
import type { StockRequest } from '@/types/purchasing';

export function StockRequestSummaryPanel({ stockRequest }: { stockRequest: StockRequest }) {
    return (
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.1 }} className="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-100">
                <h3 className="text-base font-semibold text-gray-900">Request Details</h3>
            </div>
            <div className="p-6">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-8">
                    <div className="mb-6">
                        <p className="text-sm font-medium text-gray-500">Requested By</p>
                        <p className="mt-1 text-sm text-gray-900">{stockRequest.user?.name || 'N/A'}</p>
                    </div>
                    <div className="mb-6">
                        <p className="text-sm font-medium text-gray-500">Department</p>
                        <p className="mt-1 text-sm text-gray-900">{stockRequest.department?.name || 'N/A'} ({stockRequest.department?.code || 'N/A'})</p>
                    </div>
                    <div className="mb-6">
                        <p className="text-sm font-medium text-gray-500">Date of Request</p>
                        <p className="mt-1 text-sm text-gray-900">{formatDate(stockRequest.date_of_request)}</p>
                    </div>
                    <div className="mb-6">
                        <p className="text-sm font-medium text-gray-500">Expected Date</p>
                        <p className="mt-1 text-sm text-gray-900">{formatDate(stockRequest.expected_date) || 'Not specified'}</p>
                    </div>
                    <div className="sm:col-span-2">
                        <p className="text-sm font-medium text-gray-500">Purpose</p>
                        <p className="mt-1 text-sm text-gray-900">{stockRequest.purpose || 'Not specified'}</p>
                    </div>
                </div>
            </div>
        </motion.div>
    );
}

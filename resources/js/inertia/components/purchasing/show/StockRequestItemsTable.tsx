import { motion } from 'framer-motion';
import { Eye } from 'lucide-react';
import type { StockRequest } from '@/types/purchasing';

export function StockRequestItemsTable({ stockRequest }: { stockRequest: StockRequest }) {
    return (
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.2 }} className="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 className="text-base font-semibold text-gray-900">Items</h3>
                <span className="text-sm text-gray-500">{stockRequest.items?.length || 0} {stockRequest.items?.length === 1 ? 'item' : 'items'}</span>
            </div>
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                            <th className="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                            <th className="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-100">
                        {stockRequest.items && stockRequest.items.length > 0 ? (
                            stockRequest.items.map((item, index) => (
                                <motion.tr key={item.id} initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.3 + index * 0.05 }} className="hover:bg-gray-50 transition-colors">
                                    <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-500">{index + 1}</td>
                                    <td className="px-5 py-4">
                                        <div className="text-sm text-gray-900">{item.item_name}</div>
                                        {item.item_description && <div className="text-sm text-gray-400 mt-1">{item.item_description}</div>}
                                    </td>
                                    <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{item.quantity} {item.unit}</td>
                                    <td className="px-5 py-4 whitespace-nowrap text-center">
                                        {item.image_path ? (
                                            <a href={`/storage/${item.image_path}`} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:text-blue-800">
                                                <Eye className="w-4 h-4 inline" />
                                            </a>
                                        ) : (
                                            <span className="text-gray-400">-</span>
                                        )}
                                    </td>
                                </motion.tr>
                            ))
                        ) : (
                            <tr><td colSpan={4} className="px-5 py-8 text-center text-sm text-gray-500">No items found</td></tr>
                        )}
                    </tbody>
                </table>
            </div>
        </motion.div>
    );
}

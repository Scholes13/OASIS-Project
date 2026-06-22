import { motion } from 'framer-motion';
import { formatCurrency } from '@/lib/formatters';
import type { PurchaseRequest } from '@/types/purchasing';

interface PurchaseRequestItemsTableProps {
    purchaseRequest: PurchaseRequest;
}

export function PurchaseRequestItemsTable({ purchaseRequest }: PurchaseRequestItemsTableProps) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.3 }}
            className="bg-white rounded-xl border border-gray-100 overflow-hidden"
        >
            <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 className="text-base font-semibold text-gray-900">Items</h3>
                <span className="text-sm text-gray-500">
                    {purchaseRequest.items?.length || 0} {purchaseRequest.items?.length === 1 ? 'item' : 'items'}
                </span>
            </div>
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                            <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expense Dept</th>
                            <th className="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                            <th className="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                            <th className="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-100">
                        {purchaseRequest.items && purchaseRequest.items.length > 0 ? (
                            purchaseRequest.items.map((item, index) => (
                                <motion.tr key={item.id} initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.4 + index * 0.05 }} className="hover:bg-gray-50 transition-colors">
                                    <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-500">{index + 1}</td>
                                    <td className="px-5 py-4">
                                        <div className="text-sm text-gray-900">{item.item_name}</div>
                                        {item.brand_name && <div className="text-sm text-gray-500">Brand: {item.brand_name}</div>}
                                        {item.item_description && <div className="text-sm text-gray-400 mt-1">{item.item_description}</div>}
                                        {item.supplier_name && <div className="text-xs text-gray-400 mt-1">Supplier: {item.supplier_name}</div>}
                                    </td>
                                    <td className="px-5 py-4 whitespace-nowrap">
                                        <div className="text-sm text-gray-900">{item.expense_department?.name || 'N/A'}</div>
                                        <div className="text-xs text-gray-500">{item.expense_department?.code || ''}</div>
                                    </td>
                                    <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {formatCurrency(item.quantity)} {item.unit}
                                    </td>
                                    <td className="px-5 py-4 whitespace-nowrap text-right">
                                        <span className="text-sm text-gray-500">{item.currency || 'IDR'} </span>
                                        <span className="text-sm text-gray-900">{formatCurrency(item.unit_price, item.currency)}</span>
                                    </td>
                                    <td className="px-5 py-4 whitespace-nowrap text-right">
                                        <span className="text-sm text-gray-500">{item.currency || 'IDR'} </span>
                                        <span className="text-sm text-gray-900">{formatCurrency(item.total_price, item.currency)}</span>
                                    </td>
                                </motion.tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan={6} className="px-5 py-8 text-center text-sm text-gray-500">No items found</td>
                            </tr>
                        )}
                    </tbody>
                    <tfoot className="bg-gray-50">
                        <tr>
                            <td colSpan={5} className="px-5 py-4 text-right text-sm font-semibold text-gray-900">Total Amount</td>
                            <td className="px-5 py-4 whitespace-nowrap text-right">
                                <span className="text-sm text-gray-900">{purchaseRequest.currency || 'IDR'} </span>
                                <span className="text-base font-semibold text-gray-900">{formatCurrency(purchaseRequest.total_amount, purchaseRequest.currency)}</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </motion.div>
    );
}

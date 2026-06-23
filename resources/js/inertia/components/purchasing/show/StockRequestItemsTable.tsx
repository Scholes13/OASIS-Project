import { motion } from 'framer-motion';
import { Eye } from 'lucide-react';
import type { StockRequest } from '@/types/purchasing';

export type StockRequestGaReviewItem = {
    id: number;
    ga_review_result: 'pending_review' | 'warehouse_stock' | 'need_procurement';
    ga_review_note: string;
    warehouse_available_qty: string;
    procurement_quantity: string;
};

interface StockRequestItemsTableProps {
    stockRequest: StockRequest;
    canReviewGa?: boolean;
    gaReviewItems?: StockRequestGaReviewItem[];
    onGaReviewItemChange?: (id: number, field: keyof Omit<StockRequestGaReviewItem, 'id'>, value: string) => void;
}

export function StockRequestItemsTable({ stockRequest, canReviewGa = false, gaReviewItems = [], onGaReviewItemChange }: StockRequestItemsTableProps) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            className="bg-white rounded-xl border border-gray-100 overflow-hidden"
        >
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
                            <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">GA Review</th>
                            <th className="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-100">
                        {stockRequest.items && stockRequest.items.length > 0 ? (
                            stockRequest.items.map((item, index) => {
                                const reviewItem = gaReviewItems.find((review) => review.id === item.id);
                                const reviewResult = reviewItem?.ga_review_result || item.ga_review_result || 'pending_review';
                                const isWarehouseStock = reviewResult === 'warehouse_stock';

                                return (
                                    <motion.tr
                                        key={item.id}
                                        initial={{ opacity: 0, y: 10 }}
                                        animate={{ opacity: 1, y: 0 }}
                                        transition={{ delay: 0.3 + index * 0.05 }}
                                        className="hover:bg-gray-50 transition-colors"
                                    >
                                        <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-500">{index + 1}</td>
                                        <td className="px-5 py-4">
                                            <div className={`text-sm text-gray-900 ${isWarehouseStock ? 'line-through text-gray-400' : ''}`}>{item.item_name}</div>
                                            {item.item_description && <div className="text-sm text-gray-400 mt-1">{item.item_description}</div>}
                                        </td>
                                        <td className={`px-5 py-4 whitespace-nowrap text-sm text-right ${isWarehouseStock ? 'line-through text-gray-400' : 'text-gray-900'}`}>{item.quantity} {item.unit}</td>
                                        <td className="px-5 py-4 min-w-64 text-sm text-gray-700">
                                            {canReviewGa && reviewItem ? (
                                                <div className="space-y-2">
                                                    <select
                                                        value={reviewItem.ga_review_result}
                                                        onChange={(event) => onGaReviewItemChange?.(item.id, 'ga_review_result', event.target.value)}
                                                        className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary"
                                                    >
                                                        <option value="pending_review">Pending review</option>
                                                        <option value="warehouse_stock">Warehouse stock</option>
                                                        <option value="need_procurement">Need procurement</option>
                                                    </select>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        max={item.quantity}
                                                        value={reviewItem.warehouse_available_qty}
                                                        onChange={(event) => onGaReviewItemChange?.(item.id, 'warehouse_available_qty', event.target.value)}
                                                        placeholder="Warehouse qty"
                                                        className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary"
                                                    />
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        max={item.quantity}
                                                        value={reviewItem.procurement_quantity}
                                                        onChange={(event) => onGaReviewItemChange?.(item.id, 'procurement_quantity', event.target.value)}
                                                        placeholder="Procurement qty"
                                                        className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary"
                                                    />
                                                    <textarea
                                                        value={reviewItem.ga_review_note}
                                                        onChange={(event) => onGaReviewItemChange?.(item.id, 'ga_review_note', event.target.value)}
                                                        rows={2}
                                                        placeholder="GA note"
                                                        className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary"
                                                    />
                                                </div>
                                            ) : (
                                                <div className="space-y-1">
                                                    <span className="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium capitalize text-gray-700">{reviewResult.replace(/_/g, ' ')}</span>
                                                    {item.warehouse_available_qty !== null && item.warehouse_available_qty !== undefined && <div className="text-xs text-gray-500">Warehouse: {item.warehouse_available_qty}</div>}
                                                    {item.ga_review_note && <div className="text-xs text-gray-500">{item.ga_review_note}</div>}
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-5 py-4 whitespace-nowrap text-center">
                                            {item.image_path ? (
                                                <a
                                                    href={`/storage/${item.image_path}`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="text-blue-600 hover:text-blue-800"
                                                >
                                                    <Eye className="w-4 h-4 inline" />
                                                </a>
                                            ) : (
                                                <span className="text-gray-400">-</span>
                                            )}
                                        </td>
                                    </motion.tr>
                                );
                            })
                        ) : (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="px-5 py-8 text-center text-sm text-gray-500"
                                >
                                    No items found
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </motion.div>
    );
}

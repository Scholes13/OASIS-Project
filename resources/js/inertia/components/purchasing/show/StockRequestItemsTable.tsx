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

function getReviewBadgeClass(reviewResult: string) {
    if (reviewResult === 'warehouse_stock') {
        return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
    }

    if (reviewResult === 'need_procurement') {
        return 'bg-blue-50 text-blue-700 ring-blue-200';
    }

    return 'bg-amber-50 text-amber-700 ring-amber-200';
}

export function StockRequestItemsTable({ stockRequest, canReviewGa = false, gaReviewItems = [], onGaReviewItemChange }: StockRequestItemsTableProps) {
    const itemCount = stockRequest.items?.length || 0;

    return (
        <section className="space-y-5">
            <div className="flex items-baseline justify-between">
                <h2 className="text-xs font-semibold uppercase tracking-wider text-slate-500">Items</h2>
                <span className="text-sm text-slate-400">
                    {itemCount} {itemCount === 1 ? 'item' : 'items'}
                </span>
            </div>

            <div className="-mx-2 overflow-x-auto">
                <table className="min-w-full">
                    <thead>
                        <tr className="border-b border-slate-200">
                            <th className="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">No</th>
                            <th className="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Item</th>
                            <th className="px-3 py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Qty</th>
                            <th className="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">GA Review</th>
                            <th className="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Image</th>
                        </tr>
                    </thead>
                    <tbody>
                        {stockRequest.items && stockRequest.items.length > 0 ? (
                            stockRequest.items.map((item, index) => {
                                const reviewItem = gaReviewItems.find((review) => review.id === item.id);
                                const reviewResult = reviewItem?.ga_review_result || item.ga_review_result || 'pending_review';
                                const isWarehouseStock = reviewResult === 'warehouse_stock';

                                return (
                                    <tr
                                        key={item.id}
                                        className="border-b border-slate-100 transition-colors hover:bg-slate-50/50"
                                    >
                                        <td className="whitespace-nowrap px-3 py-4 text-sm tabular-nums text-slate-400">{index + 1}</td>
                                        <td className="px-3 py-4">
                                            <div className={`text-sm font-medium text-slate-950 ${isWarehouseStock ? 'text-slate-400 line-through' : ''}`}>
                                                {item.item_name}
                                            </div>
                                            {item.item_description && (
                                                <div className="mt-0.5 text-sm text-slate-500">{item.item_description}</div>
                                            )}
                                        </td>
                                        <td className={`whitespace-nowrap px-3 py-4 text-right text-sm font-medium tabular-nums ${isWarehouseStock ? 'text-slate-400 line-through' : 'text-slate-950'}`}>
                                            {item.quantity} <span className="text-slate-400">{item.unit}</span>
                                        </td>
                                        <td className="min-w-64 px-3 py-4 text-sm text-slate-700">
                                            {canReviewGa && reviewItem ? (
                                                <div className="space-y-2">
                                                    <select
                                                        value={reviewItem.ga_review_result}
                                                        onChange={(event) => onGaReviewItemChange?.(item.id, 'ga_review_result', event.target.value)}
                                                        className="w-full rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
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
                                                        className="w-full rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                                    />
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        max={item.quantity}
                                                        value={reviewItem.procurement_quantity}
                                                        onChange={(event) => onGaReviewItemChange?.(item.id, 'procurement_quantity', event.target.value)}
                                                        placeholder="Procurement qty"
                                                        className="w-full rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                                    />
                                                    <textarea
                                                        value={reviewItem.ga_review_note}
                                                        onChange={(event) => onGaReviewItemChange?.(item.id, 'ga_review_note', event.target.value)}
                                                        rows={2}
                                                        placeholder="GA note"
                                                        className="w-full rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                                    />
                                                </div>
                                            ) : (
                                                <div className="space-y-1">
                                                    <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium capitalize ring-1 ring-inset ${getReviewBadgeClass(reviewResult)}`}>
                                                        {reviewResult.replace(/_/g, ' ')}
                                                    </span>
                                                    {item.warehouse_available_qty !== null && item.warehouse_available_qty !== undefined && (
                                                        <div className="text-xs text-slate-500">Warehouse: {item.warehouse_available_qty}</div>
                                                    )}
                                                    {item.ga_review_note && (
                                                        <div className="text-xs text-slate-500">{item.ga_review_note}</div>
                                                    )}
                                                </div>
                                            )}
                                        </td>
                                        <td className="whitespace-nowrap px-3 py-4 text-center">
                                            {item.image_path ? (
                                                <a
                                                    href={`/storage/${item.image_path}`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex items-center justify-center text-slate-400 transition-colors hover:text-slate-700"
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </a>
                                            ) : (
                                                <span className="text-slate-300">—</span>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })
                        ) : (
                            <tr>
                                <td colSpan={5} className="px-3 py-8 text-center text-sm text-slate-400">
                                    No items found
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </section>
    );
}

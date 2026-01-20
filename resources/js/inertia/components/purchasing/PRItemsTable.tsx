import React from 'react';
import { PRItem, PurchaseRequest } from '@/types/purchasing';
import { Card } from '@/components/ui/Card';
import { FileText } from 'lucide-react';

/**
 * PRItemsTable Component
 * 
 * Displays a table of Purchase Request items with detailed information.
 * 
 * Columns:
 * - No: Sequential number
 * - Item: Item name, brand, description, supplier
 * - Expense Dept: Department responsible for the expense
 * - Qty: Quantity and unit
 * - Unit Price: Price per unit with currency
 * - Total: Total price (quantity × unit price)
 * 
 * Features:
 * - Responsive table with horizontal scroll on mobile
 * - Hover effects on rows
 * - Footer with grand total
 * - Empty state with icon
 * - Indonesian number formatting (no decimals, dot separators)
 * 
 * @component
 * @example
 * ```tsx
 * <PRItemsTable 
 *   items={purchaseRequest.items} 
 *   currency={purchaseRequest.currency}
 *   totalAmount={purchaseRequest.total_amount}
 * />
 * ```
 */

interface PRItemsTableProps {
    items: PRItem[];
    currency: string;
    totalAmount: number;
}

export function PRItemsTable({ items, currency, totalAmount }: PRItemsTableProps) {
    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    };

    if (!items || items.length === 0) {
        return (
            <Card>
                <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 className="text-base font-semibold text-gray-900">Items</h3>
                    <span className="text-sm text-gray-500">0 items</span>
                </div>
                <div className="p-8 text-center">
                    <FileText className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                    <p className="text-sm text-gray-500">No items found</p>
                </div>
            </Card>
        );
    }

    return (
        <Card>
            <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 className="text-base font-semibold text-gray-900">Items</h3>
                <span className="text-sm text-gray-500">
                    {items.length} {items.length === 1 ? 'item' : 'items'}
                </span>
            </div>
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                No
                            </th>
                            <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Item
                            </th>
                            <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Expense Dept
                            </th>
                            <th className="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Qty
                            </th>
                            <th className="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Unit Price
                            </th>
                            <th className="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-100">
                        {items.map((item, index) => (
                            <tr key={item.id} className="hover:bg-gray-50 transition-colors">
                                <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {index + 1}
                                </td>
                                <td className="px-5 py-4">
                                    <div className="text-sm text-gray-900">{item.item_name}</div>
                                    {item.brand_name && (
                                        <div className="text-sm text-gray-500">Brand: {item.brand_name}</div>
                                    )}
                                    {item.item_description && (
                                        <div className="text-sm text-gray-400 mt-1">{item.item_description}</div>
                                    )}
                                    {item.supplier_name && (
                                        <div className="text-xs text-gray-400 mt-1">
                                            Supplier: {item.supplier_name}
                                        </div>
                                    )}
                                </td>
                                <td className="px-5 py-4 whitespace-nowrap">
                                    <div className="text-sm text-gray-900">
                                        {item.expense_department?.name || 'N/A'}
                                    </div>
                                    {item.expense_department?.code && (
                                        <div className="text-xs text-gray-500">
                                            {item.expense_department.code}
                                        </div>
                                    )}
                                </td>
                                <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    {formatCurrency(item.quantity)} {item.unit}
                                </td>
                                <td className="px-5 py-4 whitespace-nowrap text-right">
                                    <span className="text-sm text-gray-500">{item.currency || currency}</span>{' '}
                                    <span className="text-sm text-gray-900">{formatCurrency(item.unit_price)}</span>
                                </td>
                                <td className="px-5 py-4 whitespace-nowrap text-right">
                                    <span className="text-sm text-gray-500">{item.currency || currency}</span>{' '}
                                    <span className="text-sm text-gray-900">{formatCurrency(item.total_price)}</span>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                    <tfoot className="bg-gray-50">
                        <tr>
                            <td colSpan={5} className="px-5 py-4 text-right text-sm font-semibold text-gray-900">
                                Total Amount
                            </td>
                            <td className="px-5 py-4 whitespace-nowrap text-right">
                                <span className="text-sm text-gray-900">{currency}</span>{' '}
                                <span className="text-base font-semibold text-gray-900">
                                    {formatCurrency(totalAmount)}
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </Card>
    );
}

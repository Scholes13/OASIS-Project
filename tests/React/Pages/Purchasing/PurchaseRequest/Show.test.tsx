import { beforeEach, describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import { type ComponentProps } from 'react';
import Show from '@/Pages/Purchasing/PurchaseRequest/Show';
import type { PRShowProps } from '@/types/purchasing';

vi.mock('@inertiajs/react', async () => {
    const actual = await vi.importActual<typeof import('@inertiajs/react')>('@inertiajs/react');

    return {
        ...actual,
        Head: () => null,
        Link: ({ children, href, ...props }: ComponentProps<'a'>) => (
            <a href={href} {...props}>
                {children}
            </a>
        ),
    };
});

describe('PurchaseRequest Show page', () => {
    const baseProps: PRShowProps = {
        purchaseRequest: {
            id: 42,
            pr_number: 'PR-WNS-2026-042',
            business_unit_id: 1,
            department_id: 1,
            user_id: 1,
            used_for: 'Office equipment',
            date_of_request: '2026-03-31',
            expected_date: null,
            designated_date: null,
            status: 'draft',
            submitted_at: null,
            approved_at: null,
            rejected_at: null,
            voided_at: null,
            offline_approved_at: null,
            offline_approved_by: null,
            offline_approval_notes: null,
            offline_approval_document_path: null,
            offline_approval_document_name: null,
            approval_workflow: null,
            is_sequential_approval: false,
            total_amount: 0,
            currency: 'IDR',
            supporting_document_path: 'purchase-requests/supporting-documents/supporting.pdf',
            supporting_document_name: 'supporting.pdf',
            approval_notes: null,
            created_at: '2026-03-31T00:00:00Z',
            updated_at: '2026-03-31T00:00:00Z',
            user: {
                id: 1,
                name: 'Test User',
                email: 'test@example.com',
                role: 'user',
                avatar_url: null,
                primary_department_id: 1,
            },
            department: {
                id: 1,
                name: 'General Affairs',
                code: 'GA',
                business_unit_id: 1,
            },
            business_unit: {
                id: 1,
                code: 'WNS',
                name: 'WNS Business Unit',
                logo: null,
            },
            items: [],
            approvals: [],
        } as PRShowProps['purchaseRequest'],
        can: {
            edit: false,
            delete: false,
            void: false,
            resubmit: false,
            resendApprovalEmail: false,
            approve: false,
            reject: false,
            downloadPdf: false,
            markOfflineApproved: false,
            supportingDocument: true,
        },
    };

    beforeEach(() => {
        vi.clearAllMocks();
        global.route = vi.fn((name?: string, params?: Record<string, string | number>) => {
            if (!name) {
                return {
                    has: (routeName: string) => [
                        'purchase-requests.supporting-document',
                        'purchase-requests.supporting-document.download',
                    ].includes(routeName),
                };
            }

            if (params && typeof params === 'object') {
                return `/${name}?${new URLSearchParams(
                    Object.entries(params).reduce<Record<string, string>>((carry, [key, value]) => {
                        carry[key] = String(value);
                        return carry;
                    }, {})
                ).toString()}`;
            }

            return `/${name}`;
        }) as any;
    });

    it('uses the authenticated supporting document route when available', () => {
        render(<Show {...baseProps} />);

        expect(global.route).toHaveBeenCalledWith();
        expect(global.route).toHaveBeenCalledWith('purchase-requests.supporting-document', {
            purchaseRequest: 42,
        });
        expect(global.route).toHaveBeenCalledWith('purchase-requests.supporting-document.download', {
            purchaseRequest: 42,
        });

        expect(screen.getByTitle('View document')).toHaveAttribute(
            'href',
            '/purchase-requests.supporting-document?purchaseRequest=42'
        );
        expect(screen.getByTitle('Download document')).toHaveAttribute(
            'href',
            '/purchase-requests.supporting-document.download?purchaseRequest=42'
        );
    });

    it('hides supporting document actions when the user is not allowed to access the file', () => {
        render(
            <Show
                {...baseProps}
                can={{
                    ...baseProps.can!,
                    supportingDocument: false,
                }}
            />
        );

        expect(screen.queryByTitle('View document')).not.toBeInTheDocument();
        expect(screen.queryByTitle('Download document')).not.toBeInTheDocument();
    });
});

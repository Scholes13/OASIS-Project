import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen } from '@testing-library/react';
import { type ComponentProps } from 'react';
import Show from '@/Pages/Purchasing/StockRequest/Show';
import type { STShowProps } from '@/types/purchasing';
import { router } from '@inertiajs/react';

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
        router: {
            post: vi.fn(),
        },
    };
});

describe('StockRequest Show page', () => {
    const baseProps: STShowProps = {
        stockRequest: {
            id: 7,
            st_number: 'ST-WNS-2026-007',
            business_unit_id: 1,
            department_id: 1,
            user_id: 1,
            purpose: 'Warehouse replenishment',
            date_of_request: '2026-04-17',
            expected_date: null,
            status: 'in_approval',
            routes_directly_to_purchasing: false,
            skips_ga_review: false,
            submitted_at: '2026-04-17T00:00:00Z',
            approved_at: null,
            rejected_at: null,
            voided_at: null,
            offline_approved_at: null,
            offline_approval_document_path: 'offline-approvals/stock-requests/7/proof.pdf',
            offline_approval_document_name: 'proof.pdf',
            created_at: '2026-04-17T00:00:00Z',
            updated_at: '2026-04-17T00:00:00Z',
            user: {
                id: 1,
                name: 'Stock Owner',
                email: 'owner@example.com',
                role: 'user',
                avatar_url: null,
                primary_department_id: 1,
            },
            department: {
                id: 1,
                name: 'Warehouse',
                code: 'WH',
                business_unit_id: 1,
            },
            business_unit: {
                id: 1,
                name: 'WNS Business Unit',
                code: 'WNS',
                logo: null,
            },
            items: [],
            approvals: [],
        } as STShowProps['stockRequest'],
        can: {
            edit: false,
            delete: false,
            void: true,
            resubmit: false,
            resendApprovalEmail: true,
            approve: false,
            reject: false,
            downloadPdf: true,
            markOfflineApproved: true,
            offlineApprovalDocument: true,
        },
    };

    beforeEach(() => {
        vi.clearAllMocks();
        global.route = vi.fn((name?: string, params?: Record<string, string | number>) => {
            if (!name) {
                return {
                    has: (routeName: string) => [
                        'stock-requests.offline-approval-document',
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

    it('uses the stock approval home when opened from approval context', () => {
        render(
            <Show
                {...baseProps}
                approvalContext={{
                    approvalId: 99,
                    canApprove: true,
                    approvalStatus: 'pending',
                }}
            />
        );

        expect(screen.getAllByRole('link')[0]).toHaveAttribute('href', '/stock-approvals.index');
    });

    it('posts the resend approval email action when allowed', () => {
        render(<Show {...baseProps} />);

        fireEvent.click(screen.getByRole('button', { name: /resend email/i }));

        expect(global.route).toHaveBeenCalledWith('stock-requests.resend-approval-email', { stockRequest: 7 });
        expect(router.post).toHaveBeenCalledWith(
            '/stock-requests.resend-approval-email?stockRequest=7',
            {},
            expect.objectContaining({
                onSuccess: expect.any(Function),
                onError: expect.any(Function),
                onFinish: expect.any(Function),
            })
        );
    });

    it('uses the authenticated offline approval document route when available', () => {
        render(<Show {...baseProps} />);

        expect(global.route).toHaveBeenCalledWith();
        expect(global.route).toHaveBeenCalledWith('stock-requests.offline-approval-document', {
            stockRequest: 7,
        });
        expect(screen.getByTitle('View document')).toHaveAttribute(
            'href',
            '/stock-requests.offline-approval-document?stockRequest=7'
        );
    });

    it('hides offline approval document actions when the user is not allowed to access the file', () => {
        render(
            <Show
                {...baseProps}
                can={{
                    ...baseProps.can!,
                    offlineApprovalDocument: false,
                }}
            />
        );

        expect(screen.queryByTitle('View offline approval document')).not.toBeInTheDocument();
    });

    it('omits department approval and GA review for requests originating from GA', () => {
        render(
            <Show
                {...baseProps}
                stockRequest={{
                    ...baseProps.stockRequest,
                    status: 'ready_for_purchasing',
                    routes_directly_to_purchasing: true,
                    skips_ga_review: true,
                    approved_at: '2026-04-18T00:00:00Z',
                    department: {
                        ...baseProps.stockRequest.department,
                        is_ga_stock_review_department: true,
                    },
                    admin_task: {
                        id: 10,
                        assigned_admin_id: null,
                        status: 'pending_followup',
                        entered_at: '2026-04-18T00:00:00Z',
                        started_at: null,
                        completed_at: null,
                    },
                }}
            />
        );

        expect(screen.queryByText('General Affairs Review')).not.toBeInTheDocument();
        expect(screen.queryByText('Department Approval')).not.toBeInTheDocument();
        expect(screen.getAllByText('Purchasing Follow-up').length).toBeGreaterThan(0);
        expect(screen.getAllByText('In progress').length).toBeGreaterThan(0);
    });

    it('keeps historical department approval while omitting skipped GA review', () => {
        render(
            <Show
                {...baseProps}
                stockRequest={{
                    ...baseProps.stockRequest,
                    status: 'ready_for_purchasing',
                    routes_directly_to_purchasing: false,
                    skips_ga_review: true,
                }}
            />
        );

        expect(screen.getAllByText('Department Approval').length).toBeGreaterThan(0);
        expect(screen.queryByText('General Affairs Review')).not.toBeInTheDocument();
    });

    it('uses approver snapshot when historical user relation is unavailable', () => {
        render(
            <Show
                {...baseProps}
                stockRequest={{
                    ...baseProps.stockRequest,
                    status: 'ready_for_purchasing',
                    approvals: [{
                        id: 55,
                        approver_id: 99,
                        step_order: 1,
                        status: 'approved',
                        notes: null,
                        responded_at: '2026-04-18T00:00:00Z',
                        approver: null,
                        metadata: {
                            approver_snapshot: {
                                name: 'Historical Department Head',
                            },
                        },
                    }],
                }}
            />
        );

        expect(screen.getAllByText('Historical Department Head').length).toBeGreaterThan(0);
    });
});

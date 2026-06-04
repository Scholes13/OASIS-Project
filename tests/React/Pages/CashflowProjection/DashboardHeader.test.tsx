import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import DashboardHeader from '@/Pages/CashflowProjection/components/DashboardHeader';

vi.mock('@/lib/download', () => ({
    openDownloadInSameTab: vi.fn(),
}));

const baseProps = {
    periodTitle: 'FY 2026',
    periodCaption: 'Yearly portfolio view across Jan-Dec 2026.',
    filteredEntryCount: 39,
    financeIncome: 0,
    draftMode: 'year' as const,
    draftYear: '2026',
    draftMonth: '6',
    draftStartDate: '2026-01-01',
    draftEndDate: '2026-12-31',
    draftPeriodTitle: 'FY 2026',
    availableYears: [2026],
    filtersYear: 2026,
    filtersMonth: 6,
    filtersStartDate: '2026-01-01',
    filtersEndDate: '2026-12-31',
    rangeSelectionIncomplete: false,
    hasLinkedUnits: true,
    scope: 'consolidated' as const,
    exportParams: { year: 2026, month: 6 },
    formatIsoDate: (dateValue: string) => dateValue,
    onDraftModeChange: vi.fn(),
    onDraftYearChange: vi.fn(),
    onDraftMonthChange: vi.fn(),
    onDraftStartDateChange: vi.fn(),
    onDraftEndDateChange: vi.fn(),
    onApplyFilters: vi.fn(),
    onResetToCurrentMonth: vi.fn(),
};

describe('DashboardHeader', () => {
    it('keeps Add Entry as the only blue action while adjacent controls use a soft gray segmented style', () => {
        render(<DashboardHeader {...baseProps} />);

        expect(screen.getByRole('link', { name: /add entry/i })).toHaveClass('bg-[#2f6f9f]');
        expect(screen.getByRole('button', { name: /export excel/i })).not.toHaveClass('bg-[#1f5f9f]');
        expect(screen.getByRole('button', { name: /consolidated/i })).toHaveClass('bg-white');
        expect(screen.getByRole('button', { name: /consolidated/i })).not.toHaveClass('bg-slate-900');
        expect(screen.getByRole('button', { name: /bu only/i })).not.toHaveClass('bg-primary');
    });
});

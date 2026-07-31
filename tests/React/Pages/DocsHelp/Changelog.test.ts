import { describe, expect, it } from 'vitest';
import { ChangelogArticles } from '@/Pages/DocsHelp/data/articles/changelog';

describe('Docs & Help changelog', () => {
    it('publishes the July 2026 staging update as the newest article', () => {
        const latest = ChangelogArticles[0];

        expect(latest.id).toBe('changelog-v4-beta-july-2026');
        expect(latest.title).toBe('OASIS V4 Beta - IT Support, Purchasing & Workflow Update');
        expect(latest.updatedAt).toBe('2026-07-31');
        expect(latest.bilingual).toBe(true);
        expect(latest.toc.map((item) => item.id)).toEqual([
            'overview',
            'it-support',
            'dashboard-reporting',
            'purchase-request',
            'stock-request',
            'workflow-updates',
            'platform-reliability',
            'impact',
        ]);
        expect(JSON.stringify(latest.content)).toContain('Validasi 212 Ticket Historis');
        expect(JSON.stringify(latest.content)).toContain('48-hour SLA for every priority');
        expect(JSON.stringify(latest.content)).toContain('Riwayat approver tetap akurat');
        expect(JSON.stringify(latest.content)).toContain('Approval kepala departemen dapat berjalan paralel');
    });
});

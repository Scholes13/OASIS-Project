import { describe, expect, it } from 'vitest';
import { ChangelogArticles } from '@/Pages/DocsHelp/data/articles/changelog';

describe('Docs & Help changelog', () => {
    it('publishes the July 31 Activity update as the newest article', () => {
        const latest = ChangelogArticles[0];

        expect(latest.id).toBe('changelog-v4-beta-july-31-activity');
        expect(latest.title).toBe('OASIS V4 Beta - Activity Scope & Stability Update');
        expect(latest.updatedAt).toBe('2026-07-31');
        expect(latest.bilingual).toBe(true);
        expect(latest.toc.map((item) => item.id)).toEqual([
            'overview',
            'task-scope',
            'stability',
            'impact',
        ]);
        expect(JSON.stringify(latest.content)).toContain('My Tasks and Team for staff');
        expect(JSON.stringify(latest.content)).toContain('This update only affects the Activity module');
    });
});

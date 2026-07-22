import { render, screen } from '@testing-library/react';
import { type ComponentProps } from 'react';
import { describe, expect, it, vi } from 'vitest';
import Article from '@/Pages/Ticket/Knowledge/Article';
import type { KnowledgeArticle } from '@/types/ticket';

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ children, href, ...props }: ComponentProps<'a'>) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
}));

vi.mock('framer-motion', () => ({
    motion: {
        article: ({ children, ...props }: ComponentProps<'article'>) => (
            <article {...props}>{children}</article>
        ),
    },
}));

describe('Knowledge article security', () => {
    it('renders stored markup as inert text', () => {
        global.route = vi.fn(() => '/it-support/knowledge') as typeof route;
        const content = '<img src=x onerror="alert(1)"><script>alert(2)</script>Safe';
        const article = {
            id: 1,
            title: 'Security article',
            slug: 'security-article',
            content,
            category: null,
            is_published: true,
            views_count: 0,
            author: null,
            published_at: '2026-07-12T00:00:00Z',
            meta_description: null,
            tags: [],
            created_at: '2026-07-12T00:00:00Z',
            updated_at: '2026-07-12T00:00:00Z',
        } satisfies KnowledgeArticle;

        const { container } = render(<Article article={article} relatedArticles={[]} />);

        expect(screen.getByText(content)).toBeInTheDocument();
        expect(container.querySelector('script')).toBeNull();
        expect(container.querySelector('img')).toBeNull();
    });
});

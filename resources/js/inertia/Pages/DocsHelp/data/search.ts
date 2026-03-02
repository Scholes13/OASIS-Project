import type { Article, ArticleBlock, Category } from './types';

/**
 * Extracts all searchable plain text from an article's content blocks.
 */
function extractText(blocks: ArticleBlock[]): string {
    const parts: string[] = [];

    for (const block of blocks) {
        switch (block.type) {
            case 'paragraph':
                parts.push(stripHtml(block.html));
                break;
            case 'heading':
                parts.push(block.text);
                break;
            case 'ordered-list':
            case 'unordered-list':
                if (block.intro) {
                    parts.push(stripHtml(block.intro));
                }
                for (const item of block.items) {
                    parts.push(stripHtml(item));
                }
                break;
            case 'callout':
                parts.push(block.title, stripHtml(block.body));
                break;
            case 'step-list':
                if (block.intro) {
                    parts.push(stripHtml(block.intro));
                }
                for (const s of block.steps) {
                    parts.push(s.title, stripHtml(s.body));
                }
                break;
            case 'status-list':
                if (block.intro) {
                    parts.push(stripHtml(block.intro));
                }
                for (const s of block.items) {
                    parts.push(s.label, stripHtml(s.description));
                }
                break;
            case 'faq':
                for (const faq of block.items) {
                    parts.push(faq.question, stripHtml(faq.answer));
                }
                break;
        }
    }

    return parts.join(' ').toLowerCase();
}

function stripHtml(html: string): string {
    return html.replace(/<[^>]*>/g, '');
}

export interface SearchResult {
    article: Article;
    matchType: 'title' | 'description' | 'content';
}

/**
 * Full-text search across all articles.
 * Searches: title, description, and all content blocks.
 * Returns results sorted by relevance (title > description > content).
 */
export function searchArticles(articles: Article[], query: string): SearchResult[] {
    if (!query.trim()) {
        return [];
    }

    const q = query.toLowerCase().trim();
    const results: SearchResult[] = [];

    for (const article of articles) {
        if (article.title.toLowerCase().includes(q)) {
            results.push({ article, matchType: 'title' });
        } else if (article.description.toLowerCase().includes(q)) {
            results.push({ article, matchType: 'description' });
        } else if (extractText(article.content).includes(q)) {
            results.push({ article, matchType: 'content' });
        }
    }

    // Sort: title matches first, then description, then content
    const order = { title: 0, description: 1, content: 2 };
    results.sort((a, b) => order[a.matchType] - order[b.matchType]);

    return results;
}

/**
 * Get articles filtered by category.
 */
export function getArticlesByCategory(articles: Article[], categoryKey: string): Article[] {
    return articles.filter(a => a.category === categoryKey);
}

/**
 * Get popular/featured articles.
 */
export function getPopularArticles(articles: Article[]): Article[] {
    return articles.filter(a => a.popular);
}

/**
 * Get category count (number of articles per category).
 */
export function getCategoryCounts(articles: Article[], categories: Category[]): Record<string, number> {
    const counts: Record<string, number> = {};
    for (const cat of categories) {
        counts[cat.key] = articles.filter(a => a.category === cat.key).length;
    }
    return counts;
}

/**
 * Format date string for display.
 */
export function formatArticleDate(dateStr: string): string {
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays === 0) {
        return 'Updated today';
    }
    if (diffDays === 1) {
        return 'Updated yesterday';
    }
    if (diffDays < 7) {
        return `Updated ${diffDays} days ago`;
    }
    if (diffDays < 30) {
        const weeks = Math.floor(diffDays / 7);
        return `Updated ${weeks} week${weeks > 1 ? 's' : ''} ago`;
    }
    if (diffDays < 365) {
        const months = Math.floor(diffDays / 30);
        return `Updated ${months} month${months > 1 ? 's' : ''} ago`;
    }

    return `Updated on ${date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}`;
}

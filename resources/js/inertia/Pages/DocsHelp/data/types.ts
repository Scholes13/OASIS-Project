export type CategoryKey =
    | 'getting-started'
    | 'purchase-request'
    | 'stock-request'
    | 'approvals'
    | 'activity-tracking'
    | 'cashflow-projection'
    | 'dashboard'
    | 'changelog'
    | 'faq';

export interface Category {
    key: CategoryKey;
    label: string;
    description: string;
    icon: string; // lucide icon name
    color: string;
}

export interface TocItem {
    id: string;
    label: string;
}

export interface Article {
    id: string;
    category: CategoryKey;
    title: string;
    description: string;
    author: string;
    updatedAt: string;
    toc: TocItem[];
    content: ArticleBlock[];
    popular?: boolean;
    /** When true, article content contains bilingual ID/EN spans and a language toggle will be shown. */
    bilingual?: boolean;
}

// ── Content block types ──────────────────────────────
export type ArticleBlock =
    | ParagraphBlock
    | HeadingBlock
    | OrderedListBlock
    | UnorderedListBlock
    | CalloutBlock
    | StepListBlock
    | StatusListBlock
    | FaqBlock;

export interface ParagraphBlock {
    type: 'paragraph';
    id?: string;
    html: string; // supports <strong>, <em>, <code>, etc.
}

export interface HeadingBlock {
    type: 'heading';
    id: string;
    level: 2 | 3;
    text: string;
}

export interface OrderedListBlock {
    type: 'ordered-list';
    id?: string;
    intro?: string;
    items: string[]; // supports inline html
}

export interface UnorderedListBlock {
    type: 'unordered-list';
    id?: string;
    intro?: string;
    items: string[]; // supports inline html
}

export interface CalloutBlock {
    type: 'callout';
    variant: 'info' | 'warning' | 'tip';
    title: string;
    body: string;
}

export interface StepListBlock {
    type: 'step-list';
    id?: string;
    intro?: string;
    steps: { title: string; body: string }[];
}

export interface StatusListBlock {
    type: 'status-list';
    id?: string;
    intro?: string;
    items: { label: string; description: string; color: 'gray' | 'blue' | 'amber' | 'emerald' | 'red' }[];
}

export interface FaqBlock {
    type: 'faq';
    id?: string;
    items: { question: string; answer: string }[];
}

import type { Article } from './types';
import { GettingStartedArticles } from './articles/getting-started';
import { PurchaseRequestArticles } from './articles/purchase-request';
import { StockRequestArticles } from './articles/stock-request';
import { ApprovalsArticles } from './articles/approvals';
import { ActivityTrackingArticles } from './articles/activity-tracking';
import { CashflowProjectionArticles } from './articles/cashflow-projection';
import { DashboardArticles } from './articles/dashboard';
import { ChangelogArticles } from './articles/changelog';
import { FaqArticles } from './articles/faq';

// Add new DocsHelp articles in matching file under ./articles/.
// Create a new category file only when categories.ts gains matching key.
// Keep this orchestrator import-compatible for existing consumers.
export const articles: Article[] = [
    ...GettingStartedArticles,
    ...PurchaseRequestArticles,
    ...StockRequestArticles,
    ...ApprovalsArticles,
    ...ActivityTrackingArticles,
    ...CashflowProjectionArticles,
    ...DashboardArticles,
    ...ChangelogArticles,
    ...FaqArticles,
];

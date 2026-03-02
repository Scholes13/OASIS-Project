import { FileText, ChevronRight } from 'lucide-react';
import type { Article } from '../data/types';
import { formatArticleDate } from '../data/search';

interface ArticleListItemProps {
    article: Article;
    onClick: () => void;
    showCategory?: boolean;
    categoryLabel?: string;
}

export default function ArticleListItem({ article, onClick, showCategory = false, categoryLabel }: ArticleListItemProps) {
    return (
        <div
            onClick={onClick}
            className="flex items-center justify-between p-4 border-b border-slate-100 last:border-0 hover:bg-slate-50 cursor-pointer transition-colors"
        >
            <div className="flex items-center text-sm font-medium text-slate-700 min-w-0 flex-1 mr-4">
                <FileText className="w-5 h-5 text-[#16599c] mr-3 flex-shrink-0" />
                <div className="min-w-0">
                    <span className="block truncate">{article.title}</span>
                    {showCategory && categoryLabel && (
                        <span className="text-xs text-slate-400 font-normal">{categoryLabel}</span>
                    )}
                </div>
            </div>
            <div className="flex items-center text-xs text-slate-400 flex-shrink-0">
                <span className="hidden sm:inline">{formatArticleDate(article.updatedAt)}</span>
                <ChevronRight className="w-4 h-4 ml-3" />
            </div>
        </div>
    );
}

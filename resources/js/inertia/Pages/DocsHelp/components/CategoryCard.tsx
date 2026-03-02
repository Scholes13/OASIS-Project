import {
    Zap,
    ShoppingCart,
    Package,
    CheckCircle,
    ClipboardList,
    BarChart3,
    HelpCircle,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

const iconMap: Record<string, LucideIcon> = {
    Zap,
    ShoppingCart,
    Package,
    CheckCircle,
    ClipboardList,
    BarChart3,
    HelpCircle,
};

interface CategoryCardProps {
    label: string;
    description: string;
    iconName: string;
    articleCount: number;
    onClick: () => void;
}

export default function CategoryCard({ label, description, iconName, articleCount, onClick }: CategoryCardProps) {
    const Icon = iconMap[iconName] ?? HelpCircle;

    return (
        <div
            onClick={onClick}
            className="bg-white border border-slate-200 rounded-xl p-6 cursor-pointer hover:shadow-md hover:border-[#16599c] transition-all group"
        >
            <div className="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center mb-4 text-[#16599c] group-hover:scale-110 transition-transform">
                <Icon className="w-6 h-6" />
            </div>
            <h3 className="text-lg font-semibold text-slate-900 mb-1">{label}</h3>
            <p className="text-sm text-slate-500 line-clamp-2 mb-3">{description}</p>
            <span className="text-xs text-slate-400">
                {articleCount} {articleCount === 1 ? 'article' : 'articles'}
            </span>
        </div>
    );
}

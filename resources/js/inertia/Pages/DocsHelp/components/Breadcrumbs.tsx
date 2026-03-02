import { ChevronRight } from 'lucide-react';

interface BreadcrumbItem {
    label: string;
    onClick?: () => void;
}

interface BreadcrumbsProps {
    items: BreadcrumbItem[];
}

export default function Breadcrumbs({ items }: BreadcrumbsProps) {
    return (
        <div className="flex items-center text-sm text-slate-500 pb-8 px-8 border-b border-slate-100 bg-white max-w-6xl mx-auto w-full flex-wrap gap-y-1">
            {items.map((item, i) => (
                <span key={i} className="flex items-center">
                    {i > 0 && <ChevronRight className="w-4 h-4 mx-2 text-slate-300" />}
                    {item.onClick ? (
                        <button onClick={item.onClick} className="hover:text-[#16599c] transition-colors">
                            {item.label}
                        </button>
                    ) : (
                        <span className="text-slate-900 font-medium truncate max-w-xs">{item.label}</span>
                    )}
                </span>
            ))}
        </div>
    );
}

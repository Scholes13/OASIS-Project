import type { TocItem } from '../data/types';

interface TableOfContentsProps {
    items: TocItem[];
    activeId?: string;
}

export default function TableOfContents({ items, activeId }: TableOfContentsProps) {
    if (items.length === 0) {
        return null;
    }

    const handleClick = (e: React.MouseEvent<HTMLAnchorElement>, id: string) => {
        e.preventDefault();
        const el = document.getElementById(id);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    return (
        <div className="hidden lg:block w-56 flex-shrink-0 pt-2">
            <div className="sticky top-6">
                <h4 className="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-5">
                    ON THIS PAGE
                </h4>
                <nav className="flex flex-col space-y-3.5 relative">
                    <div className="absolute left-0 top-0 bottom-0 w-px bg-slate-200" />
                    {items.map((item) => {
                        const isActive = activeId ? item.id === activeId : false;
                        return (
                            <a
                                key={item.id}
                                href={`#${item.id}`}
                                onClick={(e) => handleClick(e, item.id)}
                                className={`text-sm border-l-[2px] pl-4 -ml-px relative z-10 transition-colors ${
                                    isActive
                                        ? 'text-[#16599c] font-medium border-[#16599c]'
                                        : 'text-slate-500 hover:text-slate-900 border-transparent'
                                }`}
                            >
                                {item.label}
                            </a>
                        );
                    })}
                </nav>
            </div>
        </div>
    );
}

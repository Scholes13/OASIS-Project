import type { ArticleBlock } from '../data/types';
import { Info, AlertTriangle, Lightbulb } from 'lucide-react';

type Lang = 'id' | 'en';

interface ArticleRendererProps {
    blocks: ArticleBlock[];
    lang?: Lang;
}

/**
 * Renders an array of ArticleBlock objects into styled JSX.
 *
 * When `lang` is provided, the container receives a `data-lang` attribute.
 * Bilingual content uses `.lang-id` / `.lang-en` spans that are shown/hidden
 * via global CSS rules targeting `[data-lang="id"]` and `[data-lang="en"]`.
 *
 * To add a new block type:
 * 1. Define the type in `data/types.ts`
 * 2. Add a case here in the switch statement
 */
export default function ArticleRenderer({ blocks, lang }: ArticleRendererProps) {
    return (
        <div className="prose prose-slate max-w-none text-[15px]" data-lang={lang ?? 'id'}>
            {blocks.map((block, i) => (
                <BlockRenderer key={i} block={block} />
            ))}
        </div>
    );
}

function BlockRenderer({ block }: { block: ArticleBlock }) {
    switch (block.type) {
        case 'paragraph':
            return (
                <p
                    id={block.id}
                    className="text-slate-600 leading-relaxed mb-8 text-base"
                    dangerouslySetInnerHTML={{ __html: block.html }}
                />
            );

        case 'heading':
            if (block.level === 2) {
                return (
                    <h2 id={block.id} className="text-xl font-bold text-slate-900 mb-5 mt-10 tracking-tight">
                        {block.text}
                    </h2>
                );
            }
            return (
                <h3 id={block.id} className="text-lg font-bold text-slate-900 mb-4 mt-8 tracking-tight">
                    {block.text}
                </h3>
            );

        case 'ordered-list':
            return (
                <div className="mb-10">
                    {block.intro && <p className="text-slate-600 mb-5">{block.intro}</p>}
                    <ol className="list-decimal list-outside text-slate-600 space-y-3.5 ml-5 marker:text-slate-500">
                        {block.items.map((item, i) => (
                            <li key={i} className="pl-2" dangerouslySetInnerHTML={{ __html: item }} />
                        ))}
                    </ol>
                </div>
            );

        case 'unordered-list':
            return (
                <div className="mb-10">
                    {block.intro && <p className="text-slate-600 mb-5">{block.intro}</p>}
                    <ul className="list-disc list-outside text-slate-600 space-y-2.5 ml-5 marker:text-slate-400">
                        {block.items.map((item, i) => (
                            <li key={i} className="pl-2" dangerouslySetInnerHTML={{ __html: item }} />
                        ))}
                    </ul>
                </div>
            );

        case 'callout':
            return <Callout variant={block.variant} title={block.title} body={block.body} />;

        case 'step-list':
            return (
                <div className="mb-10">
                    {block.intro && <p className="text-slate-600 mb-5">{block.intro}</p>}
                    <div className="space-y-4">
                        {block.steps.map((step, i) => (
                            <div key={i} className="bg-white border border-gray-200 rounded-lg p-4">
                                <div className="flex items-start">
                                    <span className="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-primary text-white rounded-full text-sm font-bold mr-4">
                                        {i + 1}
                                    </span>
                                    <div className="flex-1">
                                        <h4 className="font-semibold text-gray-900 mb-2">{step.title}</h4>
                                        <p
                                            className="text-slate-600 text-sm"
                                            dangerouslySetInnerHTML={{ __html: step.body }}
                                        />
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            );

        case 'status-list':
            return (
                <div className="mb-10">
                    {block.intro && <p className="text-slate-600 mb-5">{block.intro}</p>}
                    <div className="space-y-2">
                        {block.items.map((item, i) => (
                            <StatusBadge key={i} color={item.color} label={item.label} description={item.description} />
                        ))}
                    </div>
                </div>
            );

        case 'faq':
            return (
                <div className="space-y-8" id={block.id}>
                    {block.items.map((item, i) => (
                        <div key={i}>
                            <h4 className="font-semibold text-slate-900 text-lg">
                                {i + 1}. {item.question}
                            </h4>
                            <p className="text-slate-600 mt-2" dangerouslySetInnerHTML={{ __html: item.answer }} />
                        </div>
                    ))}
                </div>
            );

        default:
            return null;
    }
}

function Callout({ variant, title, body }: { variant: 'info' | 'warning' | 'tip'; title: string; body: string }) {
    const config = {
        info: { Icon: Info, border: 'border-[#16599c]', bg: 'bg-[#f8fafc]', titleColor: 'text-[#1e3a8a]', bodyColor: 'text-[#1e40af]', iconColor: 'text-[#16599c]' },
        warning: { Icon: AlertTriangle, border: 'border-amber-500', bg: 'bg-amber-50', titleColor: 'text-amber-900', bodyColor: 'text-amber-800', iconColor: 'text-amber-500' },
        tip: { Icon: Lightbulb, border: 'border-[#16599c]', bg: 'bg-[#f8fafc]', titleColor: 'text-[#1e3a8a]', bodyColor: 'text-[#1e40af]', iconColor: 'text-[#16599c]' },
    };

    const c = config[variant];

    return (
        <div className={`${c.bg} border-l-[3px] ${c.border} rounded-r-lg p-5 mb-10 mt-6`}>
            <div className="flex items-start">
                <c.Icon className={`w-5 h-5 ${c.iconColor} mr-3 mt-0.5 shrink-0`} />
                <div>
                    <h4 className={`font-semibold ${c.titleColor} mb-1 text-[15px]`}>{title}</h4>
                    <p className={`${c.bodyColor} text-sm leading-relaxed`} dangerouslySetInnerHTML={{ __html: body }} />
                </div>
            </div>
        </div>
    );
}

function StatusBadge({
    color,
    label,
    description,
}: {
    color: 'gray' | 'blue' | 'amber' | 'emerald' | 'red';
    label: string;
    description: string;
}) {
    const colorMap = {
        gray: { bg: 'bg-gray-100 text-gray-700', dot: 'bg-gray-400' },
        blue: { bg: 'bg-blue-100 text-blue-700', dot: 'bg-blue-500' },
        amber: { bg: 'bg-amber-100 text-amber-700', dot: 'bg-amber-500' },
        emerald: { bg: 'bg-emerald-100 text-emerald-700', dot: 'bg-emerald-500' },
        red: { bg: 'bg-red-100 text-red-700', dot: 'bg-red-500' },
    };

    const c = colorMap[color];
    const content = `<strong>${label}</strong> - ${description}`;

    return (
        <div className={`flex items-center space-x-2 ${c.bg} rounded-lg px-3 py-2`}>
            <span className={`w-2 h-2 ${c.dot} rounded-full`} />
            <span className="text-sm" dangerouslySetInnerHTML={{ __html: content }} />
        </div>
    );
}

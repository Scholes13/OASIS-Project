import { FlaskConical } from 'lucide-react';

interface StagingBannerProps {
    compact?: boolean;
}

export default function StagingBanner({ compact = false }: StagingBannerProps) {
    return (
        <div
            className={
                compact
                    ? 'rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-amber-950 shadow-sm'
                    : 'border-b border-amber-200 bg-amber-50 px-4 py-2 text-amber-950 shadow-sm'
            }
            role="status"
            aria-live="polite"
        >
            <div className="mx-auto flex max-w-7xl items-center justify-center gap-2 text-center text-xs font-semibold sm:text-sm">
                <FlaskConical className="h-4 w-4 shrink-0 text-amber-600" />
                <span>STAGING / TEST VERSION</span>
                <span className="hidden h-1 w-1 rounded-full bg-amber-500 sm:inline-block" aria-hidden="true" />
                <span>Password semua user: werkudara88.</span>
            </div>
        </div>
    );
}

import * as React from 'react';

export function usePagination<T>(items: T[], perPage = 10) {
    const [page, setPage] = React.useState(1);
    const totalPages = Math.max(1, Math.ceil(items.length / perPage));

    React.useEffect(() => {
        setPage((current) => Math.min(current, totalPages));
    }, [totalPages]);

    const pagedItems = React.useMemo(() => {
        const start = (page - 1) * perPage;
        return items.slice(start, start + perPage);
    }, [items, page, perPage]);

    const canPrev = page > 1;
    const canNext = page < totalPages;

    return {
        page,
        setPage,
        totalPages,
        pagedItems,
        canPrev,
        canNext,
        goNext: () => setPage((current) => Math.min(current + 1, totalPages)),
        goPrev: () => setPage((current) => Math.max(current - 1, 1)),
    };
}

import * as React from 'react';

export function useDebouncedSearch(initialValue = '', delay = 300): { value: string; debouncedValue: string; setValue: (v: string) => void } {
    const [value, setValue] = React.useState(initialValue);
    const [debouncedValue, setDebouncedValue] = React.useState(initialValue);

    React.useEffect(() => {
        const timer = window.setTimeout(() => setDebouncedValue(value), delay);

        return () => window.clearTimeout(timer);
    }, [delay, value]);

    return { value, debouncedValue, setValue };
}

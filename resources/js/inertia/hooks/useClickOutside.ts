import * as React from 'react';

export function useClickOutside<T extends HTMLElement>(ref: React.RefObject<T | null>, handler: () => void): void {
    React.useEffect(() => {
        const listener = (event: MouseEvent | TouchEvent) => {
            const element = ref.current;
            const target = event.target;

            if (!element || !(target instanceof Node) || element.contains(target)) return;

            handler();
        };

        document.addEventListener('mousedown', listener);
        document.addEventListener('touchstart', listener);

        return () => {
            document.removeEventListener('mousedown', listener);
            document.removeEventListener('touchstart', listener);
        };
    }, [handler, ref]);
}

export function normalizeBodyBottomSpacing(body: HTMLElement | null = document.body): void {
    if (!body) {
        return;
    }

    body.style.setProperty('margin-bottom', '0px', 'important');
    body.style.setProperty('padding-bottom', '0px', 'important');
}

export function installBodySpacingGuard(): void {
    const run = () => normalizeBodyBottomSpacing(document.body);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run, { once: true });
    } else {
        run();
    }

    window.addEventListener('load', run);

    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                run();
            }
        }
    });

    const startObserver = () => {
        if (document.body) {
            observer.observe(document.body, { attributes: true, attributeFilter: ['style'] });
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startObserver, { once: true });
    } else {
        startObserver();
    }
}

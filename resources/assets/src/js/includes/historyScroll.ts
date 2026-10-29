import {jumpTo} from './sticky';

// htmx re-fetches a page on back/forward, and the browser restores the scroll only once htmx's navigation
// handler settles — after the view transition, so the restored page first shows at the offset of the one left
// (monorepo issue #733). The offset is kept in the entry's state instead and set while the new page is captured.
let pending: number | undefined;
let timeout = 0;

export const saveScroll = (): void => {
    window.clearTimeout(timeout);

    // While a restore waits for its page, the entry is already the one restored to and the page the one left.
    if (pending === undefined && history.state?.htmx) {
        history.replaceState({...history.state, scrollY: window.scrollY}, '');
    }
};

export const isRestore = (ctx: {request?: {headers?: Record<string, string>}} | undefined): boolean =>
    ctx?.request?.headers?.['HX-History-Restore-Request'] === 'true';

export const prepareRestore = (): void => {
    const scrollY = history.state?.scrollY;
    pending = typeof scrollY === 'number' ? scrollY : 0;
};

export const restoreScroll = (): void => {
    if (pending !== undefined) {
        jumpTo(pending);
        pending = undefined;
    }
};

export const cancelRestore = (): void => {
    pending = undefined;
};

export default (): void => {
    history.scrollRestoration = 'manual';

    // A reload or a traversal to a full page load is no longer restored by the browser either.
    const [entry] = performance.getEntriesByType('navigation') as PerformanceNavigationTiming[];
    const scrollY = history.state?.scrollY;

    if (entry && entry.type !== 'navigate' && typeof scrollY === 'number') {
        window.scrollTo({top: scrollY, behavior: 'instant'});
    }

    document.addEventListener('scroll', () => {
        window.clearTimeout(timeout);
        timeout = window.setTimeout(saveScroll, 100);
    }, {passive: true});

    window.addEventListener('pagehide', saveScroll);
};

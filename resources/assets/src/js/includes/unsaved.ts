/**
 * A long form lost to a stray click: htmx swaps `#wrap` on every navigation without asking. A form carrying
 * `data-unsaved` (the question, worded by the server) is dirty after its first input or change, or from the start
 * when the server rendered it with unsaved input (`data-dirty`: a failed save, a form reload). Leaving the page —
 * an htmx navigation of `#wrap` or the browser's own — asks while one is; submitting the form clears it.
 */
const dirty = new Set<HTMLFormElement>();

export const trackUnsavedForm = ($form: HTMLFormElement): void => {
    const mark = () => dirty.add($form);

    if ($form.dataset.dirty !== undefined) {
        mark();
    }

    $form.addEventListener('input', mark);
    $form.addEventListener('change', mark);
    $form.addEventListener('submit', () => dirty.delete($form));
};

const getDirtyForm = (): HTMLFormElement | undefined => {
    for (const $form of dirty) {
        if (!$form.isConnected) {
            dirty.delete($form);
        }
    }

    return dirty.values().next().value;
};

interface RequestContext {
    sourceElement?: Element | null;
    target?: Element | null;
    request?: {method?: string};
}

/**
 * `false` when the user chose to stay. Only a navigation leaves: a request from within the dirty form (its save, a
 * type change reloading it) or one swapping less than `#wrap` (a grid's pager) keeps the form on the page.
 */
export const confirmLeaving = (ctx?: RequestContext, confirm: (message: string) => boolean = window.confirm): boolean => {
    const $form = getDirtyForm();

    if (!$form || !ctx || ctx.request?.method !== 'GET') {
        return true;
    }

    const isNavigation = ctx.target?.id === 'wrap' || ctx.target === document.body;

    if (!isNavigation || (ctx.sourceElement && $form.contains(ctx.sourceElement))) {
        return true;
    }

    if (confirm($form.dataset.unsaved || '')) {
        dirty.clear();
        return true;
    }

    return false;
};

export const hasUnsavedChanges = (): boolean => getDirtyForm() !== undefined;

export const resetUnsavedForms = (): void => dirty.clear();

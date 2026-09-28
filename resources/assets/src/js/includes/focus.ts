/**
 * After a save the form failed to pass, the first field it rejected takes the focus, so a keyboard or screen reader
 * user lands on the error instead of at the top of a page that looks unchanged. The swap replaced the target, so
 * the page is asked rather than the element the request was issued from.
 */
export const focusFirstInvalid = (method?: string): void => {
    if (method !== 'POST') {
        return;
    }

    document.querySelector<HTMLElement>('#wrap [aria-invalid="true"]')?.focus();
};

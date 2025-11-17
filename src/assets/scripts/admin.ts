import htmx from "htmx.org"

import collapse from "./includes/collapse";
import dropdown from "./includes/dropdown";
import filter from "./includes/filter";
import modal from "./includes/modals";
import tooltip from "./includes/tooltips";
import {toggleTargetsOnChange, updateTargetsOnChange} from "./includes/forms";

import './components/ActiveForm';
import './components/FlashAlert';

const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).getAttribute('content') as string;


htmx.on('htmx:configRequest', (event: Event) => {
    (event as CustomEvent).detail.headers['X-CSRF-Token'] = csrfToken;
});

htmx.onLoad(($container) => {
    const queryAll = (selector: string, method: Function) => {
        ($container as HTMLElement).querySelectorAll(selector).forEach(($el: Element) => method($el));
    };

    queryAll('[data-collapse]', collapse);
    queryAll('[data-dropdown]', dropdown);
    queryAll('[data-filter]', filter);
    queryAll('[data-form-target]', updateTargetsOnChange);
    queryAll('[data-form-toggle]', toggleTargetsOnChange);
    queryAll('[data-modal]', modal);
    queryAll('[data-tooltip]', tooltip);
});


htmx.on('htmx:responseError', (event: Event) => {
    const detail = (event as CustomEvent).detail;

    if (!detail.xhr.ok) {
        alert(detail.xhr.statusText);
    }
});

htmx.config.historyCacheSize = 0;
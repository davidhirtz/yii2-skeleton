import htmx from 'htmx.org';
import type {Editor} from 'tinymce';

import {createModal} from './modals';

export interface LinkValues {
    href: string;
    text: string;
    title: string;
    target: string;
    class: string;
}

interface LinkClass {
    title: string;
    value: string;
}

/**
 * Opens the page at `url` (`Widgets\Forms\LinkDialog`) in a modal, filled with `values`, and answers what its form held
 * on submit, `null` when closed otherwise. Nothing posts the form.
 */
export const openLinkDialog = (url: string, title: string, values: LinkValues, classes: LinkClass[] = []): Promise<LinkValues | null> => new Promise((resolve) => {
    const $dialog = createModal(title, '<div id="link-dialog"></div>');
    const $body = $dialog.querySelector<HTMLElement>('.modal-body')!;
    let result: LinkValues | null = null;

    $body.setAttribute('hx-push-url:inherited', 'false');
    $body.setAttribute('hx-select-oob:inherited', '');

    $body.addEventListener('submit', (event: Event) => {
        const $form = (event.target as Element).closest<HTMLFormElement>('form[data-link-dialog]');

        if (!$form) {
            return;
        }

        event.preventDefault();

        const data = new FormData($form);
        const get = (name: string) => String(data.get(name) ?? '').trim();

        result = {href: get('href'), text: get('text'), title: get('title'), target: get('target'), class: get('class')};
        $dialog.close();
    });

    // The `hx-head` extension would merge the dialog page's `<head>` into the document.
    const keepHead = (event: Event) => {
        if ($dialog.contains((event as CustomEvent).detail?.ctx?.sourceElement ?? null)) {
            event.preventDefault();
        }
    };

    document.body.addEventListener('htmx:head:before:merge', keepHead);

    $body.addEventListener('htmx:after:swap', () => $body.querySelector<HTMLElement>('[autofocus]')?.focus());

    $dialog.addEventListener('close', () => {
        document.body.removeEventListener('htmx:head:before:merge', keepHead);
        resolve(result);
    });

    const params = new URLSearchParams({
        ...values,
        classes: JSON.stringify(Object.fromEntries(classes.map(({value, title}) => [value, title]))),
    });

    void htmx.ajax('GET', `${url}${url.includes('?') ? '&' : '?'}${params}`, {
        source: $body,
        target: '#link-dialog',
        select: '#link-dialog',
        swap: 'outerHTML transition:false ignoreTitle:true',
        push: false,
    });
});

const getAttributes = (values: LinkValues): Record<string, string | null> => ({
    href: values.href,
    title: values.title || null,
    target: values.target || null,
    class: values.class || null,
});

const apply = (editor: Editor, $anchor: HTMLAnchorElement | null, values: LinkValues, text: string) => {
    if (!values.href) {
        if ($anchor) {
            editor.execCommand('unlink');
        }

        return;
    }

    const attributes = getAttributes(values);

    if ($anchor) {
        editor.dom.setAttribs($anchor, attributes);

        // Rewriting the text drops the formatting inside the link, so only a changed text is written.
        if (values.text && values.text !== text) {
            $anchor.textContent = values.text;
        }

        return;
    }

    if (editor.selection.isCollapsed() || (values.text && values.text !== text)) {
        const defined = Object.fromEntries(Object.entries(attributes).filter(([, value]) => value !== null)) as Record<string, string>;
        editor.insertContent(editor.dom.createHTML('a', defined, editor.dom.encode(values.text || values.href)));
        return;
    }

    editor.execCommand('mceInsertLink', false, attributes);
};

/**
 * Stands in for the link plugin: its buttons, its command and its shortcut open {@see openLinkDialog}.
 */
export const registerLinkDialog = (editor: Editor, url: string, title: string, classes: LinkClass[] = []) => {
    const getAnchor = () => editor.dom.getParent(editor.selection.getNode(), 'a[href]') as HTMLAnchorElement | null;

    const open = async () => {
        const $anchor = getAnchor();

        if ($anchor) {
            editor.selection.select($anchor);
        }

        const text = $anchor ? $anchor.textContent ?? '' : editor.selection.getContent({format: 'text'});
        const bookmark = editor.selection.getBookmark(2, true);

        const values = await openLinkDialog(url, title, {
            href: $anchor?.getAttribute('href') ?? '',
            text,
            title: $anchor?.getAttribute('title') ?? '',
            target: $anchor?.getAttribute('target') ?? '',
            class: $anchor?.getAttribute('class') ?? '',
        }, classes);

        editor.focus();
        editor.selection.moveToBookmark(bookmark);

        if (values) {
            editor.undoManager.transact(() => apply(editor, $anchor, values, text));
            editor.nodeChanged();
        }
    };

    const onNodeChange = (callback: (isLink: boolean) => void) => {
        const handler = () => callback(getAnchor() !== null);
        editor.on('NodeChange', handler);
        return () => editor.off('NodeChange', handler);
    };

    editor.addCommand('mceLink', () => void open());
    editor.addShortcut('Meta+K', '', () => void open());

    editor.ui.registry.addToggleButton('link', {
        icon: 'link',
        tooltip: 'Insert/edit link',
        onAction: () => void open(),
        onSetup: (api) => onNodeChange((isLink) => api.setActive(isLink)),
    });

    editor.ui.registry.addButton('unlink', {
        icon: 'unlink',
        tooltip: 'Remove link',
        onAction: () => editor.execCommand('unlink'),
        onSetup: (api) => onNodeChange((isLink) => api.setEnabled(isLink)),
    });
};

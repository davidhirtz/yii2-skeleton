import {beforeEach, describe, expect, it, vi} from 'vitest';

const editors = vi.hoisted(() => new Map<string, {removed: boolean}>());

vi.mock('tinymce', () => ({
    default: {
        init: async ({selector}: {selector: string}) => {
            if (!document.querySelector(selector)) {
                return [];
            }

            const state = {removed: false};
            editors.set(selector, state);

            return [{on: () => {}, save: () => {}, remove: () => state.removed = true}];
        },
    },
}));

vi.mock('tinymce/icons/default/icons.min.js', () => ({}));
vi.mock('tinymce/themes/silver/theme.min.js', () => ({}));
vi.mock('tinymce/models/dom/model.min.js', () => ({}));
vi.mock('tinymce/plugins/code', () => ({}));
vi.mock('tinymce/plugins/fullscreen', () => ({}));
vi.mock('tinymce/plugins/link', () => ({}));
vi.mock('tinymce/plugins/lists', () => ({}));
vi.mock('tinymce/plugins/table', () => ({}));

await import('../../../src/js/components/TinyMceEditor');

const wait = () => new Promise((resolve) => setTimeout(resolve, 20));
const live = () => [...editors.values()].filter((state) => !state.removed).length;

describe('tinymce-editor', () => {
    let $list: HTMLElement;
    let $editor: HTMLElement;

    beforeEach(() => {
        editors.clear();
        document.body.innerHTML = '<div><div></div></div>';
        $list = document.body.firstElementChild as HTMLElement;

        // Built before it connects, as a swap inserts it: the parser connects an element before its children.
        $editor = document.createElement('tinymce-editor');
        $editor.dataset.config = '{}';
        $editor.append(document.createElement('textarea'));
        $list.append($editor);
    });

    it('keeps one editor through two moves and removes it with the element', async () => {
        await wait();
        expect(live()).toBe(1);

        $list.prepend($editor);
        await wait();
        $list.append($editor);
        await wait();
        expect(live()).toBe(1);

        $editor.remove();
        await wait();
        expect(live()).toBe(0);
    });

    it('builds one editor when moved before the first one is built', async () => {
        $list.prepend($editor);
        $list.append($editor);
        await wait();
        expect(editors.size).toBe(1);
        expect(live()).toBe(1);

        $editor.remove();
        await wait();
        expect(live()).toBe(0);
    });
});

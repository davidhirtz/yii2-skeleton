import {beforeEach, describe, expect, it, vi} from 'vitest';

const editors = vi.hoisted(() => new Map<string, {removed: boolean}>());
const configs = vi.hoisted(() => [] as Record<string, unknown>[]);

vi.mock('tinymce', () => ({
    default: {
        init: async (config: Record<string, unknown> & {selector: string}) => {
            const {selector} = config;
            configs.push(config);

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
vi.mock('htmx.org', () => ({default: {ajax: async () => {}}}));

await import('../../../src/js/components/TinyMceEditor');

const wait = () => new Promise((resolve) => setTimeout(resolve, 20));
const live = () => [...editors.values()].filter((state) => !state.removed).length;

describe('tinymce-editor', () => {
    let $list: HTMLElement;
    let $editor: HTMLElement;

    beforeEach(() => {
        editors.clear();
        configs.length = 0;
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

    it('hands the link dialog to its own setup, never to TinyMCE', async () => {
        $editor.remove();
        await wait();
        configs.length = 0;

        const $linked = document.createElement('tinymce-editor');
        $linked.dataset.config = JSON.stringify({linkDialogUrl: '/admin/link', linkDialogTitle: 'Link'});
        $linked.append(document.createElement('textarea'));
        $list.append($linked);
        await wait();

        const config = configs[0]! as Record<string, unknown> & {setup: (editor: unknown) => void};
        const registry = {addToggleButton: vi.fn(), addButton: vi.fn()};
        const editor = {on: vi.fn(), addCommand: vi.fn(), addShortcut: vi.fn(), ui: {registry}};

        config.setup(editor);

        expect(config.linkDialogUrl).toBeUndefined();
        expect(registry.addToggleButton).toHaveBeenCalledWith('link', expect.any(Object));
        expect(registry.addButton).toHaveBeenCalledWith('unlink', expect.any(Object));
        expect(editor.addCommand).toHaveBeenCalledWith('mceLink', expect.any(Function));
    });

    it('registers no link buttons without a dialog URL', async () => {
        await wait();

        const config = configs[0]! as Record<string, unknown> & {setup: (editor: unknown) => void};
        const registry = {addToggleButton: vi.fn(), addButton: vi.fn()};

        config.setup({on: vi.fn(), addCommand: vi.fn(), addShortcut: vi.fn(), ui: {registry}});

        expect(registry.addToggleButton).not.toHaveBeenCalled();
    });
});

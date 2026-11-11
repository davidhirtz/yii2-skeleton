import {beforeEach, describe, expect, it, vi} from 'vitest';
import '../../../src/js/includes/modifierClick';

describe('modifierClick', () => {
    let $link: HTMLAnchorElement;
    let handler: ReturnType<typeof vi.fn<(event: Event) => void>>;

    const click = ($target: Element, init: MouseEventInit = {}): MouseEvent => {
        const event = new MouseEvent('click', {bubbles: true, cancelable: true, button: 0, ...init});
        $target.dispatchEvent(event);

        return event;
    };

    beforeEach(() => {
        document.body.innerHTML = '<a href="/admin/entry/index"><span>Entries</span></a><a href="#top">Top</a>';
        $link = document.querySelector('a')!;
        handler = vi.fn<(event: Event) => void>((event) => event.preventDefault());
        document.querySelectorAll('a').forEach(($a) => $a.addEventListener('click', handler));
    });

    it('leaves a plain click to htmx', () => {
        click($link.querySelector('span')!);

        expect(handler).toHaveBeenCalledOnce();
    });

    it.each(['metaKey', 'ctrlKey', 'shiftKey', 'altKey'])('leaves a click with %s to the browser', (key) => {
        const event = click($link.querySelector('span')!, {[key]: true});

        expect(handler).not.toHaveBeenCalled();
        expect(event.defaultPrevented).toBe(false);
    });

    it('leaves a modified click on a fragment link alone', () => {
        click(document.querySelector('a[href="#top"]')!, {metaKey: true});

        expect(handler).toHaveBeenCalledOnce();
    });
});

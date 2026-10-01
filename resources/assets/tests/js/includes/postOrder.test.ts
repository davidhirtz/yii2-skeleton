import {afterEach, describe, expect, it, vi} from 'vitest';

// happy-dom has no XPath, which htmx compiles its `hx-on` query with when it loads.
vi.stubGlobal('XPathEvaluator', class {
    createExpression() {
        return {evaluate: () => ({iterateNext: () => null})};
    }
});

const {default: postOrder} = await import('../../../src/js/includes/postOrder');

const tick = () => new Promise((resolve) => setTimeout(resolve, 0));

describe('postOrder', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
        document.body.innerHTML = '';
    });

    it('sends the last order of moves made within one round trip', async () => {
        const bodies: string[] = [];
        const pending: Array<() => void> = [];

        vi.stubGlobal('fetch', vi.fn((_url: string, request: {body: URLSearchParams}) => {
            bodies.push(decodeURIComponent(request.body.toString()));
            return new Promise((resolve) => pending.push(() => resolve(new Response('', {status: 200}))));
        }));

        document.body.innerHTML = '<table><tbody data-sort-url="/admin/entry/order">'
            + '<tr id="entry-1"></tr><tr id="entry-2"></tr><tr id="entry-3"></tr></tbody></table>';

        const $list = document.querySelector('tbody')!;
        const move = () => $list.prepend($list.lastElementChild!);

        const requests = [1, 2, 3].map(() => {
            move();
            return postOrder($list);
        });

        while (pending.length || bodies.length < 2) {
            await tick();
            pending.shift()?.();
        }

        await Promise.all(requests);

        // Order 1 is in flight, order 2 is replaced in the queue by order 3.
        expect(bodies.at(-1)).toBe('entry[0]=1&entry[1]=2&entry[2]=3');
    });
});

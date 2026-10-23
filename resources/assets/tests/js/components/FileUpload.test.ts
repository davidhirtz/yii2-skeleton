import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';

// happy-dom has no XPath, which htmx compiles its `hx-on` query with when it loads.
vi.stubGlobal('XPathEvaluator', class {
    createExpression() {
        return {evaluate: () => ({iterateNext: () => null})};
    }
});

await import('../../../src/js/components/FileUpload');

const tick = () => new Promise((resolve) => setTimeout(resolve, 0));

describe('file-upload', () => {
    let $input: HTMLInputElement;
    let values: string[];

    beforeEach(() => {
        document.body.innerHTML = '<div id="flashes"></div>'
            + '<template id="network-error-flash"><div class="alert">Not saved</div></template>'
            + '<template id="error-flash"><flash-alert><div class="alert"><div class="alert-content"></div></div></flash-alert></template>'
            + '<div id="wrap" hx-headers:inherited=\'{"X-CSRF-Token":"token"}\'></div>';

        const $upload = document.createElement('file-upload');
        $upload.dataset.url = '/admin/upload/create';
        $upload.innerHTML = '<button type="button"></button><input type="file" name="upload">';
        $input = $upload.querySelector('input')!;

        values = [];
        Object.defineProperty($input, 'value', {set: (value: string) => values.push(value)});
        Object.defineProperty($input, 'files', {value: [new File(['content'], 'notes.txt')]});

        document.getElementById('wrap')!.append($upload);
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    const upload = async () => {
        $input.dispatchEvent(new Event('change'));

        for (let i = 0; i < 10; i++) {
            await tick();
        }
    };

    it('flashes the message the server sends in its header, which HTTP/2 needs', async () => {
        vi.stubGlobal('fetch', vi.fn(async () => new Response('', {
            status: 400,
            headers: {'X-Upload-Error': encodeURIComponent('Datei „Å😀.exe“ ist nicht erlaubt')},
        })));

        await upload();

        expect(document.querySelector('#flashes .alert-content')?.textContent).toBe('Datei „Å😀.exe“ ist nicht erlaubt');
        expect(values).toEqual(['']);
        expect(document.body.inert).toBe(false);
    });

    it('flashes a request that got no response', async () => {
        vi.stubGlobal('fetch', vi.fn(async () => {
            throw new TypeError('Failed to fetch');
        }));

        await upload();

        expect(document.querySelector('#flashes .alert')?.textContent).toBe('Not saved');
        expect(values).toEqual(['']);
        expect(document.body.inert).toBe(false);
    });

    it('appends what a listener prepares to the request completing the file', async () => {
        const bodies: FormData[] = [];

        vi.stubGlobal('fetch', vi.fn(async (_url: string, init: RequestInit) => {
            bodies.push(init.body as FormData);
            return new Response('', {status: 204});
        }));

        const listener = (event: Event) => {
            const detail = (event as CustomEvent).detail;
            detail.fields.push(Promise.resolve(['preview', new Blob(['frame'], {type: 'image/webp'})]));
            detail.fields.push(Promise.resolve(null));
        };

        document.addEventListener('file-upload:prepare', listener);

        try {
            await upload();
        } finally {
            document.removeEventListener('file-upload:prepare', listener);
        }

        expect(bodies).toHaveLength(1);
        expect(bodies[0].get('upload')).toBeInstanceOf(File);
        expect(bodies[0].get('preview')).toBeInstanceOf(Blob);
    });
});

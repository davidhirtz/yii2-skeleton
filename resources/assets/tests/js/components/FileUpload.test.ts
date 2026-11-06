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

    describe('drop zone', () => {
        let $zone: HTMLElement;
        let $row: HTMLElement;

        beforeEach(() => {
            document.body.querySelector('file-upload')!.remove();

            const $upload = document.createElement('file-upload');
            $upload.dataset.url = '/admin/upload/create';
            $upload.dataset.target = '#grid';
            $upload.dataset.dropZone = '#grid';
            $upload.innerHTML = '<button type="button"></button><input type="file" name="upload" multiple>';
            Object.defineProperty($upload.querySelector('input')!, 'value', {set: (value: string) => values.push(value)});

            document.getElementById('wrap')!.append($upload, Object.assign(document.createElement('div'), {
                id: 'grid',
                innerHTML: '<div class="row"></div>',
            }));

            $zone = document.getElementById('grid')!;
            $row = $zone.querySelector('.row')!;
        });

        const drag = ($target: Element, type: string, files: File[], types = ['Files']) => {
            const event = new Event(type, {bubbles: true, cancelable: true});

            Object.defineProperty(event, 'dataTransfer', {
                value: {
                    types,
                    dropEffect: 'none',
                    items: files.map(file => ({kind: 'file', getAsFile: () => file})),
                },
            });

            $target.dispatchEvent(event);
            return event;
        };

        it('marks the zone while files are dragged over it, not a sortable row', () => {
            drag($row, 'dragenter', [], ['text/plain']);
            expect($zone.classList.contains('is-drop-target')).toBe(false);

            drag($zone, 'dragenter', []);
            drag($row, 'dragenter', []);
            drag($zone, 'dragleave', []);
            expect($zone.classList.contains('is-drop-target')).toBe(true);
            expect(drag($row, 'dragover', []).defaultPrevented).toBe(true);

            drag($row, 'dragleave', []);
            expect($zone.classList.contains('is-drop-target')).toBe(false);
        });

        it('leaves a drop outside the zone to the browser', () => {
            expect(drag(document.getElementById('flashes')!, 'dragover', []).defaultPrevented).toBe(false);
        });

        it('uploads every file dropped onto the zone', async () => {
            const bodies: FormData[] = [];

            vi.stubGlobal('fetch', vi.fn(async (_url: string, init: RequestInit) => {
                bodies.push(init.body as FormData);
                return new Response('', {status: 204});
            }));

            drag($row, 'dragenter', []);

            const event = drag($row, 'drop', [new File(['a'], 'Å😀.jpg'), new File(['b'], 'Mu\u{308}ller.png')]);

            for (let i = 0; i < 10; i++) {
                await tick();
            }

            expect(event.defaultPrevented).toBe(true);
            expect($zone.classList.contains('is-drop-target')).toBe(false);
            expect(bodies.map(body => (body.get('upload') as File).name)).toEqual(['Å😀.jpg', 'Mu\u{308}ller.png']);
        });
    });
});

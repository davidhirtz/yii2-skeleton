import htmx from 'htmx.org';

const callbacks: Array<($root: HTMLElement) => void> = [];
let $processed: HTMLElement | null = null;

htmx.onLoad(($root: HTMLElement) => {
    $processed = $root;
    callbacks.forEach((callback) => callback($root));
});

// htmx runs its first pass on a timer of its own, which fires between two deferred module scripts — so a page
// loading more than one entry point would leave everything the later one registers without the document it
// loaded into. This module lives in the chunk htmx itself is in, so it registers before that first pass and
// replays it for whoever arrives after.
export default ($callback: ($root: HTMLElement) => void) => {
    callbacks.push($callback);

    if ($processed) {
        $callback($processed);
    }
};

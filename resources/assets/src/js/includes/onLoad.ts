import htmx from 'htmx.org';

const callbacks: Array<($root: HTMLElement) => void> = [];
let $processed: HTMLElement | null = null;

// A callback's error is reported rather than thrown, so the ones registered after it still run (monorepo issue #559).
const run = (callback: ($root: HTMLElement) => void, $root: HTMLElement) => {
    try {
        callback($root);
    } catch (error) {
        reportError(error);
    }
};

// htmx hands its callback an `Element`, and everything registered here wants the `HTMLElement` it is in
// practice — so the narrowing happens once, here, rather than as a cast in every caller.
//
// htmx runs its first pass on a timer of its own, which fires between two deferred module scripts — so a page
// loading more than one entry point would leave everything the later one registers without the document it
// loaded into. This module lives in the chunk htmx itself is in, so it registers before that first pass and
// replays it for whoever arrives after.
htmx.onLoad(($root) => {
    if (!($root instanceof HTMLElement)) {
        return;
    }

    $processed = $root;
    callbacks.forEach((callback) => run(callback, $root));
});

export default ($callback: ($root: HTMLElement) => void) => {
    callbacks.push($callback);

    if ($processed) {
        run($callback, $processed);
    }
};

import {autoUpdate, computePosition, flip, offset, shift} from "@floating-ui/dom";

import {teardownOnDisconnect} from "./teardown";

const position = ($anchor: HTMLElement, $popover: HTMLElement) => computePosition($anchor, $popover, {
    placement: 'bottom-start',
    middleware: [offset(4), flip(), shift({padding: 8})],
}).then(({x, y}) => {
    Object.assign($popover.style, {
        left: `${x}px`,
        minWidth: `${$anchor.offsetWidth}px`,
        top: `${y}px`,
    });
});

// Opens a manual popover under the element it belongs to and keeps it there. The returned handle closes it again
// and is also registered against the popover being removed from the DOM, which fires no `toggle` event of its own.
export const openUnder = ($anchor: HTMLElement, $popover: HTMLElement, cleanup?: () => void) => {
    if ($popover.matches(':popover-open')) {
        position($anchor, $popover);
        return null;
    }

    $popover.showPopover();

    const stop = autoUpdate($anchor, $popover, () => position($anchor, $popover));

    return teardownOnDisconnect($popover, () => {
        cleanup?.();
        stop();
    });
}

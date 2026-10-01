import {arrow, computePosition, flip, offset, shift} from "@floating-ui/dom";

interface HotspotEvent extends CustomEvent {
    detail: {
        hotspots: HTMLElement[];
    }
}

document.addEventListener('tooltip:init', (event) => {
    (event as HotspotEvent).detail.hotspots.forEach($hotspot => {
        initHotspot($hotspot);
    });
});

const initHotspot = ($hotspot: HTMLElement) => {
    const $arrow = document.createElement('div');
    const $tooltip = document.createElement('div');
    const $inner = document.createElement('div');
    const title = $hotspot.title;

    $tooltip.classList.add('tooltip');
    $tooltip.setAttribute('role', 'tooltip');

    // `title` comes back entity-decoded: written as markup, an escaped record name would run as HTML.
    $inner.classList.add('tooltip-inner');
    $inner.textContent = title;

    $arrow.classList.add('tooltip-arrow');
    $tooltip.append($arrow, $inner);

    // The native tooltip would double this one, but the text stays the element's accessible name or description.
    $hotspot.removeAttribute('title');

    if (!$hotspot.hasAttribute('aria-label')) {
        $hotspot.setAttribute($hotspot.textContent?.trim() ? 'aria-description' : 'aria-label', title);
    }

    // A tooltip opened by hovering is dismissed from the keyboard too, wherever the focus is (WCAG 1.4.13).
    const onKeydown = (event: KeyboardEvent) => {
        if (event.key === 'Escape') {
            hide();
        }
    };

    const show = () => {
        $hotspot.after($tooltip);
        document.addEventListener('keydown', onKeydown);

        computePosition($hotspot, $tooltip, {
            placement: 'top',
            middleware: [
                offset(8),
                flip(),
                shift({padding: 5}),
                arrow({element: $arrow}),
            ],
        }).then(({x, y, placement, middlewareData}) => {
            Object.assign($tooltip.style, {
                left: `${x}px`,
                top: `${y}px`,
            });

            const arrow = middlewareData.arrow!;

            const staticSide = {
                top: 'bottom',
                right: 'left',
                bottom: 'top',
                left: 'right',
            }[placement.split('-')[0]] as string;

            Object.assign($arrow.style, {
                left: arrow.x !== null ? `${arrow.x}px` : '',
                top: arrow.y !== null ? `${arrow.y}px` : '',
                right: '',
                bottom: '',
                [staticSide]: '-4px',
            });
        });
    };

    const hide = () => {
        $tooltip.remove();
        document.removeEventListener('keydown', onKeydown);
    };

    $hotspot.addEventListener('mouseenter', show);
    $hotspot.addEventListener('mouseleave', hide);
    $hotspot.addEventListener('focus', show);
    $hotspot.addEventListener('blur', hide);
}

export default initHotspot;

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

    $tooltip.classList.add('tooltip');
    $tooltip.setAttribute('role', 'tooltip');
    $tooltip.innerHTML = `<div class="tooltip-inner">${$hotspot.title}</div>`;

    $arrow.classList.add('tooltip-arrow');
    $tooltip.prepend($arrow);

    $hotspot.removeAttribute('title');

    $hotspot.addEventListener('mouseenter', () => {
        $hotspot.after($tooltip);

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
    });

    $hotspot.addEventListener('mouseleave', () => $tooltip.remove());
}

export default initHotspot;
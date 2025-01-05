import {arrow, computePosition, flip, offset, shift} from "@floating-ui/dom";

export default ($owner: HTMLElement) => {
    const $tooltip = document.createElement('div');
    const $arrow = document.createElement('div');

    $tooltip.classList.add('tooltip');
    $tooltip.setAttribute('role', 'tooltip');
    $tooltip.innerHTML = `<div class="tooltip-inner">${$owner.title}</div>`;

    $arrow.classList.add('tooltip-arrow');
    $tooltip.prepend($arrow);

    $owner.removeAttribute('title');

    $owner.addEventListener('mouseenter', () => {
        $owner.after($tooltip);

        computePosition($owner, $tooltip, {
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

            const {x: arrowX, y: arrowY} = middlewareData.arrow;

            const staticSide = {
                top: 'bottom',
                right: 'left',
                bottom: 'top',
                left: 'right',
            }[placement.split('-')[0]];

            Object.assign($arrow.style, {
                left: arrowX != null ? `${arrowX}px` : '',
                top: arrowY != null ? `${arrowY}px` : '',
                right: '',
                bottom: '',
                [staticSide]: '-4px',
            });
        });
    });

    $owner.addEventListener('mouseleave', () => $tooltip.remove());
}
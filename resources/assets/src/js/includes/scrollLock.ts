const $owners = new Set<HTMLElement>();
let locked = false;

const update = () => {
    if (locked === ($owners.size > 0)) {
        return;
    }

    locked = !locked;

    const $html = document.documentElement;

    $html.style.paddingRight = locked ? `${window.innerWidth - $html.clientWidth}px` : '';
    $html.style.overflow = locked ? 'hidden' : '';
}

export const lockScroll = ($owner: HTMLElement) => {
    $owners.add($owner);
    update();
}

export const unlockScroll = ($owner: HTMLElement) => {
    $owners.delete($owner);
    update();
}

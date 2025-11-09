export default ($input: HTMLInputElement) => {
    const selector = $input.dataset.filter;
    const $targets = document.querySelectorAll(selector) as NodeListOf<HTMLElement>;

    if ($targets) {
        $input.addEventListener('input', () => {
            const value = $input.value.toLowerCase();

            $targets.forEach(($item) => {
                const text = $item.textContent.toLowerCase();
                const found = text.split(' ').some(word => word.startsWith(value));

                $item.style.display = found ? 'block' : 'none';
            });
        });
    }
}
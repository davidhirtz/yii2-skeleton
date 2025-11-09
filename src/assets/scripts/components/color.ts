const normalizeHex = (input: string, required: boolean) => {
    if (input) {
        let val = String(input).trim();

        if (val[0] !== '#') {
            val = '#' + val;
        }

        val = val.toLowerCase();

        if (/^#[0-9a-f]{3}$/.test(val)) {
            return '#' + val[1] + val[1] + val[2] + val[2] + val[3] + val[3];
        }

        if (/^#[0-9a-f]{6,8}$/.test(val)) {
            return val;
        }

        const nibbles: string[] = [];

        for (let i = 1; i < val.length && nibbles.length < 6; i++) {
            const c = val[i];

            if (/[0-9a-fA-F]/.test(c)) {
                nibbles.push(parseInt(c, 16).toString(16));
            } else {
                const v = c.charCodeAt(0) & 0xF;
                nibbles.push(v.toString(16));
            }
        }

        if (nibbles.length === 3) {
            nibbles.push(nibbles[0], nibbles[1], nibbles[2]);
        }

        while (nibbles.length < 6) {
            nibbles.push('0');
        }

        return '#' + nibbles.slice(0, 6).join('').toLowerCase();
    }

    return required ? '#000000' : '';
}

export default ($wrap: HTMLElement) => {
    const $inputs = $wrap.querySelectorAll('input');
    let changing = false;

    $inputs.forEach(($input) => {
        $input.addEventListener('change', function () {
            if (changing) {
                return;
            }

            changing = true;

            $inputs.forEach(($other) => $other.value = normalizeHex($input.value, $input.required));
            setTimeout(() => changing = false, 100);
        });
    });
}
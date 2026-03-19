(function () {
    const lengthInput = document.getElementById('length');
    const hint = document.getElementById('length-hint');

    const digits = document.getElementById('digits');
    const uppercase = document.getElementById('uppercase');
    const lowercase = document.querySelector('input[name="lowercase"]');

    function minLength() {
        let c = 0;
        if (digits && digits.checked) c++;
        if (uppercase && uppercase.checked) c++;
        if (lowercase && lowercase.checked) c++;
        return Math.max(1, c);
    }

    function update() {
        const min = minLength();
        if (lengthInput) {
            lengthInput.min = String(min);
        }

        if (hint) {
            const current = lengthInput && lengthInput.value !== '' ? Number(lengthInput.value) : null;
            if (current !== null && !Number.isNaN(current) && current < min) {
                hint.textContent = 'Minimum length for the selected sets: ' + min + '. Current value is below the minimum.';
            } else {
                hint.textContent = 'Minimum length for the selected sets: ' + min + '.';
            }
        }
    }

    [digits, uppercase, lowercase, lengthInput].forEach((el) => {
        if (!el) return;
        el.addEventListener('change', update);
        el.addEventListener('input', update);
    });

    update();
})();

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 720px;
            margin: 32px auto;
            padding: 0 12px;
            line-height: 1.4;
        }
        h1 { margin: 0 0 16px; }
        form { border: 1px solid #ddd; padding: 16px; border-radius: 8px; }
        label { display: inline-block; margin: 6px 0; }
        input[type="number"], input[type="text"] {
            width: 100%;
            max-width: 420px;
            padding: 8px 10px;
            margin-top: 6px;
        }
        .row { margin: 10px 0; }
        .errors { border: 1px solid #f3b4be; background: #fff5f7; padding: 10px 12px; border-radius: 8px; margin: 0 0 12px; }
        .errors ul { margin: 0; padding-left: 18px; }
        .result { border: 1px solid #c7dbff; background: #f3f8ff; padding: 10px 12px; border-radius: 8px; margin-top: 14px; }
        pre { margin: 8px 0 0; padding: 10px; background: #fff; border: 1px solid #eee; border-radius: 8px; overflow: auto; }
        button { padding: 8px 12px; }
        details { margin-top: 12px; }
        summary { cursor: pointer; }
        .muted { color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Password Generator</h1>

    @if ($errors->any())
        <div class="errors">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('passwords.generate') }}">
        @csrf

        <div class="row">
            <label>
                Length:
                <input id="length" type="number" name="length" value="{{ old('length', 12) }}" min="1" required>
            </label>
            <div id="length-hint" class="muted"></div>
        </div>

        <div class="row">
            <label>
                <input id="digits" type="checkbox" name="digits" value="1" {{ old('digits') ? 'checked' : '' }}>
                Digits
            </label>
        </div>

        <div class="row">
            <label>
                Digits to exclude (optional):
                <input type="text" name="digits_exclude" value="{{ old('digits_exclude', '') }}" placeholder="e.g. 019">
            </label>
            <div class="muted">If filled in, the specified digits will not be used.</div>
        </div>

        <div class="row">
            <label>
                <input id="uppercase" type="checkbox" name="uppercase" value="1" {{ old('uppercase') ? 'checked' : '' }}>
                Uppercase letters
            </label>
        </div>

        <div class="row">
            <label>
                Uppercase letters to exclude (optional):
                <input type="text" name="uppercase_exclude" value="{{ old('uppercase_exclude', '') }}" placeholder="e.g. OIL">
            </label>
            <div class="muted">If filled in, the specified letters will not be used. Input is case-insensitive.</div>
        </div>

        <div class="row">
            <label>
                <input type="checkbox" name="lowercase" value="1" {{ old('lowercase') ? 'checked' : '' }}>
                Lowercase letters
            </label>
        </div>

        <div class="row">
            <label>
                Lowercase letters to exclude (optional):
                <input type="text" name="lowercase_exclude" value="{{ old('lowercase_exclude', '') }}" placeholder="e.g. oil">
            </label>
            <div class="muted">If filled in, the specified letters will not be used. Input is case-insensitive.</div>
        </div>

        <button type="submit">Generate</button>

        <details>
            <summary>Rules (help)</summary>
            <ul>
                <li>Characters inside a password do not repeat.</li>
                <li>Passwords are globally unique (stored in DB by hash).</li>
                <li>If multiple sets are selected, the password includes at least 1 character from each set.</li>
                <li>If the length exceeds the number of available unique characters, you will get an error.</li>
            </ul>
        </details>
    </form>

    <script>
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
    </script>

    @if (session('generated_password'))
        <div class="result">
            <strong>Password</strong>
            <pre>{{ session('generated_password') }}</pre>
        </div>
    @endif
</body>
</html>

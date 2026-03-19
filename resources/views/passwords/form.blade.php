<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Generator</title>
    <link rel="stylesheet" href="{{ asset('css/passwords.css') }}">
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

    @if (session('length_notice'))
        <div class="result">
            <strong>Notice</strong>
            <pre>{{ session('length_notice') }}</pre>
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
                <input type="radio" name="length_mode" value="hard" {{ old('length_mode', 'hard') === 'hard' ? 'checked' : '' }}>
                Hard length
            </label>
            <label>
                <input type="radio" name="length_mode" value="soft" {{ old('length_mode', 'hard') === 'soft' ? 'checked' : '' }}>
                Soft length
            </label>
            <div class="muted">Soft length will generate the maximum possible length if the requested length is not possible.</div>
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
    <script src="{{ asset('js/passwords.js') }}" defer></script>

    @if (session('generated_password'))
        <div class="result">
            <strong>Password</strong>
            <pre>{{ session('generated_password') }}</pre>
        </div>
    @endif
</body>
</html>

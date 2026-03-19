<?php

namespace App\Http\Controllers;

use App\Exceptions\PasswordGenerationException;
use App\Services\PasswordGeneratorService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function __construct(
        private readonly PasswordGeneratorService $passwordGenerator,
    ) {
    }

    public function showForm(): View
    {
        return view('passwords.form');
    }

    public function generate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'length' => ['required', 'integer', 'min:1', 'max:1000'],
            'length_mode' => ['nullable', 'string', 'in:hard,soft'],
            'digits' => ['nullable', 'boolean'],
            'uppercase' => ['nullable', 'boolean'],
            'lowercase' => ['nullable', 'boolean'],
            'digits_exclude' => ['nullable', 'string', 'max:500'],
            'uppercase_exclude' => ['nullable', 'string', 'max:500'],
            'lowercase_exclude' => ['nullable', 'string', 'max:500'],
        ]);

        $length = (int) $data['length'];
        $digits = (bool) ($data['digits'] ?? false);
        $uppercase = (bool) ($data['uppercase'] ?? false);
        $lowercase = (bool) ($data['lowercase'] ?? false);
        $digitsExclude = $data['digits_exclude'] ?? null;
        $uppercaseExclude = $data['uppercase_exclude'] ?? null;
        $lowercaseExclude = $data['lowercase_exclude'] ?? null;
        $lengthMode = $data['length_mode'] ?? 'hard';

        try {
            $result = $this->passwordGenerator->generateUniqueResult(
                length: $length,
                digits: $digits,
                uppercase: $uppercase,
                lowercase: $lowercase,
                digitsExclude: $digitsExclude,
                uppercaseExclude: $uppercaseExclude,
                lowercaseExclude: $lowercaseExclude,
                lengthMode: $lengthMode,
            );
        } catch (PasswordGenerationException $e) {
            return back()->withErrors(['length' => $e->getMessage()])->withInput();
        } catch (QueryException $e) {
            return back()->withErrors([
                'length' => 'Failed to save the password to the database. Please try again.',
            ])->withInput();
        }

        $flash = [
            'generated_password' => $result->password,
        ];

        if ($result->lengthMode === 'soft' && $result->actualLength < $result->requestedLength) {
            $flash['length_notice'] = sprintf(
                'Requested length %d is not possible with the selected sets. Generated a password with length %d (max available).',
                $result->requestedLength,
                $result->actualLength,
            );
        }

        return back()->with($flash)->withInput();
    }
}

<?php

namespace App\Services;

use App\Enums\PasswordErrorCode;
use App\Exceptions\PasswordGenerationException;
use App\Models\GeneratedPassword;
use Illuminate\Database\QueryException;

class PasswordGeneratorService
{
    private const string DIGITS = '0123456789';
    private const string LETTERS = 'abcdefghijklmnopqrstuvwxyz';

    private const string SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION = '23000';

    private const array UNIQUE_VIOLATION_CODES = [
        19,
        1062,
        2067,
    ];

    private const array UNIQUE_VIOLATION_MESSAGE_PARTS = [
        'UNIQUE constraint failed',
        'Duplicate entry',
        'unique constraint',
    ];

    public function generateUnique(
        int $length,
        bool $digits,
        bool $uppercase,
        bool $lowercase,
        ?string $digitsExclude = null,
        ?string $uppercaseExclude = null,
        ?string $lowercaseExclude = null,
    ): string {
        $sets = $this->buildSelectedSets(
            digits: $digits,
            uppercase: $uppercase,
            lowercase: $lowercase,
            digitsExclude: $digitsExclude,
            uppercaseExclude: $uppercaseExclude,
            lowercaseExclude: $lowercaseExclude,
        );

        if ($length < count($sets)) {
            throw new PasswordGenerationException(PasswordErrorCode::LengthTooSmallForSelectedSets);
        }

        $pool = $this->buildPool($sets);

        if ($length > count($pool)) {
            throw new PasswordGenerationException(PasswordErrorCode::LengthExceedsAvailableUniqueChars);
        }

        $options = [
            'digits' => $digits,
            'uppercase' => $uppercase,
            'lowercase' => $lowercase,
            'digits_exclude' => $digitsExclude,
            'uppercase_exclude' => $uppercaseExclude,
            'lowercase_exclude' => $lowercaseExclude,
        ];

        $attempts = 0;
        $maxAttempts = 200;

        while ($attempts < $maxAttempts) {
            $attempts++;

            $password = $this->generateOnce($length, $sets, $pool);
            $hash = hash('sha256', $password);

            try {
                GeneratedPassword::create([
                    'hash' => $hash,
                    'length' => $length,
                    'options' => $options,
                ]);

                return $password;
            } catch (QueryException $e) {
                if (!$this->isUniqueConstraintViolation($e)) {
                    throw $e;
                }
            }
        }

        throw new PasswordGenerationException(PasswordErrorCode::UniquePasswordNotFound);
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function buildSelectedSets(
        bool $digits,
        bool $uppercase,
        bool $lowercase,
        ?string $digitsExclude,
        ?string $uppercaseExclude,
        ?string $lowercaseExclude,
    ): array {
        $sets = [];

        if ($digits) {
            $sets[] = $this->buildSetWithExclusions(
                base: str_split(self::DIGITS),
                excludeInput: $digitsExclude,
                normalizeExclude: fn (string $value): array => $this->normalizeChars($value, '/[0-9]/'),
                invalidExcludeCode: PasswordErrorCode::DigitsExcludeInvalid,
                excludedAllCode: PasswordErrorCode::DigitsExcludedAll,
            );
        }

        if ($uppercase) {
            $sets[] = $this->buildSetWithExclusions(
                base: str_split(strtoupper(self::LETTERS)),
                excludeInput: $uppercaseExclude,
                normalizeExclude: $this->normalizeUppercaseChars(...),
                invalidExcludeCode: PasswordErrorCode::UppercaseExcludeInvalid,
                excludedAllCode: PasswordErrorCode::UppercaseExcludedAll,
            );
        }

        if ($lowercase) {
            $sets[] = $this->buildSetWithExclusions(
                base: str_split(self::LETTERS),
                excludeInput: $lowercaseExclude,
                normalizeExclude: $this->normalizeLowercaseChars(...),
                invalidExcludeCode: PasswordErrorCode::LowercaseExcludeInvalid,
                excludedAllCode: PasswordErrorCode::LowercaseExcludedAll,
            );
        }

        if ($sets === []) {
            throw new PasswordGenerationException(PasswordErrorCode::NoCharSetsSelected);
        }

        foreach ($sets as $set) {
            if ($set === []) {
                throw new PasswordGenerationException(PasswordErrorCode::NoCharSetsSelected);
            }
        }

        return $sets;
    }

    /**
     * @param array<int, string> $base
     * @param callable(string): array<int, string> $normalizeExclude
     * @return array<int, string>
     */
    private function buildSetWithExclusions(
        array $base,
        ?string $excludeInput,
        callable $normalizeExclude,
        PasswordErrorCode $invalidExcludeCode,
        PasswordErrorCode $excludedAllCode,
    ): array {
        if ($excludeInput === null || trim($excludeInput) === '') {
            return $base;
        }

        $exclude = $normalizeExclude($excludeInput);
        if ($exclude === []) {
            throw new PasswordGenerationException($invalidExcludeCode);
        }

        $set = array_values(array_diff($base, $exclude));
        if ($set === []) {
            throw new PasswordGenerationException($excludedAllCode);
        }

        return $set;
    }

    /**
     * @param array<int, array<int, string>> $sets
     * @return array<int, string>
     */
    private function buildPool(array $sets): array
    {
        $pool = [];

        foreach ($sets as $set) {
            foreach ($set as $ch) {
                $pool[$ch] = true;
            }
        }

        return array_keys($pool);
    }

    /**
     * @param array<int, array<int, string>> $sets
     * @param array<int, string> $pool
     */
    private function generateOnce(int $length, array $sets, array $pool): string
    {
        $available = array_values($pool);
        $result = [];

        foreach ($sets as $set) {
            $picked = $set[random_int(0, count($set) - 1)];

            if (!in_array($picked, $available, true)) {
                $picked = $this->pickFromIntersection($set, $available);
                if ($picked === null) {
                    throw new PasswordGenerationException(PasswordErrorCode::NotEnoughUniqueCharsForSelectedSets);
                }
            }

            $result[] = $picked;
            $available = array_values(array_diff($available, [$picked]));
        }

        $need = $length - count($result);
        for ($i = 0; $i < $need; $i++) {
            if ($available === []) {
                throw new PasswordGenerationException(PasswordErrorCode::PoolExhausted);
            }

            $picked = $available[random_int(0, count($available) - 1)];
            $result[] = $picked;
            $available = array_values(array_diff($available, [$picked]));
        }

        $this->shuffleSecure($result);

        return implode('', $result);
    }

    /**
     * @param array<int, string> $set
     * @param array<int, string> $available
     */
    private function pickFromIntersection(array $set, array $available): ?string
    {
        $intersection = array_values(array_intersect($set, $available));
        if ($intersection === []) {
            return null;
        }

        return $intersection[random_int(0, count($intersection) - 1)];
    }

    /**
     * @param array<int, string> $chars
     */
    private function shuffleSecure(array &$chars): void
    {
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }
    }

    /**
     * @return array<int, string>
     */
    private function normalizeUppercaseChars(string $input): array
    {
        $input = strtoupper($input);
        return $this->normalizeChars($input, '/[A-Z]/');
    }

    private function normalizeLowercaseChars(string $input): array
    {
        $input = strtolower($input);
        return $this->normalizeChars($input, '/[a-z]/');
    }

    /**
     * @return array<int, string>
     */
    private function normalizeChars(string $input, string $pattern): array
    {
        $input = trim($input);
        if ($input === '') {
            return [];
        }

        $chars = preg_split('//u', $input, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($chars)) {
            return [];
        }

        $out = [];
        foreach ($chars as $ch) {
            if (preg_match($pattern, $ch) === 1) {
                $out[$ch] = true;
            }
        }

        return array_keys($out);
    }

    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $driverCode = $e->errorInfo[1] ?? null;
        $driverMessage = (string) ($e->errorInfo[2] ?? '');

        if (!$this->isIntegrityConstraintViolation($sqlState)) {
            return false;
        }

        if ($this->isKnownUniqueViolationCode($driverCode)) {
            return true;
        }

        return $this->hasUniqueViolationMessage($driverMessage);
    }

    private function isIntegrityConstraintViolation(mixed $sqlState): bool
    {
        return $sqlState === self::SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION;
    }

    private function isKnownUniqueViolationCode(mixed $driverCode): bool
    {
        if (!is_int($driverCode)) {
            return false;
        }

        return in_array($driverCode, self::UNIQUE_VIOLATION_CODES, true);
    }

    private function hasUniqueViolationMessage(string $driverMessage): bool
    {
        foreach (self::UNIQUE_VIOLATION_MESSAGE_PARTS as $part) {
            if (str_contains($driverMessage, $part)) {
                return true;
            }
        }

        return false;
    }
}

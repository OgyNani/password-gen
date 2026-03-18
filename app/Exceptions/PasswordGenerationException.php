<?php

namespace App\Exceptions;

use App\Enums\PasswordErrorCode;

class PasswordGenerationException extends \RuntimeException
{
    public function __construct(
        public readonly PasswordErrorCode $errorCode,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($this->messageFor($errorCode), 0, $previous);
    }

    private function messageFor(PasswordErrorCode $code): string
    {
        return match ($code) {
            PasswordErrorCode::NoCharSetsSelected => 'Select at least one character set.',
            PasswordErrorCode::LengthTooSmallForSelectedSets => 'Password length must be at least the number of selected character sets.',
            PasswordErrorCode::LengthExceedsAvailableUniqueChars => 'Password length exceeds the number of available unique characters for the selected sets.',
            PasswordErrorCode::DigitsExcludeInvalid => 'The excluded digits set is empty or contains invalid characters.',
            PasswordErrorCode::UppercaseExcludeInvalid => 'The excluded uppercase letters set is empty or contains invalid characters.',
            PasswordErrorCode::LowercaseExcludeInvalid => 'The excluded lowercase letters set is empty or contains invalid characters.',
            PasswordErrorCode::DigitsExcludedAll => 'You excluded all digits — the digits set became empty.',
            PasswordErrorCode::UppercaseExcludedAll => 'You excluded all uppercase letters — the uppercase set became empty.',
            PasswordErrorCode::LowercaseExcludedAll => 'You excluded all lowercase letters — the lowercase set became empty.',
            PasswordErrorCode::NotEnoughUniqueCharsForSelectedSets => 'Not enough unique characters for the selected sets.',
            PasswordErrorCode::PoolExhausted => 'No more unique characters available for the selected sets.',
            PasswordErrorCode::UniquePasswordNotFound => 'All (or almost all) possible passwords for these rules already exist. Try increasing the length or changing the character sets.',
            PasswordErrorCode::DatabaseWriteFailed => 'Failed to save the password to the database. Please try again.',
        };
    }
}

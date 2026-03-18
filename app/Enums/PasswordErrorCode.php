<?php

namespace App\Enums;

enum PasswordErrorCode: string
{
    case NoCharSetsSelected = 'no_char_sets_selected';
    case LengthTooSmallForSelectedSets = 'length_too_small_for_selected_sets';
    case LengthExceedsAvailableUniqueChars = 'length_exceeds_available_unique_chars';
    case DigitsExcludeInvalid = 'digits_exclude_invalid';
    case UppercaseExcludeInvalid = 'uppercase_exclude_invalid';
    case LowercaseExcludeInvalid = 'lowercase_exclude_invalid';
    case DigitsExcludedAll = 'digits_excluded_all';
    case UppercaseExcludedAll = 'uppercase_excluded_all';
    case LowercaseExcludedAll = 'lowercase_excluded_all';
    case NotEnoughUniqueCharsForSelectedSets = 'not_enough_unique_chars_for_selected_sets';
    case PoolExhausted = 'pool_exhausted';
    case UniquePasswordNotFound = 'unique_password_not_found';
    case DatabaseWriteFailed = 'database_write_failed';
}

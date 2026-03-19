<?php

namespace App\DTO;

final class PasswordGenerationResult
{
    public function __construct(
        public readonly string $password,
        public readonly int $requestedLength,
        public readonly int $actualLength,
        public readonly int $maxLength,
        public readonly string $lengthMode,
    ) {
    }
}

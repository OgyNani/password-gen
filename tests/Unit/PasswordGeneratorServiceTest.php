<?php

namespace Tests\Unit;

use App\Enums\PasswordErrorCode;
use App\Exceptions\PasswordGenerationException;
use App\Models\GeneratedPassword;
use App\Services\PasswordGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_password_without_repeated_characters(): void
    {
        $service = new PasswordGeneratorService();

        $result = $service->generateUniqueResult(
            length: 20,
            digits: true,
            uppercase: true,
            lowercase: true,
            lengthMode: 'hard',
        );

        $this->assertSame(20, $result->actualLength);
        $chars = str_split($result->password);
        $this->assertCount(20, array_unique($chars));
    }

    public function test_generates_password_with_at_least_one_char_from_each_selected_set(): void
    {
        $service = new PasswordGeneratorService();

        $result = $service->generateUniqueResult(
            length: 12,
            digits: true,
            uppercase: true,
            lowercase: true,
            lengthMode: 'hard',
        );

        $this->assertMatchesRegularExpression('/[0-9]/', $result->password);
        $this->assertMatchesRegularExpression('/[A-Z]/', $result->password);
        $this->assertMatchesRegularExpression('/[a-z]/', $result->password);
    }

    public function test_excluded_characters_are_not_used(): void
    {
        $service = new PasswordGeneratorService();

        $result = $service->generateUniqueResult(
            length: 20,
            digits: true,
            uppercase: true,
            lowercase: true,
            digitsExclude: '019',
            uppercaseExclude: 'OIL',
            lowercaseExclude: 'oil',
            lengthMode: 'hard',
        );

        $this->assertDoesNotMatchRegularExpression('/[019]/', $result->password);
        $this->assertDoesNotMatchRegularExpression('/[OIL]/', $result->password);
        $this->assertDoesNotMatchRegularExpression('/[oil]/', $result->password);
    }

    public function test_soft_length_caps_to_max_length(): void
    {
        $service = new PasswordGeneratorService();

        $result = $service->generateUniqueResult(
            length: 1000,
            digits: true,
            uppercase: true,
            lowercase: true,
            lengthMode: 'soft',
        );

        $this->assertSame(1000, $result->requestedLength);
        $this->assertGreaterThan(0, $result->maxLength);
        $this->assertSame($result->maxLength, $result->actualLength);
        $this->assertSame($result->actualLength, strlen($result->password));
    }

    public function test_hard_length_throws_when_exceeds_max_length(): void
    {
        $service = new PasswordGeneratorService();

        try {
            $service->generateUniqueResult(
                length: 1000,
                digits: true,
                uppercase: true,
                lowercase: true,
                lengthMode: 'hard',
            );

            $this->fail('Expected PasswordGenerationException was not thrown.');
        } catch (PasswordGenerationException $e) {
            $this->assertSame(PasswordErrorCode::LengthExceedsAvailableUniqueChars, $e->errorCode);
        }
    }

    public function test_excluding_all_digits_throws_domain_error(): void
    {
        $service = new PasswordGeneratorService();

        try {
            $service->generateUniqueResult(
                length: 1,
                digits: true,
                uppercase: false,
                lowercase: false,
                digitsExclude: '0123456789',
                lengthMode: 'hard',
            );

            $this->fail('Expected PasswordGenerationException was not thrown.');
        } catch (PasswordGenerationException $e) {
            $this->assertSame(PasswordErrorCode::DigitsExcludedAll, $e->errorCode);
        }
    }

    public function test_retry_loop_handles_duplicate_hash_and_eventually_fails(): void
    {
        $engine = new class implements \Random\Engine {
            public function generate(): string
            {
                return str_repeat("\0", 64);
            }
        };

        $randomizer = new \Random\Randomizer($engine);
        $service = new PasswordGeneratorService($randomizer);

        $password = '0';
        GeneratedPassword::create([
            'hash' => hash('sha256', $password),
            'length' => 1,
            'options' => [],
        ]);

        try {
            $service->generateUniqueResult(
                length: 1,
                digits: true,
                uppercase: false,
                lowercase: false,
                lengthMode: 'hard',
            );

            $this->fail('Expected PasswordGenerationException was not thrown.');
        } catch (PasswordGenerationException $e) {
            $this->assertSame(PasswordErrorCode::UniquePasswordNotFound, $e->errorCode);
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for backend form validation rules.
 */
final class ValidatorTest extends TestCase
{
    /**
     * Build a valid payload, optionally overriding fields.
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'amount' => '1500',
            'buyer' => 'Test User',
            'receipt_id' => 'ABCDEF',
            'items' => 'Laptop, Mouse',
            'buyer_email' => 'test@example.com',
            'note' => 'A short valid note.',
            'city' => 'Dhaka',
            'phone' => '8801712345678',
            'entry_by' => '1',
        ], $overrides);
    }

    public function testValidPayloadPasses(): void
    {
        $validator = new Validator();

        $this->assertTrue($validator->validate($this->validPayload()));
        $this->assertSame([], $validator->errors());
    }

    public function testUnicodeNoteIsAllowed(): void
    {
        $validator = new Validator();

        $this->assertTrue($validator->validate($this->validPayload([
            'note' => 'নোট with café résumé',
        ])));
    }

    /**
     * @param array<string, mixed> $overrides
     * @param list<string> $expectedFields
     */
    #[DataProvider('invalidPayloadProvider')]
    public function testInvalidPayloadFails(array $overrides, array $expectedFields): void
    {
        $validator = new Validator();

        $this->assertFalse($validator->validate($this->validPayload($overrides)));

        $errors = $validator->errors();
        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $errors, "Expected error for {$field}");
        }
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: list<string>}>
     */
    public static function invalidPayloadProvider(): array
    {
        return [
            'amount letters' => [['amount' => '12a'], ['amount']],
            'buyer too long' => [['buyer' => str_repeat('a', 21)], ['buyer']],
            'buyer symbols' => [['buyer' => 'Bad!'], ['buyer']],
            'receipt digits' => [['receipt_id' => 'ABC123'], ['receipt_id']],
            'items empty' => [['items' => ''], ['items']],
            'items digits' => [['items' => 'Item1'], ['items']],
            'email bad' => [['buyer_email' => 'not-an-email'], ['buyer_email']],
            'note too many words' => [[
                'note' => implode(' ', array_fill(0, 31, 'word')),
            ], ['note']],
            'city digits' => [['city' => 'Dhaka1'], ['city']],
            'phone missing 880' => [['phone' => '1712345678'], ['phone']],
            'phone letters' => [['phone' => '880abc'], ['phone']],
            'entry_by letters' => [['entry_by' => 'x'], ['entry_by']],
        ];
    }
}

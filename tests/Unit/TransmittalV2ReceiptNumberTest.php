<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\TransmittalV2\TransmittalAcknowledgmentToken;
use App\Services\TransmittalV2\TransmittalReceiptNumber;
use PHPUnit\Framework\TestCase;

final class TransmittalV2ReceiptNumberTest extends TestCase
{
    public function test_generated_receipt_numbers_have_v2_prefix_and_safe_width(): void
    {
        $number = (new TransmittalReceiptNumber())->generate();

        self::assertMatchesRegularExpression('/\ATRR-[A-F0-9]{32}\z/', $number);
        self::assertLessThanOrEqual(255, strlen($number));
    }

    public function test_generated_receipt_numbers_are_distinct_in_a_synthetic_batch(): void
    {
        $generator = new TransmittalReceiptNumber();
        $numbers = [];

        for ($index = 0; $index < 100; $index++) {
            $numbers[] = $generator->generate();
        }

        self::assertCount(100, array_unique($numbers));
    }

    public function test_acknowledgment_tokens_require_url_safe_opaque_values(): void
    {
        self::assertTrue(TransmittalAcknowledgmentToken::isValid(str_repeat('A', 64)));
        self::assertFalse(TransmittalAcknowledgmentToken::isValid(str_repeat('A', 31)));
        self::assertFalse(TransmittalAcknowledgmentToken::isValid(str_repeat('A', 101)));
        self::assertFalse(TransmittalAcknowledgmentToken::isValid(str_repeat('A', 63).'/'));
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', TransmittalAcknowledgmentToken::hash(str_repeat('A', 64)));
    }
}

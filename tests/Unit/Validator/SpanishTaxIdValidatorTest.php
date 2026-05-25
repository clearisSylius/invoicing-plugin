<?php

declare(strict_types=1);

namespace Tests\ClearisSylius\InvoicingPlugin\Unit\Validator;

use ClearisSylius\InvoicingPlugin\Validator\Constraints\SpanishTaxId;
use ClearisSylius\InvoicingPlugin\Validator\Constraints\SpanishTaxIdValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<SpanishTaxIdValidator>
 */
final class SpanishTaxIdValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): SpanishTaxIdValidator
    {
        return new SpanishTaxIdValidator();
    }

    public function testNullPassesUnvalidated(): void
    {
        $this->validator->validate(null, new SpanishTaxId());
        $this->assertNoViolation();
    }

    public function testEmptyStringPassesUnvalidated(): void
    {
        $this->validator->validate('', new SpanishTaxId());
        $this->assertNoViolation();
    }

    #[DataProvider('validValues')]
    public function testAcceptsValid(string $value): void
    {
        $this->validator->validate($value, new SpanishTaxId());
        $this->assertNoViolation();
    }

    #[DataProvider('invalidFormatValues')]
    public function testRejectsInvalidFormat(string $value): void
    {
        $this->validator->validate($value, new SpanishTaxId());
        $this->buildViolation('clearis.tax_id.invalid_format')->assertRaised();
    }

    #[DataProvider('invalidControlDigitValues')]
    public function testRejectsBadControlDigit(string $value): void
    {
        $this->validator->validate($value, new SpanishTaxId());
        $this->buildViolation('clearis.tax_id.invalid')->assertRaised();
    }

    /** @return iterable<string, array{string}> */
    public static function validValues(): iterable
    {
        // DNI: 8 digits + correct letter (letters table indexed by N mod 23,
        // alphabet "TRWAGMYFPDXBNJZSQVHLCKE").
        yield 'DNI 00000000T' => ['00000000T'];   // 0 mod 23 = 0  → T
        yield 'DNI 12345678Z' => ['12345678Z'];   // 12345678 mod 23 = 14 → Z

        // NIE: X/Y/Z prefix mapped to 0/1/2 then DNI algorithm.
        yield 'NIE X1234567L' => ['X1234567L'];   // 1234567 mod 23 = 19 → L
        yield 'NIE Y0000000Z' => ['Y0000000Z'];   // 10000000 mod 23 = 14 → Z
    }

    /** @return iterable<string, array{string}> */
    public static function invalidFormatValues(): iterable
    {
        yield 'too short' => ['1234567T'];
        yield 'NIE bad prefix' => ['A1234567L'];
        yield 'CIF unknown org letter' => ['M1234567A'];
        yield 'random garbage' => ['hello world'];
    }

    /** @return iterable<string, array{string}> */
    public static function invalidControlDigitValues(): iterable
    {
        yield 'DNI wrong letter' => ['12345678A'];
        yield 'NIE wrong letter' => ['X1234567A'];
        yield 'CIF wrong digit'  => ['A12345670'];
    }
}

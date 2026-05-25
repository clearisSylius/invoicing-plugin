<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validates the three Spanish fiscal id forms with their real control digit
 * algorithms. References:
 *
 * - DNI (persona física residente): 8 dígitos + letra de control = char
 *   "TRWAGMYFPDXBNJZSQVHLCKE"[N mod 23].
 * - NIE (persona física no residente): primera letra X/Y/Z mapeada a 0/1/2,
 *   luego 7 dígitos + letra. Igual algoritmo que DNI sobre el número resultante.
 * - CIF (persona jurídica, formato heredado pero AEAT lo sigue aceptando):
 *   letra organización + 7 dígitos + carácter control (letra o dígito según
 *   la organización). Algoritmo de Luhn modificado: suma de impares (×2) +
 *   suma de pares; control = (10 − unidades de la suma) mod 10. Letras de
 *   organización que terminan en letra de control: P, Q, R, S, W; las demás
 *   pueden terminar en dígito.
 */
final class SpanishTaxIdValidator extends ConstraintValidator
{
    private const DNI_LETTERS = 'TRWAGMYFPDXBNJZSQVHLCKE';

    private const CIF_CONTROL_LETTERS = 'JABCDEFGHI';

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SpanishTaxId) {
            throw new UnexpectedTypeException($constraint, SpanishTaxId::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $normalised = strtoupper(preg_replace('/[\s\-_.]/', '', $value) ?? '');

        if (!$this->matchesAnyFormat($normalised)) {
            $this->context->buildViolation($constraint->invalidFormatMessage)->addViolation();

            return;
        }

        $valid = match (true) {
            preg_match('/^[0-9]{8}[A-Z]$/', $normalised) === 1 => $this->isValidDni($normalised),
            preg_match('/^[XYZ][0-9]{7}[A-Z]$/', $normalised) === 1 => $this->isValidNie($normalised),
            preg_match('/^[ABCDEFGHJNPQRSUVW][0-9]{7}[0-9A-J]$/', $normalised) === 1 => $this->isValidCif($normalised),
            default => false,
        };

        if (!$valid) {
            $this->context->buildViolation($constraint->invalidMessage)->addViolation();
        }
    }

    private function matchesAnyFormat(string $value): bool
    {
        return preg_match('/^[0-9]{8}[A-Z]$/', $value) === 1 ||
            preg_match('/^[XYZ][0-9]{7}[A-Z]$/', $value) === 1 ||
            preg_match('/^[ABCDEFGHJNPQRSUVW][0-9]{7}[0-9A-J]$/', $value) === 1;
    }

    private function isValidDni(string $value): bool
    {
        $number = (int) substr($value, 0, 8);
        $letter = substr($value, 8, 1);

        return $letter === self::DNI_LETTERS[$number % 23];
    }

    private function isValidNie(string $value): bool
    {
        $prefix = match ($value[0]) {
            'X' => '0',
            'Y' => '1',
            'Z' => '2',
            // Inalcanzable: isValidStructure() ya garantizó que la primera
            // letra es X, Y o Z. El default existe para satisfacer PHPStan
            // al nivel max (no puede seguir la pista a través del caller).
            default => throw new \LogicException(sprintf(
                'Unreachable: NIE prefix must be X, Y or Z, got "%s".',
                $value[0],
            )),
        };
        $number = (int) ($prefix . substr($value, 1, 7));
        $letter = substr($value, 8, 1);

        return $letter === self::DNI_LETTERS[$number % 23];
    }

    private function isValidCif(string $value): bool
    {
        $organisation = $value[0];
        $digits = substr($value, 1, 7);
        $providedControl = $value[8];

        $sumEven = 0;
        $sumOdd = 0;
        for ($i = 0; $i < 7; ++$i) {
            $d = (int) $digits[$i];
            if ($i % 2 === 0) {
                // Odd position (1st, 3rd, ...) — double and sum digits.
                $doubled = $d * 2;
                $sumOdd += intdiv($doubled, 10) + ($doubled % 10);
            } else {
                $sumEven += $d;
            }
        }

        $total = $sumEven + $sumOdd;
        $controlDigit = (10 - ($total % 10)) % 10;
        $controlLetter = self::CIF_CONTROL_LETTERS[$controlDigit];

        // Organisations P, Q, R, S, W always use the letter; N, J use letter
        // too. A, B, E, H expect a digit. Others accept either.
        return match ($organisation) {
            'P', 'Q', 'R', 'S', 'W', 'N', 'J' => $providedControl === $controlLetter,
            'A', 'B', 'E', 'H' => $providedControl === (string) $controlDigit,
            default => $providedControl === (string) $controlDigit || $providedControl === $controlLetter,
        };
    }
}

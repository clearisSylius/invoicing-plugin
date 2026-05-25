<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates a Spanish tax identifier: DNI, NIE or CIF, with the real control
 * digit check (not just a regex). Passes when the value is null or empty
 * because the constraint is meant to be optional — combine with NotBlank if
 * the form expects the field to be required.
 *
 * Usage:
 *   #[SpanishTaxId]
 *   public ?string $taxId = null;
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class SpanishTaxId extends Constraint
{
    public string $invalidMessage = 'clearis.tax_id.invalid';

    public string $invalidFormatMessage = 'clearis.tax_id.invalid_format';
}

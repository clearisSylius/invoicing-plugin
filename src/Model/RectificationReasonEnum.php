<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Model;

/**
 * Spanish RD 1619/2012 codes for a factura rectificativa. The admin form
 * exposes these in the "Emitir rectificativa" modal; the value is frozen on
 * the rectifying invoice for the libro registro.
 *
 * - R1: correction of an error not due to article 80 LIVA (e.g. typo).
 * - R2: art. 80.3 LIVA — bad debt because of formal insolvency proceedings.
 * - R3: art. 80.4 LIVA — bad debt for other reasons (collection failure).
 * - R4: art. 80.1, 80.2, 80.6 — other VAT base modifications (volume discount,
 *        returns, modifications of the consideration).
 * - R5: simplified invoice rectifications (B2C, art. 7 RD 1619/2012).
 *
 * @phpstan-type RectificationReason 'R1'|'R2'|'R3'|'R4'|'R5'
 */
final class RectificationReasonEnum
{
    public const R1 = 'R1';

    public const R2 = 'R2';

    public const R3 = 'R3';

    public const R4 = 'R4';

    public const R5 = 'R5';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::R1, self::R2, self::R3, self::R4, self::R5];
    }

    /** @return array<string, string> code => translation key */
    public static function labels(): array
    {
        return [
            self::R1 => 'clearis.invoice.rectification_reason.r1',
            self::R2 => 'clearis.invoice.rectification_reason.r2',
            self::R3 => 'clearis.invoice.rectification_reason.r3',
            self::R4 => 'clearis.invoice.rectification_reason.r4',
            self::R5 => 'clearis.invoice.rectification_reason.r5',
        ];
    }
}

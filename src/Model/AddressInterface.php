<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Model;

use Sylius\Component\Core\Model\AddressInterface as BaseAddressInterface;

/**
 * Resource override interface that adds the optional Spanish tax id field to
 * every Sylius address (shipping, billing, customer book).
 */
interface AddressInterface extends BaseAddressInterface
{
    public function getTaxId(): ?string;

    public function setTaxId(?string $taxId): void;
}
